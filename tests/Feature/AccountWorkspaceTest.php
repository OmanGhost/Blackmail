<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ConsultationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_and_filters_only_return_the_members_own_requests(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $payload = ['category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'contact@example.test', 'description' => 'A sufficiently detailed description of this incident.', 'consent' => '1'];
        $this->actingAs($owner)->post('/consultation', $payload);
        $first = ConsultationRequest::latest('id')->firstOrFail();
        $this->post('/consultation', $payload);
        $closed = ConsultationRequest::latest('id')->firstOrFail();
        $closed->update(['status' => 'closed']);
        $this->actingAs($other)->post('/consultation', $payload);
        $foreign = ConsultationRequest::latest('id')->firstOrFail();
        $this->actingAs($owner)->get('/account?tab=requests&status=active')->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $first->id);
        $this->get('/account?tab=requests&q='.$closed->request_number)->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $closed->id);
        $this->get('/account?tab=requests&q='.$foreign->request_number)->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 0);
        $this->get('/account')->assertViewHas('stats', ['total' => 2, 'active' => 1, 'replied' => 0, 'closed' => 1]);
    }

    public function test_profile_security_and_english_workspace_render(): void
    {
        $this->actingAs(User::factory()->create())->get('/account?tab=profile')->assertOk()->assertSee('name="email"', false);
        $this->get('/account?tab=security')->assertOk()->assertSee('name="current_password"', false);
        $this->withSession(['locale' => 'en'])->get('/account')->assertOk()->assertSee('Your support, all in one place');
    }
}
