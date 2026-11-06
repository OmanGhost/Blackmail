<?php

namespace Tests\Feature;

use App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Filament\Resources\ServiceSections\ServiceSectionResource;
use App\Models\BillingSetting;
use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceSection;
use App\Models\User;
use App\Services\PlatformMail;
use App\Support\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesAndBillingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{email: string, key: string, message: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
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

    private function service(array $overrides = [], array $section = []): Service
    {
        $section = ServiceSection::create($section + ['name_ar' => 'استعادة الحسابات', 'name_en' => 'Account recovery', 'slug' => 'recovery-'.uniqid()]);

        return Service::create($overrides + [
            'service_section_id' => $section->id, 'name_ar' => 'استعادة حساب إنستغرام', 'name_en' => 'Instagram recovery', 'slug' => 'instagram-'.uniqid(),
            'summary_ar' => 'نساعدك على استعادة حسابك المخترق.', 'details_ar' => "## ما يشمله\n- تأمين البريد", 'price_minor' => 25500, 'duration_ar' => '3 أيام',
        ]);
    }

    private function staff(string $role = 'super-admin'): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::firstOrCreate(['name' => $role], ['label_ar' => $role]));
        $this->actingAs($user, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    private function keys(): array
    {
        return array_column($this->sent, 'key');
    }

    public function test_money_is_exact_in_baisa(): void
    {
        $this->assertSame(25500, Money::toMinor('25.5'));
        $this->assertSame(100, Money::toMinor('0.1'));
        $this->assertSame(7000, Money::toMinor('7'));
        $this->assertSame('25.500', Money::decimal(25500));
        $this->assertSame('0.050', Money::decimal(50));
        $this->expectException(\InvalidArgumentException::class);
        Money::toMinor('1.2345');
    }

    public function test_public_pages_list_only_published_services(): void
    {
        $service = $this->service();
        $hidden = $this->service(['name_ar' => 'خدمة مخفية', 'slug' => 'hidden', 'is_active' => false]);
        $closedSection = $this->service(['name_ar' => 'قسم مغلق', 'slug' => 'closed-section'], ['is_active' => false]);

        $this->get('/services')->assertOk()->assertSee('استعادة حساب إنستغرام')->assertSee('25.500')->assertDontSee('خدمة مخفية')->assertDontSee('قسم مغلق');
        $this->get('/services/'.$service->slug)->assertOk()->assertSee('ما يشمله', false)->assertSee('3 أيام')->assertSee(route('login'));
        $this->get('/services/hidden')->assertNotFound();
        $this->get('/services/closed-section')->assertNotFound();
        $this->get('/sitemap.xml')->assertSee(route('services.show', $service))->assertDontSee('/services/hidden');
        $this->get('/')->assertSee(route('services'));
    }

    public function test_only_members_can_request_and_staff_are_alerted(): void
    {
        $service = $this->service();
        $payload = ['description' => 'My account was taken over yesterday, please help.', 'consent' => '1'];
        $this->post('/services/'.$service->slug.'/request', $payload)->assertRedirect('/login');
        $this->assertSame(0, ConsultationRequest::count());

        User::factory()->create(['email' => 'agent@example.test', 'is_active' => true])->roles()->attach(Role::create(['name' => 'support-agent', 'label_ar' => 'دعم']));
        $member = User::factory()->create(['email' => 'member@example.test']);
        $this->actingAs($member)->get('/services/'.$service->slug)->assertSee(__('billing.send_request'));
        $this->post('/services/'.$service->slug.'/request', $payload)->assertRedirect('/consultation/track')->assertSessionHas('success', __('billing.request_sent'));

        $case = ConsultationRequest::firstOrFail();
        $this->assertSame([$service->id, $member->id, 'service', 'member@example.test'], [$case->service_id, $case->user_id, $case->service_type, $case->contact_details]);
        $this->assertStringContainsString('taken over', $case->description);
        $this->assertContains('request_received', $this->keys());
        $this->assertContains('staff_new_request', $this->keys());
        $this->get('/consultation/track')->assertOk()->assertSee('استعادة حساب إنستغرام')->assertSee('25.500');
    }

    public function test_staff_issue_service_invoice_member_views_it_and_staff_record_payment(): void
    {
        BillingSetting::current()->update(['tax_rate' => 5, 'issuer_name' => 'منصة الاختبار', 'payment_instructions' => 'IBAN OM00 TEST', 'due_days' => 7]);
        $service = $this->service();
        $member = User::factory()->create(['name' => 'Member One', 'email' => 'member@example.test']);
        $this->actingAs($member)->post('/services/'.$service->slug.'/request', ['description' => 'Please recover my hacked account soon.', 'consent' => '1']);
        $case = ConsultationRequest::firstOrFail();
        $this->sent = [];

        $this->staff('support-agent');
        Livewire::test(ViewConsultationRequest::class, ['record' => $case->id])->callAction('issue_service_invoice')->assertHasNoActionErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertMatchesRegularExpression('/^INV-\d{4}-0000\d$/', $invoice->number);
        $this->assertSame([25500, 1275, 26775, 'unpaid'], [$invoice->subtotal_minor, $invoice->tax_minor, $invoice->total_minor, $invoice->status]);
        $this->assertSame([$member->id, $case->id, $service->id], [$invoice->user_id, $invoice->consultation_request_id, $invoice->service_id]);
        $this->assertSame([['email' => 'member@example.test', 'key' => 'invoice_issued', 'message' => $invoice->number]], $this->sent);

        // Later changes to billing settings never alter an issued invoice.
        BillingSetting::current()->update(['issuer_name' => 'اسم جديد', 'tax_rate' => 0]);

        $this->actingAs($member)->get('/account?tab=invoices')->assertOk()->assertSee($invoice->number)->assertSee('26.775');
        $this->get('/account/invoices/'.$invoice->id)->assertOk()->assertSee('منصة الاختبار')->assertDontSee('اسم جديد')
            ->assertSee('IBAN OM00 TEST')->assertSee('25.500')->assertSee('1.275')->assertSee('26.775')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->actingAs(User::factory()->create())->get('/account/invoices/'.$invoice->id)->assertNotFound();

        $this->sent = [];
        $this->staff('support-agent');
        Livewire::test(\App\Filament\Resources\Invoices\Pages\ManageInvoices::class)
            ->callTableAction('mark_paid', $invoice, ['payment_method' => 'bank_transfer', 'payment_reference' => 'TRX-1'])->assertHasNoTableActionErrors();
        $invoice->refresh();
        $this->assertSame(['paid', 'bank_transfer', 'TRX-1'], [$invoice->status, $invoice->payment_method, $invoice->payment_reference]);
        $this->assertSame(['invoice_paid'], $this->keys());

        // A paid invoice cannot be cancelled or paid twice.
        app(\App\Services\Billing::class)->cancel($invoice);
        app(\App\Services\Billing::class)->markPaid($invoice, 'cash', null);
        $this->assertSame(['paid', 'bank_transfer'], [$invoice->fresh()->status, $invoice->fresh()->payment_method]);
        $this->assertSame(['invoice_paid'], $this->keys());
    }

    public function test_staff_issue_manual_invoice_and_cancel_it(): void
    {
        $member = User::factory()->create(['name' => 'Manual Member', 'email' => 'manual@example.test', 'is_active' => true]);
        $this->staff();
        Livewire::test(ManageInvoices::class)->callAction('create', [
            'user_id' => $member->id,
            'items' => [['description' => 'Consultation hour', 'quantity' => 2, 'unit_price' => '10.250'], ['description' => 'Report', 'quantity' => 1, 'unit_price' => '5']],
            'notes' => 'Thank you',
        ])->assertHasNoActionErrors();

        $invoice = Invoice::with('items')->firstOrFail();
        $this->assertSame([25500, 0, 25500], [$invoice->subtotal_minor, $invoice->tax_minor, $invoice->total_minor]);
        $this->assertCount(2, $invoice->items);
        $this->assertSame('Thank you', $invoice->notes);

        Livewire::test(ManageInvoices::class)->callTableAction('cancel', $invoice)->assertHasNoTableActionErrors();
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertFalse(InvoiceResource::canEdit($invoice) || InvoiceResource::canDelete($invoice));
    }

    public function test_admin_manages_services_with_decimal_prices_and_access_rules(): void
    {
        $section = ServiceSection::create(['name_ar' => 'قسم', 'slug' => 'section']);
        $this->staff('content-manager');
        Livewire::test(ManageServices::class)->callAction('create', [
            'service_section_id' => $section->id, 'slug' => 'new-service', 'price_minor' => '12.75', 'sort_order' => 0,
            'name_ar' => 'خدمة جديدة', 'summary_ar' => 'ملخص الخدمة', 'is_active' => true,
        ])->assertHasNoActionErrors();
        $this->assertSame(12750, Service::where('slug', 'new-service')->value('price_minor'));
        $this->assertFalse(ServiceSectionResource::canDelete($section));
        $this->assertFalse(InvoiceResource::canAccess());

        $this->staff('support-agent');
        $this->assertTrue(InvoiceResource::canAccess());
        $this->assertFalse(\App\Filament\Resources\Services\ServiceResource::canAccess());
        $this->assertFalse(\App\Filament\Resources\BillingSettings\BillingSettingResource::canAccess());
    }

    public function test_service_invoice_needs_a_member_request(): void
    {
        $service = $this->service();
        $this->post('/consultation', ['category' => 'other', 'preferred_contact_method' => 'email', 'contact_details' => 'guest@example.test', 'description' => 'Guest request without an account here.', 'consent' => '1']);
        $case = ConsultationRequest::firstOrFail();
        $case->update(['service_id' => $service->id]);
        $this->staff();
        Livewire::test(ViewConsultationRequest::class, ['record' => $case->id])->assertActionHidden('issue_service_invoice');
    }

    public function test_admin_billing_pages_render(): void
    {
        $service = $this->service();
        $member = User::factory()->create();
        app(\App\Services\Billing::class)->issue($member, [['description' => 'Line item', 'quantity' => 1, 'unit_price_minor' => 1000]], null, $service);
        $this->staff();
        $this->withoutMiddleware(\Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class);
        foreach (['/service-sections', '/services', '/invoices', '/billing-settings'] as $path) {
            $this->get($this->panelPath($path))->assertOk();
        }
        $this->get($this->panelPath('/invoices'))->assertSee('INV-')->assertSee('1.000');
        Livewire::test(ManageInvoices::class)->mountTableAction('view', Invoice::first())->assertHasNoTableActionErrors()->assertOk();
    }
}
