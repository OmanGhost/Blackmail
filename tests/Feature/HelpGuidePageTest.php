<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpGuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_guide_explains_its_flow_and_does_not_create_a_request(): void
    {
        $this->get('/help')->assertOk()->assertSee('كيف يعمل الدليل؟')
            ->assertSee('الدليل لا ينشئ طلب مساعدة تلقائيًا.')
            ->assertSee('اختيار الدولة اختياري')
            ->assertSee('data-advice="account"', false)->assertSee('data-guidance="minor"', false)
            ->assertSee(route('blackmail.help'));
        $this->withSession(['locale'=>'en'])->get('/help')->assertOk()
            ->assertSee('How does the guide work?')->assertSee('Country is optional');
        $this->assertDatabaseCount('consultation_requests', 0);
    }
}
