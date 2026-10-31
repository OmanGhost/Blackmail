<?php
namespace Tests\Feature;

use App\Filament\Widgets\{LatestRequests,PlatformStats,RequestsByStatusChart,RequestsTrendChart};
use App\Models\{Role,User};
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $role): User
    {
        $user=User::factory()->create(['is_active'=>true]);
        $user->roles()->attach(Role::firstOrCreate(['name'=>$role],['label_ar'=>$role]));
        $this->actingAs($user,'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->withoutMiddleware(\Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class);
        return $user;
    }

    private function seedRequest(string $status, string $urgency='normal'): void
    {
        static $i=0; $i++;
        DB::table('consultation_requests')->insert(['public_id'=>'dash-'.$i,'tracking_hash'=>hash('sha256','dash-'.$i),'service_type'=>'support',
            'category'=>'blackmail','urgency'=>$urgency,'status'=>$status,'preferred_contact_method'=>'email','contact_details_encrypted'=>'private-contact',
            'description_encrypted'=>'private-description','consented_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_super_admin_dashboard_shows_attention_panel_quick_actions_and_request_widgets(): void
    {
        $this->signIn('super-admin');
        $this->seedRequest('new','critical');
        $this->seedRequest('completed');

        $this->get($this->panelPath(''))->assertOk()
            ->assertSee('admin-attention',false)->assertSee('عاجلة أو حرجة')
            ->assertSee('admin-quick',false)->assertSee('منشور جديد')
            ->assertDontSee('private-contact');

        $this->assertTrue(RequestsTrendChart::canView());
        $this->assertSame(2,array_sum(RequestsTrendChart::dailyCounts(14)));
        Livewire::test(PlatformStats::class)->assertOk()->assertSee('طلبات جديدة');
        Livewire::test(RequestsByStatusChart::class)->assertOk();
        Livewire::test(LatestRequests::class)->assertOk()->assertSee('ابتزاز إلكتروني')->assertSee('حرج')->assertDontSee('private-description');
    }

    public function test_content_manager_does_not_see_request_data_on_dashboard(): void
    {
        $this->signIn('content-manager');
        $this->seedRequest('new','critical');

        $this->get($this->panelPath(''))->assertOk()
            ->assertDontSee('admin-attention',false)->assertDontSee('لوحة العمليات')->assertSee('منشور جديد');
        $this->assertFalse(RequestsTrendChart::canView());
        $this->assertFalse(RequestsByStatusChart::canView());
        $this->assertFalse(LatestRequests::canView());
        Livewire::test(PlatformStats::class)->assertOk()->assertDontSee('طلبات جديدة')->assertSee('المنشورات المنشورة');
    }

    public function test_homepage_keeps_entry_paths_and_marks_active_navigation(): void
    {
        $this->get('/')->assertOk()->assertSee('urgent-attention',false)->assertSee('aria-current="page"',false)
            ->assertSee(route('blackmail.help'))->assertSee(route('consultation.track'));
    }
}
