<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Services\RequestNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
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
            'countries' => Country::with(['reportingAuthorities' => fn ($query) => $query
                ->where('is_published', true)->where('verification_status', 'verified')])
                ->orderBy(app()->getLocale() === 'en' ? 'name_en' : 'name_ar')->get(),
            'emergency' => $emergency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_type' => ['nullable', 'in:consultation,support'],
            'category' => ['required', 'in:account,blackmail,privacy,evidence,other'], 'country_id' => ['nullable', 'exists:countries,id'],
            'urgency' => ['nullable', 'in:normal,urgent,critical'], 'incident_stage' => ['nullable', 'in:threatening,published,payment_requested,account_compromised,other'],
            'preferred_contact_method' => ['required', 'in:email,phone'], 'contact_details' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::when($request->input('preferred_contact_method') === 'email', ['email:rfc'])],
            'safe_contact_time' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:20', 'max:3000'], 'consent' => ['accepted'], 'website' => ['nullable', 'max:0'],
        ], ['contact_details.email'=>__('workflow.valid_email_required')]);
        $publicId = (string) Str::uuid();
        $tracking = Str::upper(Str::random(12));
        $case = ConsultationRequest::create([
            'service_type' => $data['service_type'] ?? 'consultation',
            'user_id' => $request->user()?->id,
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

        $request->session()->put('consultation_access_id', $case->id);
        if ($case->user) {
            app(\App\Services\PlatformMail::class)->send($case->user, 'request_received');
        }
        // Sent after the response so SMTP delays never slow the requester down.
        dispatch(fn () => app(RequestNotifications::class)->newRequest($case))->afterResponse();
        return redirect()->route('consultation.success')->with('tracking_code', $tracking)->with('request_number', $case->request_number);
    }

    public function success(): View
    {
        abort_unless(session()->has('tracking_code'), 404);

        $case = ConsultationRequest::findOrFail(session('consultation_access_id'));
        return view('consultation.success', ['case' => $case, 'accessLink' => $this->accessLink($case)]);
    }

    public function showTracking(Request $request): View
    {
        $case = ConsultationRequest::find(session('consultation_access_id'));
        return $this->trackingView($request, $case, (bool) $case);
    }

    public function track(Request $request): View
    {
        $request->merge(['tracking_code' => Str::upper(preg_replace('/\s+/', '', (string) $request->input('tracking_code')))]);
        $data = $request->validate(['tracking_code' => ['required', 'string', 'size:12']], [
            'tracking_code.required' => 'ألصق رمز الدخول السري الذي ظهر عند إرسال الطلب.',
            'tracking_code.size' => 'هذا الحقل للرمز السري المكون من 12 حرفًا ورقمًا. إذا لديك حساب، اختر طلبك من القائمة دون إدخال رمز.',
        ]);
        $case = ConsultationRequest::where('tracking_hash', hash('sha256', Str::upper($data['tracking_code'])))->first();
        $request->session()->forget('consultation_access_id');
        if ($case) {
            $request->session()->regenerate();
            $request->session()->put('consultation_access_id', $case->id);
        }

        return $this->trackingView($request, $case, true);
    }

    private function accessLink(ConsultationRequest $case): string
    {
        return URL::temporarySignedRoute('consultation.open', now()->addDays(30), ['consultationRequest' => $case->id]);
    }

    private function trackingView(Request $request, ?ConsultationRequest $case, bool $searched): View
    {
        $panels=['conversation','files','plan','details','timeline','access','finish'];
        $panel=$request->query('panel',session('tracking_panel', $case?->isEnded() && $case->rating===null ? 'finish':'conversation'));
        if (! in_array($panel,$panels,true)) $panel='conversation';
        $errors=session('errors');
        if ($errors) {
            if ($errors->hasAny(['file','secure_body','consent','no_secrets','evidence'])) $panel='files';
            elseif ($errors->has('verification_email')) $panel='access';
            elseif ($errors->hasAny(['rating','comment','status'])) $panel='finish';
            elseif ($errors->has('body')) $panel='conversation';
        }
        if ($case) {
            $kinds=['status'];
            $kind=['conversation'=>'message','plan'=>'task','files'=>'evidence'][$panel] ?? null;
            if ($kind) $kinds[]=$kind;
            $case->updates()->whereNull('read_at')->whereIn('kind',$kinds)->update(['read_at'=>now()]);
        }
        return view('consultation.track', [
            'case' => $case, 'searched' => $searched,
            'panel'=>$panel,
            'memberRequests' => $request->user()?->consultationRequests()->latest()->get() ?? collect(),
            'accessLink' => $case ? $this->accessLink($case) : null,
        ]);
    }

    public function openLink(Request $request, ConsultationRequest $consultationRequest): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put('consultation_access_id', $consultationRequest->id);
        return redirect()->route('consultation.track');
    }

    public function forget(Request $request): RedirectResponse
    {
        $request->session()->forget('consultation_access_id');
        return redirect()->route('consultation.track');
    }

    public function reply(Request $request): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:5000']]);
        $case = DB::transaction(function () use ($data) {
            $case = ConsultationRequest::lockForUpdate()->findOrFail(session('consultation_access_id'));
            abort_if($case->isEnded(), 422, 'الطلب منتهٍ أو مغلق');
            $case->messages()->create(['sender' => 'requester', 'body' => $data['body']]);
            $case->update(['status' => 'in_review']);
            return $case;
        });
        dispatch(fn () => app(RequestNotifications::class)->requesterReplied($case))->afterResponse();
        return redirect()->route('consultation.track')->with('success', 'تم إرسال رسالتك للفريق');
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:completed,closed']]);
        DB::transaction(function () use ($data) {
            $case = ConsultationRequest::lockForUpdate()->findOrFail(session('consultation_access_id'));
            if (! $case->isEnded()) {
                $case->update(['status' => $data['status'], 'closed_by_role' => 'requester']);
            }
        });
        return redirect()->route('consultation.track')->with('success', 'تم إنهاء الطلب. يسعدنا تقييمك للخدمة.');
    }

    public function rate(Request $request): RedirectResponse
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($data) {
            $case = ConsultationRequest::lockForUpdate()->findOrFail(session('consultation_access_id'));
            abort_unless($case->isEnded(), 422, 'التقييم متاح بعد إنهاء الطلب فقط');
            abort_if($case->rating !== null, 422, 'تم تقييم هذا الطلب مسبقًا');
            $case->update(['rating' => $data['rating'], 'rating_comment' => $data['comment'] ?? null, 'rated_at' => now()]);
        });
        return redirect()->route('consultation.track')->with('success', 'شكرًا لك، تم حفظ تقييمك للخدمة.');
    }

    public function memberRequest(Request $request, ConsultationRequest $consultationRequest): RedirectResponse
    {
        abort_unless($consultationRequest->user_id === $request->user()->id, 404);
        $request->session()->put('consultation_access_id', $consultationRequest->id);
        return redirect()->route('consultation.track');
    }
}
