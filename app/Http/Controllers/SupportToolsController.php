<?php
namespace App\Http\Controllers;

use App\Models\ConsultationRequest;
use App\Models\PrivacyRequest;
use App\Models\User;
use App\Services\PlatformMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SupportToolsController extends Controller
{
    private function current(): ConsultationRequest
    {
        return ConsultationRequest::findOrFail(session('consultation_access_id'));
    }

    public function task(Request $request, int $task)
    {
        $data = $request->validate(['completed'=>['required','boolean']]);
        DB::transaction(function () use ($data, $task): void {
            $case = ConsultationRequest::lockForUpdate()->findOrFail(session('consultation_access_id'));
            abort_if($case->isEnded(),422);
            $item = $case->tasks()->findOrFail($task);
            $item->update(['completed_at'=>$data['completed'] ? now() : null]);
        });
        return back()->with('success',__('workflow.task_saved'));
    }

    public function quickExit(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('neutral')->withHeaders(['Cache-Control'=>'no-store, private','Referrer-Policy'=>'no-referrer']);
    }

    public function privacy(Request $request)
    {
        $case = ConsultationRequest::find(session('consultation_access_id'));
        $deletions = PrivacyRequest::query()->where(function ($query) use ($request,$case) {
            $query->whereRaw('1 = 0');
            if ($request->user()) $query->orWhere('user_id',$request->user()->id);
            if ($case) $query->orWhere('consultation_request_id',$case->id);
        })->latest()->get();
        return view('support.privacy',['case'=>$case,'deletions'=>$deletions]);
    }

    public function requestDeletion(Request $request)
    {
        $data = $request->validate(['scope'=>['required','in:account,request'],'confirm'=>['accepted']]);
        DB::transaction(function () use ($data,$request): void {
            if ($data['scope']==='account') {
                abort_unless($request->user(),403);
                User::lockForUpdate()->findOrFail($request->user()->id);
                PrivacyRequest::firstOrCreate(['user_id'=>$request->user()->id,'consultation_request_id'=>null,'status'=>'pending']);
            } else {
                $case = ConsultationRequest::lockForUpdate()->findOrFail(session('consultation_access_id'));
                PrivacyRequest::firstOrCreate(['consultation_request_id'=>$case->id,'status'=>'pending'],['user_id'=>$case->user_id]);
            }
        });
        return back()->with('success',__('workflow.deletion_requested'));
    }

    public function sendContactVerification(Request $request)
    {
        $case = $this->current();
        if ($case->preferred_contact_method !== 'email') {
            return redirect()->route('consultation.track')->withErrors(['verification_email'=>__('workflow.email_contact_required')]);
        }
        $request->merge(['verification_email'=>trim((string) $request->input('verification_email',$case->contact_details))]);
        $data = $request->validate(['verification_email'=>['required','email:rfc','max:255']], [
            'verification_email.required'=>__('workflow.valid_email_required'),
            'verification_email.email'=>__('workflow.valid_email_required'),
            'verification_email.max'=>__('workflow.valid_email_required'),
        ]);
        if ($data['verification_email'] !== $case->contact_details) {
            $case->update(['contact_details_encrypted'=>Crypt::encryptString($data['verification_email']),'contact_verified_at'=>null]);
        }
        $url = URL::temporarySignedRoute('consultation.verify-contact',now()->addHour(),['consultationRequest'=>$case->id,'hash'=>hash('sha256',strtolower(trim($case->contact_details)))]);
        $sent = app(PlatformMail::class)->sendTo($case->contact_details,$case->user?->name ?? __('workflow.requester'),'verify_contact',$url,$case->user_id);
        return back()->with('success',__($sent ? 'workflow.verification_sent' : 'workflow.mail_unavailable'));
    }

    public function verifyContact(Request $request, ConsultationRequest $consultationRequest, string $hash)
    {
        abort_unless($consultationRequest->preferred_contact_method==='email' && hash_equals(hash('sha256',strtolower(trim($consultationRequest->contact_details))),$hash),403);
        $consultationRequest->update(['contact_verified_at'=>now()]);
        $request->session()->regenerate();
        $request->session()->put('consultation_access_id',$consultationRequest->id);
        return redirect()->route('consultation.track')->with('success',__('workflow.email_verified'));
    }

    public function sendMemberVerification(Request $request)
    {
        $user = $request->user();
        $url = URL::temporarySignedRoute('account.verify-email',now()->addHour(),['user'=>$user->id,'hash'=>hash('sha256',strtolower($user->email))]);
        $sent = app(PlatformMail::class)->send($user,'verify_contact',$url);
        return back()->with('success',__($sent ? 'workflow.verification_sent' : 'workflow.mail_unavailable'));
    }

    public function verifyMember(Request $request, User $user, string $hash)
    {
        abort_unless(hash_equals(hash('sha256',strtolower($user->email)),$hash),403);
        $user->forceFill(['email_verified_at'=>now()])->save();
        return redirect()->route('login')->with('status',__('workflow.email_verified'));
    }

    public function recoveryForm() { return view('support.recover'); }

    public function recover(Request $request)
    {
        $data = $request->validate(['number'=>['required','digits_between:6,15'],'email'=>['required','email','max:255']]);
        $case = ConsultationRequest::find((int) $data['number']-100000);
        $email = strtolower(trim($data['email']));
        $verified = $case && (($case->contact_verified_at && $case->preferred_contact_method==='email' && strtolower(trim($case->contact_details))===$email)
            || ($case->user?->email_verified_at && strtolower($case->user->email)===$email));
        if ($verified) {
            $token = Str::random(64);
            $tokenId = DB::table('request_recovery_tokens')->insertGetId(['consultation_request_id'=>$case->id,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addMinutes(30),'created_at'=>now(),'updated_at'=>now()]);
            $sent = app(PlatformMail::class)->sendTo($email,$case->user?->name ?? __('workflow.requester'),'access_recovery',route('consultation.recovery.open',['token'=>$token]),$case->user_id);
            if (! $sent) DB::table('request_recovery_tokens')->where('id',$tokenId)->delete();
        }
        return back()->with('success',__('workflow.recovery_sent'));
    }

    public function openRecovery(Request $request, string $token)
    {
        DB::transaction(function () use ($request,$token): void {
            $row = DB::table('request_recovery_tokens')->where('token_hash',hash('sha256',$token))->whereNull('used_at')->where('expires_at','>',now())->lockForUpdate()->first();
            abort_unless($row,403,__('workflow.link_expired'));
            DB::table('request_recovery_tokens')->where('id',$row->id)->update(['used_at'=>now()]);
            $request->session()->regenerate();
            $request->session()->put('consultation_access_id',$row->consultation_request_id);
        });
        return redirect()->route('consultation.track');
    }
}
