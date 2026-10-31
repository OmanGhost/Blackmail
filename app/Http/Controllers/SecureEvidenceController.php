<?php
namespace App\Http\Controllers;

use App\Models\EvidenceRequest;
use App\Models\EvidenceSubmission;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecureEvidenceController extends Controller
{
    private function authorizeRequester(EvidenceRequest $item): void
    {
        abort_unless((int) session('consultation_access_id')===$item->consultation_request_id || (auth()->check() && $item->consultationRequest->user_id===auth()->id()),404);
    }

    private function authorizeStaff(EvidenceSubmission $submission): void
    {
        $user=auth()->user();
        $item=$submission->evidenceRequest;
        abort_unless($user?->is_active && ($user->hasRole('super-admin') || ($user->hasRole('security-consultant') && ($item->requested_by===$user->id || $item->consultationRequest->assigned_to===$user->id))),403);
    }

    public function submit(Request $request, EvidenceRequest $evidenceRequest)
    {
        $this->authorizeRequester($evidenceRequest);
        if (! $request->isSecure() && app()->isProduction()) throw ValidationException::withMessages(['evidence'=>'يجب استخدام اتصال HTTPS قبل إرسال البيانات.']);
        $rules=['consent'=>['accepted'],'no_secrets'=>['accepted'],'secure_body'=>['nullable','string','max:4000']];
        if ($evidenceRequest->kind==='account_info') {
            $rules['secure_body']=['required','string','min:3','max:4000'];
            $rules['file']=['prohibited'];
        } else {
            $rules['file']=['required','file','max:20480',$evidenceRequest->kind==='image' ? 'mimes:jpg,jpeg,png,webp' : 'mimes:mp4,webm',$evidenceRequest->kind==='image' ? 'extensions:jpg,jpeg,png,webp' : 'extensions:mp4,webm'];
        }
        $data=$request->validate($rules,['consent.accepted'=>'يجب الموافقة على طريقة التخزين والوصول قبل الإرسال.','no_secrets.accepted'=>'أكد أن المحتوى لا يتضمن كلمات مرور أو رموز تحقق أو محتوى حميميًا.']);
        $path=null;
        try {
            DB::transaction(function () use ($request,$evidenceRequest,$data,&$path): void {
                $case=ConsultationRequest::lockForUpdate()->findOrFail($evidenceRequest->consultation_request_id);
                $item=EvidenceRequest::lockForUpdate()->findOrFail($evidenceRequest->id);
                if (! $item->isOpen() || $case->isEnded()) throw ValidationException::withMessages(['evidence'=>'طلب المرفقات مغلق أو منتهي الصلاحية.']);
                if ($item->submissions()->whereNull('withdrawn_at')->count()>=5) throw ValidationException::withMessages(['evidence'=>'الحد الأقصى خمسة إرسالات لكل طلب مرفقات.']);
                $fields=['body'=>$data['secure_body'] ?? null,'consented_at'=>now()];
                if ($request->hasFile('file')) {
                    $file=$request->file('file');
                    $ciphertext=Crypt::encryptString(file_get_contents($file->getRealPath()));
                    $path=Str::uuid().'.enc';
                    Storage::disk('secure_evidence')->put($path,$ciphertext);
                    $name=basename(str_replace('\\','/',$file->getClientOriginalName()));
                    $fields += ['file_path'=>$path,'original_name'=>mb_substr(preg_replace('/[\x00-\x1F\x7F]/','',$name),0,180),'mime'=>$file->getMimeType(),'size'=>$file->getSize(),'ciphertext_sha256'=>hash('sha256',$ciphertext)];
                }
                $item->submissions()->create($fields);
            });
        } catch (Throwable $error) {
            if ($path) Storage::disk('secure_evidence')->delete($path);
            throw $error;
        }
        $request->session()->put('consultation_access_id',$evidenceRequest->consultation_request_id);
        return redirect()->route('consultation.track')->with('tracking_panel','files')->with('success','تم حفظ الإرسال مشفّرًا وإتاحته للمختص المخوّل.');
    }

    public function withdraw(Request $request, EvidenceSubmission $submission)
    {
        $this->authorizeRequester($submission->evidenceRequest);
        DB::transaction(function () use ($submission): void {
            $submission=EvidenceSubmission::lockForUpdate()->findOrFail($submission->id);
            $path=$submission->file_path;
            $submission->update(['body'=>null,'file_path'=>null,'original_name'=>null,'ciphertext_sha256'=>null,'withdrawn_at'=>now()]);
            if ($path) DB::afterCommit(fn()=>Storage::disk('secure_evidence')->delete($path));
        });
        return redirect()->route('consultation.track')->with('tracking_panel','files')->with('success','تم سحب البيانات وحذف الملف المخزّن. قد تبقى نسخة سبق تنزيلها لدى المختص.');
    }

    public function staffShow(Request $request, EvidenceSubmission $submission)
    {
        abort_if(app()->isProduction() && ! $request->isSecure(),403,'يلزم HTTPS لفتح البيانات الخاصة');
        $this->authorizeStaff($submission);
        abort_unless($submission->isAvailable(),410);
        $this->log($submission,'view');
        return response()->view('filament.evidence-review',['submission'=>$submission,'case'=>$submission->evidenceRequest->consultationRequest])->withHeaders($this->headers());
    }

    public function staffDownload(Request $request, EvidenceSubmission $submission)
    {
        abort_if(app()->isProduction() && ! $request->isSecure(),403,'يلزم HTTPS لتنزيل البيانات الخاصة');
        $this->authorizeStaff($submission);
        abort_unless($submission->isAvailable() && $submission->file_path,404);
        $ciphertext=Storage::disk('secure_evidence')->get($submission->file_path);
        abort_unless(hash_equals($submission->ciphertext_sha256,hash('sha256',$ciphertext)),409,'تعذر التحقق من سلامة الملف');
        $plaintext=Crypt::decryptString($ciphertext);
        $this->log($submission,'download');
        return response()->streamDownload(function () use ($plaintext) { echo $plaintext; },$submission->original_name,$this->headers()+['Content-Type'=>'application/octet-stream']);
    }

    private function log(EvidenceSubmission $submission,string $action): void
    {
        DB::table('evidence_access_logs')->insert(['evidence_submission_id'=>$submission->id,'user_id'=>auth()->id(),'action'=>$action,'created_at'=>now(),'updated_at'=>now()]);
    }
    private function headers(): array
    {
        return ['Cache-Control'=>'no-store, private','Referrer-Policy'=>'no-referrer','X-Content-Type-Options'=>'nosniff','X-Robots-Tag'=>'noindex, nofollow'];
    }
}
