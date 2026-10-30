<?php

namespace Tests\Feature;

use App\Livewire\HelpWizard;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_arabic_first_and_has_security_headers(): void
    {
        $this->get('/')->assertOk()->assertSee('لا تواجه الابتزاز')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_help_wizard_requires_a_threat_type_and_does_not_persist_answers(): void
    {
        Livewire::test(HelpWizard::class)->call('next')->assertHasErrors('threatType')->set('threatType', 'account')->call('next')->assertSet('step', 2);
        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_help_wizard_completes_child_and_immediate_danger_flow(): void
    {
        Livewire::test(HelpWizard::class)
            ->set('threatType', 'child')->call('next')->assertSet('step', 2)
            ->set('country', 'OM')->call('next')->assertSet('step', 3)
            ->set('physicalDanger', true)->set('minorInvolved', true)->call('next')->assertSet('step', 4)
            ->assertSee('السلامة الجسدية أولاً')->assertSee('مسار حماية طفل');
        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_threat_option_can_be_selected_and_unselected(): void
    {
        Livewire::test(HelpWizard::class)->call('selectThreat', 'account')->assertSet('threatType', 'account')->call('selectThreat', 'account')->assertSet('threatType', '');
    }

    public function test_unverified_country_is_not_public(): void
    {
        Country::create(['code' => 'OM', 'name_ar' => 'عُمان', 'name_en' => 'Oman', 'slug' => 'oman']);
        $this->assertCount(0, Country::verified()->get());
    }

    public function test_locale_endpoint_rejects_unsupported_locales(): void
    {
        $this->post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
    }
}
