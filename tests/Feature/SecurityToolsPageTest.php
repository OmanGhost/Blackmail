<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SecurityToolsPageTest extends TestCase
{
    use RefreshDatabase;
    public function test_page_explains_actions_and_links_to_official_guidance_without_collecting_secrets(): void
    {
        $page=$this->get('/tools')->assertOk()->assertSee('كيف أستخدمها؟')
            ->assertSee('نفّذت هذه الخطوة في حسابي')->assertSee('وليست فحصًا للحساب')
            ->assertSee('افتح أول خطوة غير مكتملة')
            ->assertSee('https://support.google.com/accounts/answer/46526')
            ->assertSee('https://www.meta.com/safety/topics/safety-basics/tools/security/')
            ->assertSee(route('blackmail.help'))->assertDontSee('name="password"',false);
        $this->assertSame(5,substr_count($page->getContent(),'class="card security-action"'));
        $this->withSession(['locale'=>'en'])->get('/tools')->assertOk()
            ->assertSee('How do I use this?')->assertSee('not an account scan')
            ->assertSee('Open first unfinished step');
        $this->assertDatabaseCount('consultation_requests',0);
    }
}
