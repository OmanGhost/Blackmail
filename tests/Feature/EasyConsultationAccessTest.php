<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EasyConsultationAccessTest extends TestCase
{
    use RefreshDatabase;

    private function submit(): ConsultationRequest
    {
        $this->post('/consultation', [
            'category' => 'blackmail', 'preferred_contact_method' => 'email', 'contact_details' => 'contact@example.test',
            'description' => 'My original description of this incident.', 'consent' => '1',
        ])->assertRedirect('/consultation/success');
        return ConsultationRequest::firstOrFail();
    }

    public function test_submission_opens_request_without_entering_code_and_forget_removes_access(): void
    {
        $case = $this->submit();
        $this->get('/consultation/track')->assertOk()->assertSee($case->request_number)->assertSee($case->description)
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('نسخ رابط المتابعة الخاص');
        $this->post('/consultation/forget')->assertRedirect('/consultation/track');
        $this->get('/consultation/track')->assertDontSee($case->description);
        $this->post('/consultation/reply', ['body' => 'A message'])->assertNotFound();
    }

    public function test_private_link_opens_case_but_unsigned_modified_and_expired_links_are_rejected(): void
    {
        $case = $this->submit();
        $url = URL::temporarySignedRoute('consultation.open', now()->addDays(30), ['consultationRequest' => $case->id]);
        $this->post('/consultation/forget');
        $this->get('/consultation/open/'.$case->id)->assertForbidden();
        $this->get($url.'&unexpected=changed')->assertForbidden();
        $this->get($url)->assertRedirect('/consultation/track');
        $this->get('/consultation/track')->assertSee($case->request_number);
        $this->post('/consultation/forget');
        $this->travel(31)->days();
        $this->get($url)->assertForbidden();
    }

    public function test_invalid_short_code_has_arabic_guidance(): void
    {
        $this->from('/consultation/track')->post('/consultation/track', ['tracking_code' => '100001'])
            ->assertSessionHasErrors(['tracking_code' => 'هذا الحقل للرمز السري المكون من 12 حرفًا ورقمًا. إذا لديك حساب، اختر طلبك من القائمة دون إدخال رمز.']);
    }
}
