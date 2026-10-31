<?php
namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\RequestUpdate;
use App\Models\User;
use App\Models\Role;
use App\Models\PrivacyRequest;
use App\Services\PlatformMail;
use App\Services\PrivacyErasure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupportWorkflowToolsTest extends TestCase
{
    use RefreshDatabase;

    private function submit(): ConsultationRequest
    {
        $this->post('/consultation',['category'=>'blackmail','preferred_contact_method'=>'email','contact_details'=>'requester@example.test','description'=>'A sufficiently detailed description of the incident.','consent'=>'1'])->assertRedirect();
        return ConsultationRequest::latest('id')->firstOrFail();
    }

    public function test_updates_unread_timeline_tasks_and_private_notes_are_integrated(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);
        $case = $this->submit();
        $case->internalNotes()->create(['body'=>'INTERNAL PRIVATE NOTE']);
        $case->messages()->create(['sender'=>'staff','body'=>'PUBLIC SUPPORT REPLY']);
        $task = $case->tasks()->create(['title'=>'Secure your account']);
        $case->update(['status'=>'in_progress']);
        $this->assertSame(3,$case->updates()->whereNull('read_at')->count());
        $this->get('/account')->assertOk()->assertSee(__('workflow.update_message'));
        $this->get('/consultation/track')->assertOk()->assertDontSee('Secure your account')->assertSee('PUBLIC SUPPORT REPLY')->assertDontSee('INTERNAL PRIVATE NOTE');
        $this->assertSame(1,$case->updates()->whereNull('read_at')->count());
        $this->get('/consultation/track?panel=plan')->assertOk()->assertSee('Secure your account')->assertDontSee('PUBLIC SUPPORT REPLY');
        $this->assertSame(0,$case->updates()->whereNull('read_at')->count());
        $this->get('/consultation/track?panel=timeline')->assertOk()->assertSee(__('workflow.timeline'));
        $this->post('/consultation/tasks/'.$task->id,['completed'=>1])->assertRedirect();
        $this->assertNotNull($task->fresh()->completed_at);
        $this->post('/consultation/tasks/'.$task->id,['completed'=>0])->assertRedirect();
        $this->assertNull($task->fresh()->completed_at);
        $other = $this->submit();
        $this->post('/consultation/tasks/'.$task->id,['completed'=>1])->assertNotFound();
        $this->assertSame(0,$other->tasks()->count());
    }

    public function test_verified_email_recovery_uses_expiring_one_time_token(): void
    {
        $case = $this->submit();
        $case->update(['contact_verified_at'=>now()]);
        $link = null;
        $this->mock(PlatformMail::class,function ($mock) use (&$link) {
            $mock->shouldReceive('sendTo')->once()->andReturnUsing(function ($email,$name,$key,$url) use (&$link) { $link=$url; return true; });
        });
        $this->post('/consultation/forget');
        $this->post('/consultation/recover',['number'=>$case->request_number,'email'=>'requester@example.test'])->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull($link);
        $token = basename(parse_url($link,PHP_URL_PATH));
        $this->assertSame(1,DB::table('request_recovery_tokens')->where('token_hash',hash('sha256',$token))->count());
        $this->get($link)->assertRedirect('/consultation/track');
        $this->get('/consultation/track')->assertSee($case->request_number);
        $this->get($link)->assertForbidden();
    }

    public function test_recovery_does_not_accept_unverified_or_wrong_email_and_verification_rejects_tampering(): void
    {
        $case = $this->submit();
        $this->mock(PlatformMail::class,fn ($mock)=>$mock->shouldNotReceive('sendTo'));
        $this->post('/consultation/recover',['number'=>$case->request_number,'email'=>'requester@example.test'])->assertRedirect();
        $this->assertSame(0,DB::table('request_recovery_tokens')->count());
        $url = URL::temporarySignedRoute('consultation.verify-contact',now()->addHour(),['consultationRequest'=>$case->id,'hash'=>hash('sha256','requester@example.test')]);
        $this->get($url.'&changed=true')->assertForbidden();
        $this->get($url)->assertRedirect('/consultation/track');
        $this->assertNotNull($case->fresh()->contact_verified_at);
        $this->post('/consultation/recover',['number'=>$case->request_number,'email'=>'other@example.test'])->assertRedirect();
        $this->assertSame(0,DB::table('request_recovery_tokens')->count());
    }

    public function test_quick_exit_signs_out_and_removes_request_access(): void
    {
        $this->actingAs(User::factory()->create());
        $this->submit();
        $this->post('/quick-exit')->assertRedirect('/neutral');
        $this->assertGuest();
        $this->post('/consultation/reply',['body'=>'Cannot access'])->assertNotFound();
        $this->get('/neutral')->assertOk()->assertDontSee('BLACKMAIL');
    }

    public function test_deletion_request_is_scoped_and_requires_admin_review_before_erasure(): void
    {
        $case = $this->submit();
        $case->messages()->create(['sender'=>'requester','body'=>'Private content']);
        $this->post('/privacy-center',['scope'=>'request','confirm'=>'1'])->assertRedirect();
        $this->assertNotNull($case->fresh());
        $privacy = PrivacyRequest::firstOrFail();
        $this->assertSame($case->id,$privacy->consultation_request_id);
        $admin = User::factory()->create();
        $role = Role::create(['name'=>'super-admin','label_ar'=>'مدير']);
        $admin->roles()->attach($role);
        app(PrivacyErasure::class)->execute($privacy->id,$admin->id);
        $this->assertNull($case->fresh());
        $this->assertSame(0,DB::table('consultation_messages')->count());
        $this->assertSame('completed',$privacy->fresh()->status);
    }

    public function test_operations_and_privacy_admin_are_restricted_and_render_for_admin(): void
    {
        $member = User::factory()->create(['is_active'=>true]);
        $this->actingAs($member,'admin')->get($this->panelPath('/operations'))->assertForbidden();
        $admin = User::factory()->create(['is_active'=>true]);
        $role = Role::create(['name'=>'super-admin','label_ar'=>'مدير']);
        $admin->roles()->attach($role);
        $this->withoutMiddleware(\Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class);
        $this->actingAs($admin,'admin')->get($this->panelPath('/operations'))->assertOk();
        $this->get($this->panelPath('/privacy-requests'))->assertOk();
    }

    public function test_expired_recovery_links_cannot_open_a_request(): void
    {
        $case = $this->submit();
        $token = str_repeat('x',64);
        DB::table('request_recovery_tokens')->insert(['consultation_request_id'=>$case->id,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->subMinute(),'created_at'=>now(),'updated_at'=>now()]);
        $this->post('/consultation/forget');
        $this->get('/consultation/recover/open/'.$token)->assertForbidden();
        $this->post('/consultation/reply',['body'=>'No access'])->assertNotFound();
    }

    public function test_staff_can_create_and_edit_plan_and_add_encrypted_internal_note(): void
    {
        $case = $this->submit();
        $admin = User::factory()->create(['is_active'=>true]);
        $role = Role::create(['name'=>'super-admin','label_ar'=>'مدير']);
        $admin->roles()->attach($role);
        $this->actingAs($admin);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $page = \Livewire\Livewire::test(\App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest::class,['record'=>$case->id]);
        $page->callAction('add_task',['title'=>'Secure account'])->assertHasNoActionErrors();
        $task = $case->tasks()->firstOrFail();
        $page->callAction('edit_task',['task_id'=>$task->id,'title'=>'Enable two-factor authentication'])->assertHasNoActionErrors();
        $this->assertSame('Enable two-factor authentication',$task->fresh()->title);
        $page->callAction('internal_note',['body'=>'Internal case assessment'])->assertHasNoActionErrors();
        $note = $case->internalNotes()->firstOrFail();
        $this->assertStringNotContainsString('Internal case assessment',$note->getRawOriginal('body'));
    }

    public function test_member_account_deletion_only_erases_that_members_data(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($member);
        $case = $this->submit();
        $this->post('/privacy-center',['scope'=>'account','confirm'=>'1','user_id'=>$other->id])->assertRedirect();
        $privacy = PrivacyRequest::firstOrFail();
        $this->assertSame($member->id,$privacy->user_id);
        $admin = User::factory()->create();
        $role = Role::create(['name'=>'super-admin','label_ar'=>'مدير']);
        $admin->roles()->attach($role);
        app(PrivacyErasure::class)->execute($privacy->id,$admin->id);
        $this->assertNull($member->fresh());
        $this->assertNull($case->fresh());
        $this->assertNotNull($other->fresh());
    }

    public function test_invalid_legacy_contact_email_returns_helpful_validation_and_can_be_corrected(): void
    {
        $case = $this->submit();
        $case->update(['contact_details_encrypted'=>\Illuminate\Support\Facades\Crypt::encryptString('90000000')]);
        $this->from('/consultation/track')->post('/consultation/verify-contact')
            ->assertRedirect('/consultation/track')->assertSessionHasErrors('verification_email');
        $this->get('/consultation/track')->assertOk()->assertSee(__('workflow.valid_email_required'));
        $sentTo = null;
        $this->mock(PlatformMail::class,function ($mock) use (&$sentTo) {
            $mock->shouldReceive('sendTo')->once()->andReturnUsing(function ($email) use (&$sentTo) { $sentTo=$email; return true; });
        });
        $this->from('/consultation/track')->post('/consultation/verify-contact',['verification_email'=>'correct@example.test'])
            ->assertRedirect('/consultation/track')->assertSessionHas('success');
        $this->assertSame('correct@example.test',$case->fresh()->contact_details);
        $this->assertSame('correct@example.test',$sentTo);
        $this->assertNull($case->fresh()->contact_verified_at);
    }

    public function test_new_email_requests_reject_phone_numbers_but_phone_contact_still_works(): void
    {
        $payload = ['category'=>'blackmail','contact_details'=>'90000000','description'=>'A sufficiently detailed description of this incident.','consent'=>'1'];
        $this->post('/consultation',$payload+['preferred_contact_method'=>'email'])->assertSessionHasErrors('contact_details');
        $this->assertSame(0,ConsultationRequest::count());
        $this->post('/consultation',$payload+['preferred_contact_method'=>'phone'])->assertRedirect('/consultation/success');
        $this->from('/consultation/track')->post('/consultation/verify-contact')->assertRedirect('/consultation/track')->assertSessionHasErrors('verification_email');
    }
}
