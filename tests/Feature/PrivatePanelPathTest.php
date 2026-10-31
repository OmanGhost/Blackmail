<?php
namespace Tests\Feature;
use App\Models\{User,Role};
use App\Support\PanelAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PrivatePanelPathTest extends TestCase
{
    use RefreshDatabase;
    public function test_legacy_admin_paths_redirect_home_without_revealing_panel_path(): void
    {
        foreach(['/admin','/admin/login','/admin/users/1','/ADMIN/login'] as $path) {
            $this->get($path)->assertRedirect(route('home'));
            $this->post($path)->assertRedirect(route('home'));
        }
    }
    public function test_configured_panel_login_and_guard_redirects_use_new_path(): void
    {
        $url=route('filament.admin.auth.login');
        $this->assertStringNotContainsString('/admin/',$url);
        $this->get($url)->assertOk();
        $this->get($this->panelPath('/users'))->assertRedirect($url);
        $admin=User::factory()->create(['is_active'=>true]);
        $admin->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        $this->actingAs($admin,'admin');
        $admin->update(['is_active'=>false]);
        $this->get($this->panelPath('/users'))->assertRedirect($url);
    }
    public function test_invalid_panel_configuration_fails_closed(): void
    {
        config(['platform.admin_path'=>'admin']);
        $this->expectException(\InvalidArgumentException::class);
        PanelAccess::path();
    }
}
