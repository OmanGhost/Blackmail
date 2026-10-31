<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\EmailDelivery;
use App\Models\EmailTemplate;
use App\Models\MailSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\PlatformMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MemberRequestsAndMailTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'contact@example.test', 'description' => 'A sufficiently detailed description for this case.', 'consent' => '1'];
    }

    public function test_requests_belong_to_authenticated_user_and_other_members_cannot_open_them(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->post('/consultation', $this->payload() + ['user_id' => $other->id])->assertRedirect('/consultation/success');
        $case = ConsultationRequest::firstOrFail();
        $this->assertSame($owner->id, $case->user_id);
        $this->get('/account')->assertOk()->assertSee(route('account.requests.show', $case));
        $this->get('/account/requests/'.$case->id)->assertRedirect('/consultation/track');
        $this->actingAs($other)->get('/account/requests/'.$case->id)->assertNotFound();
    }

    public function test_guest_cannot_assign_a_request_to_a_user(): void
    {
        $user = User::factory()->create();
        $this->post('/consultation', $this->payload() + ['user_id' => $user->id])->assertRedirect();
        $this->assertNull(ConsultationRequest::firstOrFail()->user_id);
    }

    public function test_request_number_is_numeric_and_identical_in_receipt_account_and_tracking(): void
    {
        $owner = User::factory()->create();
        $response = $this->actingAs($owner)->post('/consultation', $this->payload());
        $case = ConsultationRequest::firstOrFail();
        $number = $case->request_number;
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $number);
        $response->assertSessionHas('request_number', $number);
        $code = $response->getSession()->get('tracking_code');
        $this->get('/consultation/success')->assertOk()->assertSee($number);
        $this->get('/account')->assertOk()->assertSee($number);
        $this->post('/consultation/track', ['tracking_code' => $code])->assertOk()->assertSee($number);
        $this->assertSame($number, $case->fresh()->request_number);
    }

    public function test_mail_uses_editable_template_and_records_delivery_without_storing_message(): void
    {
        $user = User::factory()->create(['name' => 'Example member']);
        MailSetting::first()->update(['enabled' => true, 'host' => 'smtp.example.test', 'from_address' => 'team@example.test', 'password' => 'smtp-secret']);
        $this->assertStringNotContainsString('smtp-secret', MailSetting::first()->getRawOriginal('password'));
        EmailTemplate::where('key', 'general')->update(['subject' => 'Hello {{name}}', 'body' => '{{message}} — {{account_url}}']);
        $mailer = \Mockery::mock(\Illuminate\Mail\Mailer::class);
        $mailer->shouldReceive('raw')->once()->withArgs(function ($body, $callback) use ($user) {
            $email = new Email;
            $callback(new Message($email));
            return str_contains($body, 'Custom message') && $email->getTo()[0]->getAddress() === $user->email && $email->getSubject() === 'Hello Example member';
        });
        Mail::shouldReceive('purge')->with('platform')->once();
        Mail::shouldReceive('mailer')->with('platform')->once()->andReturn($mailer);
        $this->assertTrue(app(PlatformMail::class)->send($user, 'general', 'Custom message'));
        $this->assertSame('sent', EmailDelivery::first()->status);
    }

    public function test_mail_disabled_or_failed_does_not_claim_delivery(): void
    {
        $user = User::factory()->create();
        $this->assertFalse(app(PlatformMail::class)->send($user, 'general'));
        $this->assertSame(0, EmailDelivery::count());
        MailSetting::first()->update(['enabled' => true]);
        Mail::shouldReceive('purge')->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->assertFalse(app(PlatformMail::class)->send($user, 'general'));
        $this->assertSame('failed', EmailDelivery::first()->status);
    }

    public function test_admin_can_render_user_details_and_mail_management(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->post('/consultation', $this->payload())->assertRedirect();
        $admin = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label_ar' => 'مدير']);
        $admin->roles()->attach($role);
        $this->withoutMiddleware(\Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class);
        $this->actingAs($admin,'admin')->get($this->panelPath('/users/').$member->id)->assertOk()->assertSee('contact@example.test');
        $this->get($this->panelPath('/mail-settings'))->assertOk();
        $this->get($this->panelPath('/email-templates'))->assertOk();
    }
}
