<?php
namespace Tests\Feature;
use App\Models\{User,Role};
use App\Services\ApplicationUpdates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,Cache};
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;
class ApplicationUpdatesTest extends TestCase
{
    use RefreshDatabase;
    private function service(): ApplicationUpdates { config(['platform.updates.token'=>'synthetic-test-token']);return app(ApplicationUpdates::class); }
    private function release(string $tag): array { return ['tag_name'=>$tag,'draft'=>false,'prerelease'=>false,'html_url'=>'https://malicious.example/ignored']; }
    public function test_versions_are_compared_and_only_a_safe_release_link_is_returned(): void
    {
        $service=$this->service();
        Http::fake(['api.github.com/*'=>Http::sequence()->push($this->release('v99.0.0'))->push($this->release('v'.$service->version()))]);
        $result=$service->check();
        $this->assertSame('available',$service->displayStatus($result));
        $this->assertSame('https://github.com/OmanGhost/Blackmail/releases/tag/v99.0.0',$result['url']);
        $this->assertStringNotContainsString('synthetic-test-token',json_encode($result));
        $service->check();$service->check(true);Http::assertSentCount(1);
        Http::assertSent(fn($request)=>$request->url()==='https://api.github.com/repos/OmanGhost/Blackmail/releases/latest' && $request->hasHeader('Authorization','Bearer synthetic-test-token'));
        $this->travel(61)->seconds();
        $this->assertSame('current',$service->displayStatus($service->check(true)));
    }
    public function test_private_access_errors_do_not_claim_the_application_is_current(): void
    {
        $service=$this->service();Http::fake(['api.github.com/*'=>Http::response([],404)]);
        $result=$service->check();$this->assertSame('access_denied',$service->displayStatus($result));$this->assertNull($result['url']);
        Cache::flush();config(['platform.updates.token'=>null]);Http::fake();
        $this->assertSame('not_configured',$service->displayStatus($service->check()));Http::assertNothingSent();
    }
    public function test_unstable_or_invalid_release_payloads_are_rejected(): void
    {
        $service=$this->service();
        foreach(['javascript:alert(1)','v2.0.0-beta','../../escape'] as $tag) {
            Cache::flush();Http::fake(['api.github.com/*'=>Http::response($this->release($tag))]);
            $result=$service->check();$this->assertSame('invalid_response',$result['status']);$this->assertNull($result['url']);
        }
        Cache::flush();Http::fake(['api.github.com/*'=>Http::response(['tag_name'=>'v99.0.0','draft'=>false,'prerelease'=>true])]);
        $this->assertSame('invalid_response',$service->check()['status']);
    }
    public function test_network_failure_is_a_safe_unavailable_state(): void
    {
        $service=$this->service();
        Http::fake(fn()=>throw new \Illuminate\Http\Client\ConnectionException('Synthetic network failure'));
        $result=$service->check();
        $this->assertSame('unavailable',$result['status']);
        $this->assertNull($result['url']);
        $this->assertStringNotContainsString('Synthetic',json_encode($result));
    }
    public function test_footer_and_update_check_are_available_only_to_authorized_staff(): void
    {
        Http::fake();config(['platform.updates.token'=>null]);
        $member=User::factory()->create(['is_active'=>true]);$this->actingAs($member,'admin');
        Livewire::test(\App\Livewire\SystemVersion::class)->assertSee('v'.app(ApplicationUpdates::class)->version())->call('check')->assertForbidden();
        $this->assertFalse(\App\Filament\Pages\Updates::canAccess());
        $admin=User::factory()->create(['is_active'=>true]);$admin->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        $this->actingAs($admin,'admin');Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(\App\Livewire\SystemVersion::class)->call('check')->assertSee('يلزم إعداد الوصول للمستودع الخاص');
        Livewire::test(\App\Filament\Pages\Updates::class)->assertOk()->assertSee('GITHUB_UPDATE_TOKEN');
        $this->withoutMiddleware(\Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class);
        $this->get($this->panelPath('/users'))->assertOk()->assertSee('نسخة التطبيق')->assertSee('v'.app(ApplicationUpdates::class)->version());
    }
}
