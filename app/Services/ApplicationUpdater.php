<?php

namespace App\Services;

use App\Models\AuditLog;
use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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

    /** Download, verify and copy the latest release. Finish with finalize() on the next request. */
    public function install(?int $userId = null): string
    {
        if ($this->pending()) {
            throw new UpdateFailed('pending');
        }
        $lock = Cache::lock('application-update', 900);
        if (! $lock->get()) {
            throw new UpdateFailed('busy');
        }
        @set_time_limit(0);
        ignore_user_abort(true);
        $work = $this->workDir();
        if (! is_dir($work)) {
            mkdir($work, 0755, true);
        }
        $staging = $work.'/staging';
        $archive = $work.'/download.tar.gz';
        try {
            $release = $this->release();
            if (! version_compare($release['version'], $this->version(), '>')) {
                throw new UpdateFailed('not_newer');
            }
            $this->deleteTree($staging);
            @unlink($archive);
            $this->download($release, $archive);
            $package = $this->extract($archive, $staging, $release['version']);
            $plan = $this->plan($package);
            $this->assertWritable($plan);
            $backup = $this->backup($plan, $release['version']);
            try {
                foreach ($plan as $item) {
                    $this->copyFile($item['from'], $item['to']);
                }
            } catch (Throwable $e) {
                $this->restore($backup);
                throw new UpdateFailed('copy_failed', $e);
            }
            file_put_contents($work.'/pending.json', json_encode([
                'from' => $backup['from'], 'to' => $release['version'], 'backup' => $backup['id'],
                'files' => count($plan), 'user_id' => $userId, 'at' => now()->toIso8601String(),
            ]));
            $this->resetOpcache();

            return $release['version'];
        } finally {
            $this->deleteTree($staging);
            @unlink($archive);
            $lock->release();
        }
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
        $lock = Cache::lock('application-update', 900);
        if (! $lock->get()) {
            throw new UpdateFailed('busy');
        }
        try {
            $this->restore($manifest);
            @unlink($this->workDir().'/pending.json');
            $this->resetOpcache();
            $this->clearCaches();
            $this->audit('application.rolled_back', ['from' => $manifest['to'], 'to' => $manifest['from'], 'backup' => $id, 'by' => $userId]);
        } finally {
            $lock->release();
        }

        return $manifest;
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

    private function extract(string $archive, string $staging, string $version): string
    {
        try {
            mkdir($staging, 0755, true);
            (new PharData($archive))->extractTo($staging, null, true);
        } catch (Throwable $e) {
            throw new UpdateFailed('invalid_package', $e);
        }
        $root = realpath($staging);
        foreach ($this->walk($staging, true) as $path) {
            $real = realpath($path);
            if (is_link($path) || $real === false || ! str_starts_with($this->normalize($real), $this->normalize($root).'/')) {
                throw new UpdateFailed('invalid_package');
            }
        }
        $package = $staging.'/package';
        foreach (['VERSION', 'artisan', 'composer.lock', 'vendor/autoload.php', 'public/build/manifest.json', 'app/Services/ApplicationUpdater.php'] as $required) {
            if (! is_file($package.'/'.$required)) {
                throw new UpdateFailed('invalid_package');
            }
        }
        if (trim((string) file_get_contents($package.'/VERSION')) !== $version) {
            throw new UpdateFailed('invalid_package');
        }

        return $package;
    }

    /** @return array<int, array{from: string, to: string, key: string}> Only new or changed files. */
    private function plan(string $package): array
    {
        $sameVendor = is_file($this->base.'/composer.lock')
            && hash_file('sha256', $this->base.'/composer.lock') === hash_file('sha256', $package.'/composer.lock')
            && is_file($this->base.'/vendor/autoload.php');
        $mirror = $this->publicMirror();
        $plan = [];
        foreach ($this->walk($package) as $from) {
            $rel = substr($this->normalize($from), strlen($this->normalize($package)) + 1);
            $name = basename($rel);
            if ($name === '.htaccess' || $name === '.env' || str_starts_with($name, '.env.') || $name === '.user.ini' || $name === 'php.ini'
                || preg_match('#^(storage|bootstrap/cache|node_modules|tests|\.git|\.github)/#', $rel)
                || ($sameVendor && str_starts_with($rel, 'vendor/'))) {
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
