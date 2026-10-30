<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function create(): View
    {
        return $this->formView(false);
    }

    public function emergency(): View
    {
        return $this->formView(true);
    }

    private function formView(bool $emergency): View
    {
        return view('consultation.create', [
            'countries' => Country::orderBy('name_ar')->get(),
            'emergency' => $emergency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:account,blackmail,privacy,evidence,other'], 'country_id' => ['nullable', 'exists:countries,id'],
            'urgency' => ['nullable', 'in:normal,urgent,critical'], 'incident_stage' => ['nullable', 'in:threatening,published,payment_requested,account_compromised,other'],
            'preferred_contact_method' => ['required', 'in:email,phone'], 'contact_details' => ['required', 'string', 'max:255'],
            'safe_contact_time' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:20', 'max:3000'], 'consent' => ['accepted'], 'website' => ['nullable', 'max:0'],
        ]);
        $publicId = (string) Str::uuid();
        $tracking = Str::upper(Str::random(12));
        ConsultationRequest::create([
            'technical_metadata' => [
                'request_id' => (string) Str::uuid(),
                'received_at_utc' => now()->utc()->toIso8601String(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1024, ''),
                'language' => Str::limit((string) $request->header('Accept-Language'), 255, ''),
                'transport' => $request->isSecure() ? 'HTTPS' : 'HTTP',
                'notice_version' => 'technical-metadata-v1',
            ],
            'public_id' => $publicId, 'tracking_hash' => hash('sha256', $tracking), 'category' => $data['category'], 'urgency' => $data['urgency'] ?? 'normal', 'incident_stage' => $data['incident_stage'] ?? null, 'country_id' => $data['country_id'] ?? null,
            'preferred_contact_method' => $data['preferred_contact_method'], 'contact_details_encrypted' => Crypt::encryptString($data['contact_details']),
            'safe_contact_time' => $data['safe_contact_time'] ?? null, 'description_encrypted' => Crypt::encryptString($data['description']), 'consented_at' => now(), 'status' => 'new', 'retain_until' => now()->addDays(90),
        ]);

        return redirect()->route('consultation.success')->with('tracking_code', $tracking);
    }

    public function success(): View
    {
        abort_unless(session()->has('tracking_code'), 404);

        return view('consultation.success');
    }

    public function showTracking(): View
    {
        $case = ConsultationRequest::find(session('consultation_access_id'));
        return view('consultation.track', ['case' => $case, 'searched' => (bool) $case]);
    }

    public function track(Request $request): View
    {
        $data = $request->validate(['tracking_code' => ['required', 'string', 'size:12']]);
        $case = ConsultationRequest::where('tracking_hash', hash('sha256', Str::upper($data['tracking_code'])))->first();
        $request->session()->forget('consultation_access_id');
        if ($case) {
            $request->session()->regenerate();
            $request->session()->put('consultation_access_id', $case->id);
        }

        return view('consultation.track', ['case' => $case, 'searched' => true]);
    }

    public function reply(Request $request): RedirectResponse
    {
        $case = ConsultationRequest::findOrFail(session('consultation_access_id'));
        abort_if($case->status === 'closed', 422, 'الطلب مغلق');
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:5000']]);
        $case->messages()->create(['sender' => 'requester', 'body' => $data['body']]);
        $case->update(['status' => 'in_review']);
        return redirect()->route('consultation.track')->with('success', 'تم إرسال رسالتك للفريق');
    }
}
