<?php

namespace Tests\Feature;

use App\Filament\Pages\Updates;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\ApplicationUpdater;
use App\Services\UpdateFailed;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Phar;
use PharData;
use Tests\TestCase;

class ApplicationUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private string $root;
    private string $base;
    private string $mirror;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = str_replace('\\', '/', sys_get_temp_dir()).'/updater-test-'.Str::random(8);
        $this->base = $this->root.'/app';
        $this->mirror = $this->root.'/public_html';
        $this->writeTree($this->base, [
            'VERSION' => "1.0.0\n", 'composer.lock' => 'lock-1', 'vendor/autoload.php' => 'old-vendor', '.env' => 'APP_KEY=secret',
            'app/Kept.php' => 'old kept', 'app/Changed.php' => 'old changed', 'public/index.php' => 'index', 'public/.htaccess' => 'server rules',
            'storage/app/data.txt' => 'user data',
        ]);
        $this->writeTree($this->mirror, ['index.php' => 'cpanel index', '.htaccess' => 'cpanel rules']);
        config(['platform.updates.token' => 'synthetic-token', 'platform.updates.public_mirror' => $this->mirror]);
        $this->app->instance(ApplicationUpdater::class, new ApplicationUpdater($this->base));
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);
        parent::tearDown();
    }

    private function writeTree(string $dir, array $files): void
    {
        foreach ($files as $path => $content) {
            @mkdir(dirname($dir.'/'.$path), 0755, true);
            file_put_contents($dir.'/'.$path, $content);
        }
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) return;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    /** Builds a release package like the GitHub workflow does and returns its bytes. */
    /** Lists every file with its SHA-256, as the GitHub workflow does. $tamper files get a wrong hash. */
    private function writeManifest(string $dir, array $tamper = []): void
    {
        $lines = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            $lines[$rel] = (in_array($rel, $tamper, true) ? str_repeat('a', 64) : hash_file('sha256', $file->getPathname())).'  '.$rel;
        }
        ksort($lines);
        file_put_contents($dir.'/.package-manifest', implode("\n", $lines)."\n");
    }

    private function package(string $version, array $overrides = [], array $tamper = []): string
    {
        $src = $this->root.'/build-'.Str::random(6);
        $this->writeTree($src.'/package', $overrides + [
            'VERSION' => $version."\n", 'artisan' => 'artisan', 'composer.lock' => 'lock-1', 'vendor/autoload.php' => 'new-vendor',
            'public/build/manifest.json' => '{}', 'app/Services/ApplicationUpdater.php' => 'updater', 'app/Kept.php' => 'old kept',
            'app/Changed.php' => 'new changed', 'app/Added.php' => 'added', 'public/sw.js' => 'new sw', 'public/index.php' => 'new index',
            'public/.htaccess' => 'release rules', '.env' => 'APP_KEY=attacker', 'storage/app/data.txt' => 'overwritten',
        ]);
        $this->writeManifest($src.'/package', $tamper);
        $tar = $src.'/package.tar';
        (new PharData($tar))->buildFromDirectory($src);
        (new PharData($tar))->compress(Phar::GZ);
        $bytes = file_get_contents($tar.'.gz');
        unset($tar);

        return $bytes;
    }

    private function fakeRelease(string $tag, ?string $package, ?string $checksum = null): void
    {
        $assets = $package === null ? [] : [
            ['name' => 'blackmail-'.$tag.'.tar.gz', 'url' => 'https://api.github.com/repos/OmanGhost/Blackmail/releases/assets/1'],
            ['name' => 'blackmail-'.$tag.'.tar.gz.sha256', 'url' => 'https://api.github.com/repos/OmanGhost/Blackmail/releases/assets/2'],
        ];
        Http::fake([
            'api.github.com/repos/OmanGhost/Blackmail/releases/latest' => Http::response(['tag_name' => $tag, 'draft' => false, 'prerelease' => false, 'assets' => $assets]),
            'api.github.com/repos/OmanGhost/Blackmail/releases/assets/1' => Http::response($package ?? ''),
            'api.github.com/repos/OmanGhost/Blackmail/releases/assets/2' => Http::response(($checksum ?? hash('sha256', (string) $package)).'  blackmail-'.$tag.'.tar.gz'),
        ]);
    }

    private function updater(): ApplicationUpdater
    {
        return app(ApplicationUpdater::class);
    }

    public function test_install_finalize_and_rollback(): void
    {
        $this->fakeRelease('v1.1.0', $this->package('1.1.0'));
        $admin = User::factory()->create();

        $this->assertSame('1.1.0', $this->updater()->install($admin->id));

        $this->assertSame("1.1.0\n", file_get_contents($this->base.'/VERSION'));
        $this->assertSame('new changed', file_get_contents($this->base.'/app/Changed.php'));
        $this->assertSame('added', file_get_contents($this->base.'/app/Added.php'));
        // Server-specific files and data are never touched; vendor is skipped when composer.lock is unchanged.
        $this->assertSame('APP_KEY=secret', file_get_contents($this->base.'/.env'));
        $this->assertSame('server rules', file_get_contents($this->base.'/public/.htaccess'));
        $this->assertSame('user data', file_get_contents($this->base.'/storage/app/data.txt'));
        $this->assertSame('old-vendor', file_get_contents($this->base.'/vendor/autoload.php'));
        // A separate public_html gets public files, but keeps its own index.php and .htaccess.
        $this->assertSame('new sw', file_get_contents($this->mirror.'/sw.js'));
        $this->assertSame('cpanel index', file_get_contents($this->mirror.'/index.php'));
        $this->assertSame('cpanel rules', file_get_contents($this->mirror.'/.htaccess'));
        $this->assertSame('1.1.0', $this->updater()->pending()['to']);
        $this->assertDirectoryDoesNotExist($this->base.'/storage/app/private/updates/staging');

        try { $this->updater()->install(); $this->fail('A second install must wait for the first to finish.'); }
        catch (UpdateFailed $e) { $this->assertSame('pending', $e->reason); }

        $this->assertSame('1.1.0', $this->updater()->finalize()['to']);
        $this->assertNull($this->updater()->pending());
        $this->assertTrue(AuditLog::where('event', 'application.updated')->where('user_id', $admin->id)->exists());

        $backup = $this->updater()->backups()[0];
        $this->assertSame(['1.0.0', '1.1.0'], [$backup['from'], $backup['to']]);
        $this->updater()->rollback($backup['id'], $admin->id);
        $this->assertSame("1.0.0\n", file_get_contents($this->base.'/VERSION'));
        $this->assertSame('old changed', file_get_contents($this->base.'/app/Changed.php'));
        $this->assertFileDoesNotExist($this->base.'/app/Added.php');
        $this->assertFileDoesNotExist($this->mirror.'/sw.js');
        $this->assertTrue(AuditLog::where('event', 'application.rolled_back')->exists());
    }

    public function test_changed_dependencies_replace_vendor(): void
    {
        $this->fakeRelease('v1.1.0', $this->package('1.1.0', ['composer.lock' => 'lock-2']));
        $this->updater()->install();
        $this->assertSame('new-vendor', file_get_contents($this->base.'/vendor/autoload.php'));
        $this->assertSame('lock-2', file_get_contents($this->base.'/composer.lock'));
    }

    public static function badPackages(): array
    {
        return [['checksum_failed'], ['package_missing'], ['invalid_package'], ['tampered'], ['not_newer']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badPackages')]
    public function test_bad_or_missing_packages_change_nothing(string $reason): void
    {
        match ($reason) {
            'checksum_failed' => $this->fakeRelease('v1.1.0', $this->package('1.1.0'), str_repeat('0', 64)),
            'package_missing' => $this->fakeRelease('v1.1.0', null),
            'invalid_package' => $this->fakeRelease('v1.1.0', $this->package('9.9.9')),
            'tampered' => $this->fakeRelease('v1.1.0', $this->package('1.1.0', [], ['app/Changed.php'])),
            'not_newer' => $this->fakeRelease('v1.0.0', $this->package('1.0.0')),
        };
        try { $this->updater()->install(); $this->fail('Expected '.$reason); }
        catch (UpdateFailed $e) { $this->assertSame($reason === 'tampered' ? 'invalid_package' : $reason, $e->reason); }
        $this->assertSame("1.0.0
", file_get_contents($this->base.'/VERSION'));
        $this->assertSame('old changed', file_get_contents($this->base.'/app/Changed.php'));
        $this->assertNull($this->updater()->pending());
        $this->assertSame([], $this->updater()->backups());
        $this->assertDirectoryDoesNotExist($this->base.'/storage/app/private/updates/staging');
    }

    public function test_admin_installs_and_rolls_back_from_the_updates_page_with_password(): void
    {
        $this->fakeRelease('v99.0.0', $this->package('99.0.0'));
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::create(['name' => 'super-admin', 'label_ar' => 'مدير']));
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Updates::class)->assertActionVisible('install')->assertActionHidden('rollback')
            ->callAction('install', ['password' => 'wrong-password'])->assertHasActionErrors(['password']);
        $this->assertNull($this->updater()->pending());
        // Step 1: the button only downloads and verifies, so the request stays short.
        Livewire::test(Updates::class)->callAction('install', ['password' => 'password'])->assertHasNoActionErrors()->assertRedirect(Updates::getUrl());
        $this->assertSame('99.0.0', $this->updater()->downloaded()['version']);
        $this->assertNull($this->updater()->pending());
        $this->assertSame("1.0.0\n", file_get_contents($this->base.'/VERSION'));

        // Step 2: the next page load copies the files.
        Livewire::test(Updates::class)->assertRedirect(Updates::getUrl());
        $this->assertNull($this->updater()->downloaded());
        $this->assertSame('99.0.0', $this->updater()->pending()['to']);

        // Step 3: the load after that runs migrations with the new code.
        Livewire::test(Updates::class)->assertNotified(__('updates.installed', ['version' => '99.0.0']))->assertActionHidden('install')->assertActionVisible('rollback');
        $this->assertNull($this->updater()->pending());

        $backup = $this->updater()->backups()[0]['id'];
        Livewire::test(Updates::class)->callAction('rollback', ['backup' => $backup, 'password' => 'password'])->assertHasNoActionErrors();
        $this->assertSame("1.0.0\n", file_get_contents($this->base.'/VERSION'));
    }

    public function test_a_crashed_attempt_never_blocks_the_next_one(): void
    {
        $this->fakeRelease('v1.1.0', $this->package('1.1.0'));
        $lockFile = $this->base.'/storage/app/private/updates/update.lock';
        @mkdir(dirname($lockFile), 0755, true);

        // While another process holds the lock, a second update is refused...
        $held = fopen($lockFile, 'c');
        flock($held, LOCK_EX);
        try { $this->updater()->prepare(); $this->fail('Expected busy'); }
        catch (UpdateFailed $e) { $this->assertSame('busy', $e->reason); }

        // ...but as soon as that process is gone (here: the handle is closed), the lock is free again.
        fclose($held);
        $this->assertSame('1.1.0', $this->updater()->prepare());
    }

    public function test_apply_gives_up_after_two_interrupted_attempts(): void
    {
        $this->fakeRelease('v1.1.0', $this->package('1.1.0'));
        $this->updater()->prepare();
        $state = $this->base.'/storage/app/private/updates/download.json';
        // Simulate two requests killed by the host's time limit mid-apply.
        file_put_contents($state, json_encode(['version' => '1.1.0', 'attempts' => 2]));

        try { $this->updater()->apply(); $this->fail('Expected timed_out'); }
        catch (UpdateFailed $e) { $this->assertSame('timed_out', $e->reason); }
        $this->assertNull($this->updater()->downloaded());
        $this->assertSame("1.0.0\n", file_get_contents($this->base.'/VERSION'));
    }

    public function test_terminal_command_finishes_a_pending_update_and_reports_up_to_date(): void
    {
        $this->fakeRelease('v1.1.0', $this->package('1.1.0'));
        $this->updater()->install();
        $this->artisan('app:update')->expectsOutputToContain('1.1.0')->assertExitCode(0);
        $this->assertNull($this->updater()->pending());

        $this->artisan('app:update')->expectsOutputToContain(__('updates.errors.not_newer'))->assertExitCode(0);
    }

    public function test_install_is_hidden_until_the_package_is_ready_and_from_other_staff(): void
    {
        $this->fakeRelease('v99.0.0', null);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::create(['name' => 'super-admin', 'label_ar' => 'مدير']));
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Updates::class)->assertActionHidden('install')->assertSee(__('updates.package_pending'));

        $agent = User::factory()->create(['is_active' => true]);
        $agent->roles()->attach(Role::create(['name' => 'support-agent', 'label_ar' => 'دعم']));
        $this->actingAs($agent, 'admin');
        $this->assertFalse(Updates::canAccess());
    }
}
