<?php
namespace Tests\Feature;
use App\Models\{User,Role,Country,ReportingAuthority,HomepageSection};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth,Gate,Password};
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;
    private function userWithRole(string $role): User
    {
        $user=User::factory()->create(['is_active'=>true]);
        $user->roles()->attach(Role::firstOrCreate(['name'=>$role],['label_ar'=>$role]));
        return $user;
    }
    private function country(): Country { return Country::create(['code'=>'ZZ','name_ar'=>'اختبار','name_en'=>'Test','slug'=>'test','verification_status'=>'unverified','is_published'=>false]); }

    public function test_password_only_public_login_cannot_authenticate_panel_even_with_mfa_enrolled(): void
    {
        $admin=$this->userWithRole('super-admin');$admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->withMiddleware(\App\Http\Middleware\VerifyHuman::class);
        $html=$this->get('/login')->getContent();
        preg_match('/name="human_token" value="([^"]+)"/',$html,$token);
        preg_match('/<bdi dir="ltr">(\d+) \+ (\d+)<\/bdi>/',$html,$numbers);
        $this->post('/login',['email'=>$admin->email,'password'=>'password','remember'=>1,'human_token'=>$token[1],'human_answer'=>(string)($numbers[1]+$numbers[2])])->assertRedirect();
        $this->assertAuthenticatedAs($admin,'web');$this->assertGuest('admin');
        foreach([$this->panelPath(''),$this->panelPath('/consultation-requests'),$this->panelPath('/secure-evidence/1')] as $url) $this->get($url)->assertRedirect($this->panelPath('/login'));
    }

    public function test_panel_login_requires_otp_and_correct_otp_preserves_legitimate_admin_access(): void
    {
        $admin=$this->userWithRole('super-admin');$admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $login=Livewire::test(\Filament\Auth\Pages\Login::class)->fillForm(['email'=>$admin->email,'password'=>'password'])->call('authenticate');
        $this->assertGuest('admin');
        $login->set('data.multiFactor.app.code','000000')->call('authenticate')->assertHasErrors();
        $this->assertGuest('admin');
        $code=\Filament\Auth\MultiFactor\App\AppAuthentication::make()->getCurrentCode($admin);
        $login->set('data.multiFactor.app.code',$code)->call('authenticate')->assertHasNoErrors();
        $this->assertAuthenticatedAs($admin,'admin');
        $this->get($this->panelPath('/consultation-requests'))->assertOk();
        Livewire::test(\App\Filament\Auth\EditProfile::class)
            ->fillForm(['password'=>'NewAdminPassword123','passwordConfirmation'=>'NewAdminPassword123','currentPassword'=>'password'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame((int)$admin->fresh()->auth_version,(int)session('auth_versions.admin'));
        $this->get($this->panelPath('/consultation-requests'))->assertOk();
        $this->post('/quick-exit')->assertRedirect(route('neutral'));
        $this->assertGuest('web');$this->assertGuest('admin');
    }

    public function test_suspension_reset_and_legacy_sessions_revoke_public_case_access(): void
    {
        $user=$this->userWithRole('member');
        $this->post('/login',['email'=>$user->email,'password'=>'password']);
        $this->get('/account')->assertOk();
        $oldVersion=session('auth_versions.web');
        $token=Password::broker()->createToken($user);
        // Simulate reset in another browser: change credentials through the broker, leaving this session unchanged.
        Password::reset(['email'=>$user->email,'token'=>$token,'password'=>'ChangedPassword123','password_confirmation'=>'ChangedPassword123'],fn($record,$password)=>$record->update(['password'=>$password]));
        $this->assertGreaterThan($oldVersion,$user->fresh()->auth_version);
        $this->get('/account')->assertRedirect(route('login'));
        $this->post('/login',['email'=>$user->email,'password'=>'ChangedPassword123']);
        $this->get('/account')->assertOk();
        $user->fresh()->update(['is_active'=>false]);
        $this->get('/account')->assertRedirect(route('login'));
        $user->fresh()->update(['is_active'=>true]);
        $this->post('/login',['email'=>$user->email,'password'=>'ChangedPassword123']);
        session()->forget('auth_versions.web');
        $this->get('/account')->assertRedirect(route('login'));
    }

    public function test_password_change_preserves_initiator_but_invalidates_old_version_and_remember_token(): void
    {
        $user=$this->userWithRole('member');
        $this->post('/login',['email'=>$user->email,'password'=>'password','remember'=>1]);
        $version=session('auth_versions.web');$remember=$user->fresh()->remember_token;
        $this->put('/account/password',['current_password'=>'password','password'=>'ChangedPassword123','password_confirmation'=>'ChangedPassword123'])->assertSessionHasNoErrors();
        $this->get('/account')->assertOk();
        $this->assertNotSame($remember,$user->fresh()->remember_token);
        session()->put('auth_versions.web',$version);
        $this->get('/account')->assertRedirect(route('login'));
    }

    public function test_directory_role_matrix_and_server_recorded_review(): void
    {
        $country=$this->country();
        foreach(['member','support-agent','security-consultant','content-manager'] as $role) {
            $user=$this->userWithRole($role);$this->actingAs($user,'admin');
            $this->assertFalse(Gate::allows('update',$country));
            $this->assertFalse(Gate::allows('create',ReportingAuthority::class));
            $this->assertFalse(Gate::allows('deleteAny',Country::class));
        }
        $reviewer=$this->userWithRole('legal-reviewer');$this->actingAs($reviewer,'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor=Livewire::test(\App\Filament\Resources\Countries\Pages\EditCountry::class,['record'=>$country->id]);
        $editor->fillForm(['verification_status'=>'verified','is_published'=>true])->call('save')->assertHasNoFormErrors();
        $this->assertSame($reviewer->id,$country->fresh()->verified_by);
        $this->assertSame(now()->toDateString(),$country->fresh()->last_verified_at->toDateString());
        $this->get('/countries/test')->assertOk();
        $this->actingAs($reviewer,'admin');
        $editor->fillForm(['emergency_information_ar'=>'Changed contact'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('in_review',$country->fresh()->verification_status);
        $this->assertFalse($country->fresh()->is_published);
        $this->get('/countries/test')->assertNotFound();
        $this->actingAs($reviewer,'admin');
        $editor->fillForm(['verification_status'=>'verified','is_published'=>true])->call('save')->assertHasNoFormErrors();
        $this->get('/countries/test')->assertOk()->assertSee('Changed contact');
        $this->actingAs($reviewer,'admin');
        Livewire::test(\App\Filament\Resources\ReportingAuthorities\Pages\CreateReportingAuthority::class)
            ->fillForm(['country_id'=>$country->id,'name_ar'=>'جهة موثقة','official_url'=>'https://example.test','verification_status'=>'verified','is_published'=>true])->call('create')->assertHasNoFormErrors();
        $this->assertSame($reviewer->id,ReportingAuthority::firstOrFail()->verified_by);
    }

    public function test_unsafe_homepage_urls_are_rejected_and_legacy_values_are_neutralized(): void
    {
        $user=$this->userWithRole('content-manager');$this->actingAs($user,'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $section=HomepageSection::create(['key'=>'hero','name_ar'=>'Hero','heading_ar'=>'Test','primary_button_text_ar'=>'Continue','is_enabled'=>true]);
        $editor=Livewire::test(\App\Filament\Resources\HomepageSections\Pages\EditHomepageSection::class,['record'=>$section->id]);
        foreach(['javascript://example.test/%0Aalert(1)','data:text/html,test','//evil.test','/\\evil.test','https://example.test/%0aevil'] as $url) {
            $editor->fillForm(['primary_button_url'=>$url])->call('save')->assertHasFormErrors(['primary_button_url']);
            $this->assertFalse(\App\Support\SafeNavigation::allows($url));
        }
        $editor->fillForm(['primary_button_url'=>'/consultation','secondary_button_url'=>'https://example.test/help'])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertOk()->assertSee(route('consultation'));
        $section->update(['primary_button_url'=>'javascript://example.test/%0Aalert(1)']);
        $this->get('/')->assertOk()->assertDontSee('href="javascript:',false);
    }
}
