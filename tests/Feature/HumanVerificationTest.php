<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HumanVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withMiddleware(\App\Http\Middleware\VerifyHuman::class);
    }

    private function challenge(string $page = '/consultation'): array
    {
        $html = $this->get($page)->assertOk()->getContent();
        preg_match('/name="human_token" value="([^"]+)"/', $html, $token);
        preg_match('/<bdi dir="ltr">(\d+) \+ (\d+)<\/bdi>/', $html, $numbers);
        return ['human_token'=>$token[1], 'human_answer'=>(string)($numbers[1]+$numbers[2])];
    }

    private function payload(): array
    {
        return ['category'=>'blackmail','service_type'=>'support','preferred_contact_method'=>'email',
            'contact_details'=>'example@example.test','description'=>'A sufficiently long test description of a request.', 'consent'=>'1'];
    }

    public function test_missing_wrong_and_replayed_challenges_are_rejected(): void
    {
        $this->post('/consultation',$this->payload())->assertSessionHasErrors('human_answer');
        $wrong=$this->challenge();
        $wrong['human_answer']='99';
        $this->post('/consultation',$this->payload()+$wrong)->assertSessionHasErrors('human_answer');
        $valid=$this->challenge();
        $this->post('/consultation',$this->payload()+$valid)->assertRedirect(route('consultation.success'));
        $this->assertDatabaseHas('consultation_requests',['service_type'=>'support']);
        $this->post('/consultation',$this->payload()+$valid)->assertSessionHasErrors('human_answer');
        $this->assertDatabaseCount('consultation_requests',1);
    }

    public function test_arabic_digits_are_accepted_and_challenges_are_bound_to_the_form(): void
    {
        $wrongForm=$this->challenge('/login');
        $this->post('/consultation',$this->payload()+$wrongForm)->assertSessionHasErrors('human_answer');
        $valid=$this->challenge();
        $valid['human_answer']=strtr($valid['human_answer'],array_combine(str_split('0123456789'),preg_split('//u','٠١٢٣٤٥٦٧٨٩',-1,PREG_SPLIT_NO_EMPTY)));
        $this->post('/consultation',$this->payload()+$valid)->assertRedirect(route('consultation.success'));
    }

    public function test_signed_in_members_skip_the_check_but_guests_and_inactive_accounts_do_not(): void
    {
        $member=\App\Models\User::factory()->create(['is_active'=>true]);
        $this->actingAs($member)->get('/account')->assertOk()->assertDontSee('human_token',false)->assertDontSee('التحقق من المرسل');
        $this->actingAs($member)->get('/consultation')->assertOk()->assertDontSee('human_token',false);
        $this->actingAs($member)->post('/consultation',$this->payload())->assertSessionHasNoErrors()->assertRedirect(route('consultation.success'));
        $this->assertDatabaseCount('consultation_requests',1);

        $inactive=\App\Models\User::factory()->create(['is_active'=>false]);
        $this->assertTrue(app(\App\Services\HumanChallenge::class)->required(\Illuminate\Http\Request::create('/')->setUserResolver(fn () => $inactive)));

        auth()->guard('web')->logout();
        $this->get('/consultation')->assertSee('human_token',false);
        $this->post('/consultation',$this->payload())->assertSessionHasErrors('human_answer');
    }

    public function test_expired_and_honeypot_submissions_are_rejected_and_quick_exit_is_unblocked(): void
    {
        $valid=$this->challenge();
        $challenges=session('human_challenges');
        $challenges[$valid['human_token']]['expires']=time()-1;
        $this->withSession(['human_challenges'=>$challenges])->post('/consultation',$this->payload()+$valid)->assertSessionHasErrors('human_answer');
        $valid=$this->challenge();
        $this->post('/consultation',$this->payload()+$valid+['human_website'=>'spam'])->assertSessionHasErrors('human_answer');
        $this->post('/quick-exit')->assertRedirect(route('neutral'));
        $this->assertDatabaseCount('consultation_requests',0);
    }
}
