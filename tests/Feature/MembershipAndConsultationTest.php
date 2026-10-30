<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class MembershipAndConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_update_password_and_logout(): void
    {
        Role::create(['name' => 'member', 'label_ar' => 'عضو']);
        $this->post('/register', ['name' => 'عضو تجريبي', 'email' => 'member@example.test', 'password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123', 'terms' => '1'])->assertRedirect('/account');
        $user = User::where('email', 'member@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('member'));
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => 'member@example.test', 'password' => 'StrongPass123'])->assertRedirect('/account');
        $this->put('/account/password', ['current_password' => 'StrongPass123', 'password' => 'NewStrongPass456', 'password_confirmation' => 'NewStrongPass456'])->assertSessionHas('success');
    }

    public function test_regular_member_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'member', 'label_ar' => 'عضو']);
        $user->roles()->attach($role);
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_super_admin_is_required_to_set_up_mfa_before_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label_ar' => 'مدير النظام']);
        $user->roles()->attach($role);
        $this->actingAs($user)->get('/admin')->assertRedirect();
    }

    public function test_consultation_is_encrypted_and_tracking_code_is_not_stored_plaintext(): void
    {
        $response = $this->post('/consultation', ['category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'victim@example.test', 'description' => 'هذا وصف كاف للحالة دون بيانات خاصة إضافية', 'consent' => '1', 'website' => '']);
        $response->assertRedirect('/consultation/success')->assertSessionHas('tracking_code');
        $case = ConsultationRequest::firstOrFail();
        $this->assertNotSame('victim@example.test', $case->contact_details_encrypted);
        $this->assertSame('victim@example.test', Crypt::decryptString($case->contact_details_encrypted));
        $this->assertSame(64, strlen($case->tracking_hash));
    }

    public function test_urgent_blackmail_service_and_private_tracking_work(): void
    {
        $this->get('/stop-blackmail')->assertOk()->assertSee('استجابة عاجلة لحالة ابتزاز');

        $response = $this->post('/consultation', [
            'category' => 'blackmail', 'urgency' => 'critical', 'incident_stage' => 'published',
            'preferred_contact_method' => 'phone', 'contact_details' => '90000000',
            'safe_contact_time' => 'بعد السادسة', 'description' => 'وصف كاف للحالة العاجلة دون إرفاق أي محتوى حساس',
            'consent' => '1', 'website' => '',
        ]);
        $code = $response->getSession()->get('tracking_code');
        $response->assertRedirect('/consultation/success');
        $this->assertSame('critical', ConsultationRequest::latest('id')->firstOrFail()->urgency);

        $this->post('/consultation/track', ['tracking_code' => $code])
            ->assertOk()->assertSee('جديد')->assertDontSee('90000000');
    }

    public function test_requester_can_read_staff_reply_and_respond_only_after_tracking_authentication(): void
    {
        $response = $this->post('/consultation', ['category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'private@example.test', 'description' => 'A sufficiently detailed confidential incident description.', 'consent' => '1']);
        $code = $response->getSession()->get('tracking_code');
        $case = ConsultationRequest::firstOrFail();
        $message = $case->messages()->create(['sender' => 'staff', 'body' => 'Please preserve the evidence.']);
        $this->assertNotSame('Please preserve the evidence.', $message->getRawOriginal('body'));
        $this->withSession(['consultation_access_id' => null])->post('/consultation/reply', ['body' => 'My reply'])->assertNotFound();
        $this->post('/consultation/track', ['tracking_code' => $code])->assertOk()->assertSee('Please preserve the evidence.');
        $this->post('/consultation/reply', ['body' => 'Evidence has been preserved.'])->assertRedirect('/consultation/track');
        $this->assertSame('in_review', $case->fresh()->status);
        $this->assertSame(2, $case->messages()->count());
    }

    public function test_technical_metadata_is_encrypted_hidden_and_expires(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.25'])->post('/consultation', [
            'category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'private@example.test',
            'description' => 'A sufficiently detailed incident description.', 'consent' => '1',
        ])->assertRedirect('/consultation/success');
        $case = ConsultationRequest::firstOrFail();
        $this->assertSame('192.0.2.25', $case->technical_metadata['ip_address']);
        $this->assertStringNotContainsString('192.0.2.25', $case->getRawOriginal('technical_metadata'));
        $this->assertArrayNotHasKey('technical_metadata', $case->toArray());
        $case->update(['retain_until' => now()->subMinute()]);
        $this->artisan('consultations:purge-technical')->assertSuccessful();
        $this->assertNull($case->fresh()->technical_metadata);
    }
}
