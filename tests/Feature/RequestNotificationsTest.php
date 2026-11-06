<?php

namespace Tests\Feature;

use App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest;
use App\Models\ConsultationRequest;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\PlatformMail;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{email: string, key: string, message: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        // One test app serves several requests, so after-response callbacks would pile up; send immediately instead.
        app(\Illuminate\Contracts\Bus\Dispatcher::class)->withoutDispatchingAfterResponses();
        $sent = &$this->sent;
        $this->app->instance(PlatformMail::class, new class($sent) extends PlatformMail {
            public function __construct(private array &$sent) {}
            public function sendTo(string $email, string $name, string $key, string $message = '', ?int $userId = null): bool
            {
                $this->sent[] = compact('email', 'key', 'message');
                return true;
            }
        });
    }

    private function staff(string $role, string $email, bool $active = true): User
    {
        $user = User::factory()->create(['email' => $email, 'is_active' => $active]);
        $user->roles()->attach(Role::firstOrCreate(['name' => $role], ['label_ar' => $role]));
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + ['category' => 'blackmail', 'urgency' => 'critical', 'preferred_contact_method' => 'email', 'contact_details' => 'person@example.test', 'description' => 'private-description that must never be emailed.', 'consent' => '1'];
    }

    private function sentTo(string $key): array
    {
        return collect($this->sent)->where('key', $key)->pluck('email')->sort()->values()->all();
    }

    public function test_notification_templates_are_seeded_and_protected(): void
    {
        foreach (['staff_new_request', 'staff_request_reply', 'request_replied_contact'] as $key) {
            $this->assertTrue(EmailTemplate::where('key', $key)->where('is_active', true)->exists());
            $this->assertFalse(\App\Filament\Resources\EmailTemplates\EmailTemplateResource::canDelete(EmailTemplate::where('key', $key)->first()));
        }
    }

    public function test_new_request_alerts_active_request_staff_without_private_details(): void
    {
        $this->staff('super-admin', 'admin@example.test');
        $this->staff('support-agent', 'agent@example.test');
        $this->staff('security-consultant', 'consultant@example.test');
        $this->staff('support-agent', 'inactive@example.test', false);
        $this->staff('content-manager', 'editor@example.test');

        $this->post('/consultation', $this->payload())->assertRedirect('/consultation/success');

        $case = ConsultationRequest::firstOrFail();
        $this->assertSame(['admin@example.test', 'agent@example.test', 'consultant@example.test'], $this->sentTo('staff_new_request'));
        $message = collect($this->sent)->firstWhere('key', 'staff_new_request')['message'];
        $this->assertStringContainsString($case->request_number, $message);
        $this->assertStringContainsString('ابتزاز إلكتروني', $message);
        $this->assertStringContainsString('حرج', $message);
        $this->assertStringContainsString('/'.\App\Support\PanelAccess::path().'/consultation-requests/'.$case->id, $message);
        $this->assertStringNotContainsString('private-description', $message);
        $this->assertStringNotContainsString('person@example.test', $message);
    }

    public function test_requester_reply_alerts_assigned_staff_or_all_staff_when_unassigned(): void
    {
        $admin = $this->staff('super-admin', 'admin@example.test');
        $agent = $this->staff('support-agent', 'agent@example.test');
        $this->post('/consultation', $this->payload());
        $case = ConsultationRequest::firstOrFail();
        $this->sent = [];

        $this->post('/consultation/reply', ['body' => 'Here is more information.'])->assertRedirect('/consultation/track');
        $this->assertSame(['admin@example.test', 'agent@example.test'], $this->sentTo('staff_request_reply'));
        $this->assertStringNotContainsString('more information', collect($this->sent)->firstWhere('key', 'staff_request_reply')['message']);

        $this->sent = [];
        $case->update(['assigned_to' => $agent->id]);
        $this->post('/consultation/reply', ['body' => 'Another update for the team.']);
        $this->assertSame(['agent@example.test'], $this->sentTo('staff_request_reply'));

        $this->sent = [];
        $agent->update(['is_active' => false]);
        $this->post('/consultation/reply', ['body' => 'A third update for the team.']);
        $this->assertSame(['admin@example.test'], $this->sentTo('staff_request_reply'));
    }

    public function test_staff_reply_notifies_member_by_account_email(): void
    {
        $member = User::factory()->create(['email' => 'member@example.test']);
        $this->actingAs($member)->post('/consultation', $this->payload());
        $case = ConsultationRequest::firstOrFail();
        $this->sent = [];

        $admin = $this->staff('super-admin', 'admin@example.test');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ViewConsultationRequest::class, ['record' => $case->id])
            ->callAction('reply', ['body' => 'We are working on your request.'])->assertHasNoActionErrors();

        $this->assertSame(1, $case->messages()->where('sender', 'staff')->count());
        $this->assertSame(['member@example.test'], $this->sentTo('request_replied'));
    }

    public function test_staff_reply_notifies_guest_only_through_verified_contact_email(): void
    {
        $this->post('/consultation', $this->payload());
        $case = ConsultationRequest::firstOrFail();
        $notifications = app(\App\Services\RequestNotifications::class);
        $this->sent = [];

        $this->assertFalse($notifications->staffReplied($case));
        $this->assertSame([], $this->sent);

        $case->update(['contact_verified_at' => now()]);
        $this->assertTrue($notifications->staffReplied($case->fresh()));
        $this->assertSame(['person@example.test'], $this->sentTo('request_replied_contact'));
        $this->assertSame(route('consultation.track'), $this->sent[0]['message']);

        $this->sent = [];
        $phone = ConsultationRequest::create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(), 'tracking_hash' => hash('sha256', 'PHONECASE123'), 'category' => 'other',
            'preferred_contact_method' => 'phone', 'contact_details_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('+96890000000'),
            'description_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('Phone request description'), 'consented_at' => now(),
            'status' => 'new', 'retain_until' => now()->addDays(90), 'contact_verified_at' => now(),
        ]);
        $this->assertFalse($notifications->staffReplied($phone));
        $this->assertSame([], $this->sent);
    }
}
