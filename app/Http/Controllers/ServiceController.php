<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceSection;
use App\Services\PlatformMail;
use App\Services\RequestNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $sections = ServiceSection::where('is_active', true)->orderBy('sort_order')->orderBy('id')
            ->with(['services' => fn ($q) => $q->where('is_active', true)])->get()
            ->filter(fn ($section) => $section->services->isNotEmpty());

        return view('services.index', ['sections' => $sections]);
    }

    public function show(Service $service): View
    {
        abort_unless($service->is_active && $service->section?->is_active, 404);

        return view('services.show', [
            'service' => $service,
            'related' => Service::available()->where('service_section_id', $service->service_section_id)->whereKeyNot($service->id)->orderBy('sort_order')->limit(3)->get(),
        ]);
    }

    public function request(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->is_active && $service->section?->is_active, 404);
        $data = $request->validate([
            'description' => ['required', 'string', 'min:20', 'max:3000'], 'consent' => ['accepted'], 'website' => ['nullable', 'max:0'],
        ]);
        $user = $request->user();
        $case = ConsultationRequest::create([
            'service_type' => 'service', 'service_id' => $service->id, 'user_id' => $user->id,
            'technical_metadata' => [
                'request_id' => (string) Str::uuid(), 'received_at_utc' => now()->utc()->toIso8601String(), 'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1024, ''), 'language' => Str::limit((string) $request->header('Accept-Language'), 255, ''),
                'transport' => $request->isSecure() ? 'HTTPS' : 'HTTP', 'notice_version' => 'technical-metadata-v1',
            ],
            'public_id' => (string) Str::uuid(), 'tracking_hash' => hash('sha256', Str::upper(Str::random(12))),
            'category' => 'other', 'urgency' => 'normal', 'preferred_contact_method' => 'email',
            'contact_details_encrypted' => Crypt::encryptString($user->email),
            'description_encrypted' => Crypt::encryptString('['.$service->name_ar."]\n".$data['description']),
            'consented_at' => now(), 'status' => 'new', 'retain_until' => now()->addDays(90),
        ]);
        app(PlatformMail::class)->send($user, 'request_received');
        dispatch(fn () => app(RequestNotifications::class)->newRequest($case))->afterResponse();

        $request->session()->put('consultation_access_id', $case->id);

        return redirect()->route('consultation.track')->with('success', __('billing.request_sent'));
    }

    public function invoice(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->user_id === $request->user()->id, 404);

        return view('services.invoice', ['invoice' => $invoice->load('items')]);
    }
}
