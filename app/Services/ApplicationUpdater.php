<?php

namespace App\Services;

use App\Models\AuditLog;
use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

/**
 * Installs a published release package from the admin panel.
 *
 * Flow: download the release's prebuilt package (code + vendor + built assets),
 * verify its checksum, back up every file it replaces, copy the new files in,
 * then finish (migrations + cache clear) on the next request so new code runs
 * in a fresh PHP process. Rollback restores the backed-up files.
 */
class ApplicationUpdater
{
    public const REPOSITORY = 'OmanGhost/Blackmail';
    private const MAX_BYTES = 300 * 1024 * 1024;
    private const KEEP_BACKUPS = 3;

    private string $base;

    public function __construct(?string $basePath = null)
    {
        $this->base = $this->normalize($basePath ?? base_path());
    }

    public static function packageName(string $tag): string
    {
        return 'blackmail-'.$tag.'.tar.gz';
    }

    public function version(): string
    {
        return trim((string) @file_get_contents($this->base.'/VERSION'));
    }

    public function pending(): ?array
    {
        $marker = $this->workDir().'/pending.json';
        return is_file($marker) ? json_decode((string) file_get_contents($marker), true) : null;
    }

    /** @return array{tag: string, version: string, archive: string, checksum: string} */
    public function release(): array
    {
        $token = config('platform.updates.token');
        if (! is_string($token) || $token === '') {
            throw new UpdateFailed('not_configured');
        }
        try {
            $response = $this->github($token)->get('https://api.github.com/repos/'.self::REPOSITORY.'/releases/latest');
        } catch (Throwable) {
            throw new UpdateFailed('unavailable');
        }
        if (! $response->successful()) {
            throw new UpdateFailed('unavailable');
        }
        $data = $response->json();
        $tag = $data['tag_name'] ?? '';
        if (! is_string($tag) || ! preg_match('/^v(\d+\.\d+\.\d+)$/D', $tag, $match) || ($data['draft'] ?? true) || ($data['prerelease'] ?? true)) {
            throw new UpdateFailed('invalid_response');
        }
        $assets = collect($data['assets'] ?? [])->keyBy('name');
        $archive = $assets->get(self::packageName($tag))['url'] ?? null;
        $checksum = $assets->get(self::packageName($tag).'.sha256')['url'] ?? null;
        $prefix = 'https://api.github.com/repos/'.self::REPOSITORY.'/releases/assets/';
        if (! is_string($archive) || ! is_string($checksum) || ! str_starts_with($archive, $prefix) || ! str_starts_with($checksum, $prefix)) {
            throw new UpdateFailed('package_missing');
        }

        return ['tag' => $tag, 'version' => $match[1], 'archive' => $archive, 'checksum' => $checksum];
    }

    /**
     * Step 1 of 3: download and verify the latest release package.
     * Each step runs in its own request so none of them hits the host's time limit.
     */
    public function prepare(?int $userId = null): string
    {
        if ($this->pending()) {
            throw new UpdateFailed('pending');
        }

        return $this->locked(function () use ($userId) {
            $this->discardDownload();
            $release = $this->release();
            if (! version_compare($release['version'], $this->version(), '>')) {
                throw new UpdateFailed('not_newer');
            }
            $this->download($release, $this->workDir().'/download.tar.gz');
            file_put_contents($this->workDir().'/download.json', json_encode([
                'version' => $release['version'], 'user_id' => $userId, 'attempts' => 0, 'at' => now()->toIso8601String(),
            ]));

            return $release['version'];
        });
    }

    /** A verified package waiting for apply(). */
    public function downloaded(): ?array
    {
        $state = $this->workDir().'/download.json';
        if (! is_file($state) || ! is_file($this->workDir().'/download.tar.gz')) {
            return null;
        }

        return json_decode((string) file_get_contents($state), true) ?: null;
    }

    /** Step 2 of 3: unpack only the files that changed, back them up and copy them in. Step 3 is finalize(). */
    public function apply(): string
    {
        $state = $this->downloaded();
        if (! $state) {
            throw new UpdateFailed('download_failed');
        }

        return $this->locked(function () use ($state) {
            // A request killed by the host's time limit leaves the state behind; stop retrying after two tries.
            if (($state['attempts'] ?? 0) >= 2) {
                $this->discardDownload();
                throw new UpdateFailed('timed_out');
            }
            $state['attempts'] = ($state['attempts'] ?? 0) + 1;
            file_put_contents($this->workDir().'/download.json', json_encode($state));
            $staging = $this->workDir().'/staging';
            try {
                $this->deleteTree($staging);
                $package = $this->extract($this->workDir().'/download.tar.gz', $staging, $state['version']);
                $plan = $this->plan($package);
                $this->assertWritable($plan);
                $backup = $this->backup($plan, $state['version']);
                try {
                    foreach ($plan as $item) {
                        $this->copyFile($item['from'], $item['to']);
                    }
                } catch (Throwable $e) {
                    $this->restore($backup);
                    throw new UpdateFailed('copy_failed', $e);
                }
                file_put_contents($this->workDir().'/pending.json', json_encode([
                    'from' => $backup['from'], 'to' => $state['version'], 'backup' => $backup['id'],
                    'files' => count($plan), 'user_id' => $state['user_id'] ?? null, 'at' => now()->toIso8601String(),
                ]));
            } catch (UpdateFailed $e) {
                $this->discardDownload();
                throw $e;
            } finally {
                $this->deleteTree($staging);
            }
            $this->discardDownload();
            $this->resetOpcache();

            return $state['version'];
        });
    }

    /** Steps 1 and 2 in one go (terminal and tests). Run finalize() afterwards in a fresh process. */
    public function install(?int $userId = null): string
    {
        $this->prepare($userId);

        return $this->apply();
    }

    /** Runs in a fresh request after install(): apply migrations and clear caches. */
    public function finalize(): ?array
    {
        $pending = $this->pending();
        if (! $pending) {
            return null;
        }
        if ($this->version() !== ($pending['to'] ?? null)) {
            @unlink($this->workDir().'/pending.json');
            throw new UpdateFailed('version_mismatch');
        }
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            throw new UpdateFailed('migrate_failed', $e);
        }
        @unlink($this->workDir().'/pending.json');
        $this->clearCaches();
        $this->audit('application.updated', $pending + ['by' => $pending['user_id'] ?? null]);
        $this->pruneBackups();

        return $pending;
    }

    /** Restore the files replaced by an update. Database changes are kept. */
    public function rollback(string $id, ?int $userId = null): array
    {
        $manifest = collect($this->backups())->firstWhere('id', $id);
        if (! $manifest) {
            throw new UpdateFailed('backup_missing');
        }
        return $this->locked(function () use ($manifest, $id, $userId) {
            $this->restore($manifest);
            @unlink($this->workDir().'/pending.json');
            $this->discardDownload();
            $this->resetOpcache();
            $this->clearCaches();
            $this->audit('application.rolled_back', ['from' => $manifest['to'], 'to' => $manifest['from'], 'backup' => $id, 'by' => $userId]);

            return $manifest;
        });
    }

    /** @return array<int, array{id: string, from: string, to: string, created_at: string}> */
    public function backups(): array
    {
        $root = $this->workDir().'/backups';
        if (! is_dir($root)) {
            return [];
        }
        $list = [];
        foreach (glob($root.'/*/manifest.json') ?: [] as $file) {
            $manifest = json_decode((string) file_get_contents($file), true);
            if (is_array($manifest) && preg_match('/^[0-9]{8}-[0-9]{6}-[0-9a-f]{8}$/D', $manifest['id'] ?? '') && basename(dirname($file)) === $manifest['id']) {
                $list[] = $manifest;
            }
        }
        usort($list, fn ($a, $b) => strcmp($b['id'], $a['id']));

        return $list;
    }

    private function github(string $token)
    {
        return Http::withToken($token)->withHeaders(['X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'Blackmail-Updater'])->connectTimeout(5);
    }

    private function download(array $release, string $archive): void
    {
        $token = (string) config('platform.updates.token');
        try {
            // Asset downloads redirect to GitHub's storage host; the token is not forwarded across hosts.
            $checksum = $this->github($token)->accept('application/octet-stream')->timeout(30)->get($release['checksum']);
            $file = $this->github($token)->accept('application/octet-stream')->timeout(600)->withOptions(['sink' => $archive])->get($release['archive']);
        } catch (Throwable $e) {
            throw new UpdateFailed('download_failed', $e);
        }
        if (! $checksum->successful() || ! $file->successful() || ! is_file($archive)) {
            throw new UpdateFailed('download_failed');
        }
        if (filesize($archive) > self::MAX_BYTES) {
            throw new UpdateFailed('download_failed');
        }
        $expected = strtolower(strtok(trim($checksum->body()), " \t") ?: '');
        if (! preg_match('/^[0-9a-f]{64}$/D', $expected) || ! hash_equals($expected, hash_file('sha256', $archive))) {
            throw new UpdateFailed('checksum_failed');
        }
    }

    /**
     * Unpacks only the files the update may copy (vendor/ is skipped when dependencies are unchanged).
     * Files are taken from the package's own list (.package-manifest) instead of the archive index,
     * and every extracted file must match its SHA-256 from that list.
     */
    private function extract(string $archive, string $staging, string $version): string
    {
        try {
            $phar = new PharData($archive);
            if (! isset($phar['package/.package-manifest'])) {
                throw new UpdateFailed('invalid_package');
            }
            $manifest = $this->readManifest($phar['package/.package-manifest']->getContent());
            foreach (['VERSION', 'artisan', 'composer.lock', 'vendor/autoload.php', 'public/build/manifest.json', 'app/Services/ApplicationUpdater.php'] as $required) {
                if (! isset($manifest[$required])) {
                    throw new UpdateFailed('invalid_package');
                }
            }
            if (trim($phar['package/VERSION']->getContent()) !== $version) {
                throw new UpdateFailed('invalid_package');
            }
            $sameVendor = $this->sameVendor($manifest['composer.lock']);
            $selected = array_filter($manifest, fn ($hash, $rel) => ! $this->skipped($rel, $sameVendor), ARRAY_FILTER_USE_BOTH);
            mkdir($staging, 0755, true);
            $phar->extractTo($staging, array_map(fn ($rel) => 'package/'.$rel, array_keys($selected)), true);
            unset($phar);
        } catch (UpdateFailed $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UpdateFailed('invalid_package', $e);
        }
        foreach ($selected as $rel => $hash) {
            $file = $staging.'/package/'.$rel;
            if (is_link($file) || ! is_file($file) || ! hash_equals($hash, hash_file('sha256', $file))) {
                throw new UpdateFailed('invalid_package');
            }
        }
        $realRoot = $this->normalize((string) realpath($staging));
        foreach ($this->walk($staging, true) as $path) {
            $real = realpath($path);
            if (is_link($path) || $real === false || ! str_starts_with($this->normalize($real), $realRoot.'/')) {
                throw new UpdateFailed('invalid_package');
            }
        }

        return $staging.'/package';
    }

    /** @return array<string, string> relative path => sha256, from "hash  path" lines (sha256sum format). */
    private function readManifest(string $content): array
    {
        $files = [];
        foreach (preg_split('/\r?\n/', trim($content)) as $line) {
            if (! preg_match('/^([0-9a-f]{64}) [ *](.+)$/D', $line, $match)) {
                throw new UpdateFailed('invalid_package');
            }
            $rel = $match[2];
            if ($rel === '.package-manifest') {
                continue;
            }
            if (str_starts_with($rel, '/') || str_contains($rel, '\\') || str_contains($rel, ':') || in_array('..', explode('/', $rel), true) || in_array('.', explode('/', $rel), true)) {
                throw new UpdateFailed('invalid_package');
            }
            $files[$rel] = $match[1];
        }
        if ($files === []) {
            throw new UpdateFailed('invalid_package');
        }

        return $files;
    }

    private function sameVendor(string $packageLockHash): bool
    {
        return is_file($this->base.'/composer.lock') && is_file($this->base.'/vendor/autoload.php')
            && hash_file('sha256', $this->base.'/composer.lock') === $packageLockHash;
    }

    /** Files an update never touches: server config, user data, caches, and vendor/ when unchanged. */
    private function skipped(string $rel, bool $sameVendor): bool
    {
        $name = basename($rel);

        return $name === '.htaccess' || $name === '.env' || str_starts_with($name, '.env.') || $name === '.user.ini' || $name === 'php.ini'
            || (bool) preg_match('#^(storage|bootstrap/cache|node_modules|tests|\.git|\.github)/#', $rel)
            || ($sameVendor && str_starts_with($rel, 'vendor/'));
    }

    /** @return array<int, array{from: string, to: string, key: string}> Only new or changed files. */
    private function plan(string $package): array
    {
        $sameVendor = $this->sameVendor(hash_file('sha256', $package.'/composer.lock'));
        $mirror = $this->publicMirror();
        $plan = [];
        foreach ($this->walk($package) as $from) {
            $rel = substr($this->normalize($from), strlen($this->normalize($package)) + 1);
            if ($this->skipped($rel, $sameVendor)) {
                continue;
            }
            $targets = [$rel => $this->base.'/'.$rel];
            if ($mirror && str_starts_with($rel, 'public/') && $rel !== 'public/index.php') {
                $targets['@public/'.substr($rel, 7)] = $mirror.'/'.substr($rel, 7);
            }
            foreach ($targets as $key => $to) {
                if (! is_file($to) || hash_file('sha256', $to) !== hash_file('sha256', $from)) {
                    $plan[] = ['from' => $from, 'to' => $to, 'key' => $key];
                }
            }
        }

        return $plan;
    }

    private function assertWritable(array $plan): void
    {
        foreach ($plan as $item) {
            $dir = dirname($item['to']);
            while (! is_dir($dir) && dirname($dir) !== $dir) {
                $dir = dirname($dir);
            }
            if ((is_file($item['to']) && ! is_writable($item['to'])) || ! is_writable($dir)) {
                throw new UpdateFailed('not_writable');
            }
        }
    }

    private function backup(array $plan, string $to): array
    {
        $id = now()->format('Ymd-His').'-'.bin2hex(random_bytes(4));
        $dir = $this->workDir().'/backups/'.$id;
        mkdir($dir.'/files', 0755, true);
        $files = [];
        foreach ($plan as $item) {
            $existed = is_file($item['to']);
            if ($existed) {
                $this->copyFile($item['to'], $dir.'/files/'.md5($item['key']));
            }
            $files[] = ['key' => $item['key'], 'path' => $item['to'], 'existed' => $existed];
        }
        $manifest = ['id' => $id, 'from' => $this->version(), 'to' => $to, 'created_at' => now()->toIso8601String(), 'files' => $files];
        file_put_contents($dir.'/manifest.json', json_encode($manifest));

        return $manifest;
    }

    private function restore(array $manifest): void
    {
        $dir = $this->workDir().'/backups/'.$manifest['id'];
        foreach ($manifest['files'] as $file) {
            if ($file['existed']) {
                $this->copyFile($dir.'/files/'.md5($file['key']), $file['path']);
            } elseif (is_file($file['path'])) {
                @unlink($file['path']);
            }
        }
    }

    private function pruneBackups(): void
    {
        foreach (array_slice($this->backups(), self::KEEP_BACKUPS) as $old) {
            $this->deleteTree($this->workDir().'/backups/'.$old['id']);
        }
    }

    /** A separate document root (e.g. cPanel public_html) that also needs the public files. */
    private function publicMirror(): ?string
    {
        $configured = config('platform.updates.public_mirror');
        $candidate = is_string($configured) && $configured !== '' ? $configured : ($_SERVER['DOCUMENT_ROOT'] ?? null);
        $real = is_string($candidate) && $candidate !== '' ? realpath($candidate) : false;
        $own = realpath($this->base.'/public');
        if (! $real || ($own && $this->normalize($real) === $this->normalize($own)) || ! is_file($real.'/index.php')) {
            return null;
        }

        return $this->normalize($real);
    }

    private function copyFile(string $from, string $to): void
    {
        if (! is_dir(dirname($to)) && ! mkdir(dirname($to), 0755, true) && ! is_dir(dirname($to))) {
            throw new RuntimeException('Cannot create directory for '.$to);
        }
        $tmp = $to.'.update-tmp';
        if (! copy($from, $tmp) || ! rename($tmp, $to)) {
            @unlink($tmp);
            throw new RuntimeException('Cannot write '.$to);
        }
    }

    /** @return iterable<string> */
    private function walk(string $dir, bool $includeDirs = false): iterable
    {
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($items as $item) {
            if ($includeDirs || $item->isFile() || $item->isLink()) {
                yield $item->getPathname();
            }
        }
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    /** File lock: the OS releases it if PHP dies (time limit, crash), so a failed attempt never blocks the next one. */
    private function locked(callable $callback): mixed
    {
        $dir = $this->workDir();
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $handle = fopen($dir.'/update.lock', 'c');
        if (! $handle || ! flock($handle, LOCK_EX | LOCK_NB)) {
            $handle && fclose($handle);
            throw new UpdateFailed('busy');
        }
        @set_time_limit(0);
        ignore_user_abort(true);
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function discardDownload(): void
    {
        @unlink($this->workDir().'/download.tar.gz');
        @unlink($this->workDir().'/download.json');
    }

    private function workDir(): string
    {
        return $this->base.'/storage/app/private/updates';
    }

    private function clearCaches(): void
    {
        try {
            Artisan::call('optimize:clear');
        } catch (Throwable) {
            // The update is already applied; stale caches clear on their own or via the terminal.
        }
        $this->resetOpcache();
    }

    private function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    private function audit(string $event, array $metadata): void
    {
        AuditLog::create(['user_id' => $metadata['by'] ?? null, 'event' => $event, 'metadata' => array_intersect_key($metadata, array_flip(['from', 'to', 'backup', 'files']))]);
    }

    private function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
