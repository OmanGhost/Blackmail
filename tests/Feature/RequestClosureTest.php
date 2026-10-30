<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Role;
use App\Models\User;
use App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestClosureTest extends TestCase
{
    use RefreshDatabase;

    private function submit(): ConsultationRequest
    {
        $this->post('/consultation', ['category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'test@example.test', 'description' => 'A sufficiently detailed description for testing.', 'consent' => '1'])->assertRedirect();
        return ConsultationRequest::firstOrFail();
    }

    public function test_user_can_complete_rate_once_and_cannot_send_messages_after_completion(): void
    {
        $case = $this->submit();
        $this->post('/consultation/close', ['status' => 'completed'])->assertRedirect('/consultation/track');
        $this->assertSame('completed', $case->fresh()->status);
        $this->assertSame('requester', $case->fresh()->closed_by_role);
        $this->assertNotNull($case->fresh()->closed_at);
        $this->assertSame('new', $case->statusHistory()->first()->from_status);
        $this->get('/consultation/track')->assertSee('كيف كانت تجربتك مع فريق الدعم؟');
        $this->post('/consultation/reply', ['body' => 'Additional message'])->assertStatus(422);
        $this->post('/consultation/rate', ['rating' => 6])->assertSessionHasErrors('rating');
        $this->post('/consultation/rate', ['rating' => 5, 'comment' => 'Helpful service'])->assertRedirect('/consultation/track');
        $this->assertSame(5, $case->fresh()->rating);
        $this->assertSame('Helpful service', $case->fresh()->rating_comment);
        $this->assertStringNotContainsString('Helpful service', $case->fresh()->getRawOriginal('rating_comment'));
        $this->post('/consultation/rate', ['rating' => 1])->assertStatus(422);
    }

    public function test_rating_and_closure_require_access_and_open_cases_cannot_be_rated(): void
    {
        $case = $this->submit();
        $this->post('/consultation/rate', ['rating' => 5])->assertStatus(422);
        $this->post('/consultation/close', ['status' => 'in_progress'])->assertSessionHasErrors('status');
        $this->post('/consultation/forget');
        $this->post('/consultation/close', ['status' => 'closed'])->assertNotFound();
        $this->post('/consultation/rate', ['rating' => 5])->assertNotFound();
        $this->assertSame('new', $case->fresh()->status);
    }

    public function test_admin_can_follow_up_complete_and_reopen_with_history(): void
    {
        $case = $this->submit();
        $admin = User::factory()->create(['is_active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label_ar' => 'مدير']);
        $admin->roles()->attach($role);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ViewConsultationRequest::class, ['record' => $case->id])
            ->callAction('change_status', ['status' => 'in_progress'])->assertHasNoActionErrors()
            ->callAction('change_status', ['status' => 'closed'])->assertHasNoActionErrors();
        $this->assertSame('staff', $case->fresh()->closed_by_role);
        $this->assertSame('closed', $case->fresh()->status);
        Livewire::test(ViewConsultationRequest::class, ['record' => $case->id])
            ->callAction('change_status', ['status' => 'in_progress'])->assertHasNoActionErrors();
        $this->assertNull($case->fresh()->closed_at);
        $this->assertSame(3, $case->statusHistory()->count());
    }
}
