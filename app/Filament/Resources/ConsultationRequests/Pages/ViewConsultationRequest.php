<?php

namespace App\Filament\Resources\ConsultationRequests\Pages;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultationRequest extends ViewRecord
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('request_evidence')->label('طلب معلومات أو مرفقات آمنة')->icon('heroicon-o-shield-check')
                ->visible(fn () => ! $this->record->isEnded() && (auth()->user()?->hasRole('super-admin') || auth()->user()?->hasRole('security-consultant')))
                ->schema([
                    Select::make('kind')->label('نوع المطلوب')->options(['account_info'=>'روابط الحساب ومعلومات الاسترداد غير السرية','image'=>'صورة أو لقطة شاشة منقّحة','video'=>'مقطع فيديو غير حميمي'])->required(),
                    Textarea::make('instructions')->label('ما المطلوب ولماذا نحتاجه؟')->helperText('لا تطلب كلمات مرور أو رموز تحقق أو صورًا حميمية. يمكن للمستخدم رفض الإرسال.')->required()->maxLength(2000)->rows(5),
                ])->action(function (array $data): void {
                    abort_unless(auth()->user()?->hasRole('super-admin') || auth()->user()?->hasRole('security-consultant'),403);
                    DB::transaction(function () use ($data) {
                        $case=\App\Models\ConsultationRequest::lockForUpdate()->findOrFail($this->record->id);
                        abort_if($case->isEnded(),422);
                        $case->evidenceRequests()->create(['kind'=>$data['kind'],'instructions'=>$data['instructions'],'requested_by'=>auth()->id(),'expires_at'=>now()->addDays(7)->min($case->retain_until ?? now()->addDays(7))]);
                        $case->updates()->create(['kind'=>'evidence']);
                    });
                    $this->record->refresh();
                    Notification::make()->title('ظهر نموذج الإرسال الآمن للمستخدم')->success()->send();
                }),
            Action::make('review_evidence')->label('مراجعة الإرسالات الآمنة')->icon('heroicon-o-lock-closed')
                ->visible(fn () => auth()->user()?->hasRole('super-admin') || auth()->user()?->hasRole('security-consultant'))
                ->schema([Select::make('submission_id')->label('اختر الإرسال')->options(fn ()=>\App\Models\EvidenceSubmission::whereHas('evidenceRequest',fn ($q)=>$q->where('consultation_request_id',$this->record->id))->whereNull('withdrawn_at')->get()->mapWithKeys(fn ($s)=>[$s->id=>'إرسال '.$s->id.' — '.$s->created_at->format('Y-m-d H:i')])->all())->required()])
                ->action(function (array $data) {
                    $submission=\App\Models\EvidenceSubmission::whereHas('evidenceRequest',fn ($q)=>$q->where('consultation_request_id',$this->record->id))->findOrFail($data['submission_id']);
                    return redirect()->route('filament.admin.evidence.show',['submission'=>$submission]);
                }),
            Action::make('close_evidence')->label('إغلاق نموذج مرفقات')
                ->visible(fn () => auth()->user()?->hasRole('super-admin') || auth()->user()?->hasRole('security-consultant'))
                ->schema([Select::make('evidence_id')->label('النموذج')->options(fn ()=>$this->record->evidenceRequests()->where('status','open')->get()->mapWithKeys(fn ($r)=>[$r->id=>$r->instructions])->all())->required()])
                ->action(function (array $data): void {
                    abort_unless(auth()->user()?->hasRole('super-admin') || auth()->user()?->hasRole('security-consultant'),403);
                    $this->record->evidenceRequests()->findOrFail($data['evidence_id'])->update(['status'=>'closed']);
                    $this->record->refresh();
                }),
            Action::make('add_task')->label('إضافة خطوة متابعة')->icon('heroicon-o-clipboard-document-check')
                ->visible(fn () => ! $this->record->isEnded())
                ->schema([Textarea::make('title')->label('الخطوة المطلوبة من المستخدم')->required()->maxLength(1000)->rows(3)])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        $case = \App\Models\ConsultationRequest::lockForUpdate()->findOrFail($this->record->id);
                        abort_if($case->isEnded(),422);
                        $case->tasks()->create(['title'=>$data['title'],'created_by'=>auth()->id()]);
                    });
                    $this->record->refresh();
                    Notification::make()->title('أُضيفت الخطوة إلى خطة المستخدم')->success()->send();
                }),
            Action::make('internal_note')->label('ملاحظة داخلية')->icon('heroicon-o-lock-closed')
                ->schema([Textarea::make('body')->label('ملاحظة للفريق فقط؛ لا تظهر للمستخدم')->required()->maxLength(5000)->rows(6)])
                ->action(function (array $data): void {
                    $this->record->internalNotes()->create(['user_id'=>auth()->id(),'body'=>$data['body']]);
                    $this->record->refresh();
                    Notification::make()->title('حُفظت الملاحظة الداخلية')->success()->send();
                }),
            Action::make('edit_task')->label('تعديل خطوة متابعة')
                ->visible(fn () => ! $this->record->isEnded() && $this->record->tasks()->exists())
                ->schema([
                    Select::make('task_id')->label('الخطوة')->options(fn ()=>$this->record->tasks->mapWithKeys(fn ($task)=>[$task->id=>$task->title])->all())->required(),
                    Textarea::make('title')->label('النص الجديد للخطوة')->required()->maxLength(1000),
                ])->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        $case = \App\Models\ConsultationRequest::lockForUpdate()->findOrFail($this->record->id);
                        abort_if($case->isEnded(),422);
                        $case->tasks()->findOrFail($data['task_id'])->update(['title'=>$data['title'],'completed_at'=>null]);
                        $case->updates()->create(['kind'=>'task']);
                    });
                    $this->record->refresh();
                    Notification::make()->title('تم تحديث الخطوة، وتحتاج تأكيد الإنجاز مجددًا')->success()->send();
                }),
            Action::make('change_status')->label('تغيير حالة الطلب')->icon('heroicon-o-adjustments-horizontal')
                ->schema([Select::make('status')->label('الحالة الجديدة')->options(\App\Models\ConsultationRequest::statusOptions())->required()])
                ->fillForm(fn () => ['status' => $this->record->status])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        $case = \App\Models\ConsultationRequest::lockForUpdate()->findOrFail($this->record->id);
                        $case->update(['status' => $data['status']]);
                    });
                    $this->record->refresh();
                    Notification::make()->title('تم تحديث حالة الطلب')->success()->send();
                }),
            Action::make('reply')->label('إرسال رد لصاحب الطلب')->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () => ! $this->record->isEnded())
                ->schema([Textarea::make('body')->label('الرد')->required()->minLength(3)->maxLength(5000)->rows(8)])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        $this->record = \App\Models\ConsultationRequest::lockForUpdate()->findOrFail($this->record->id);
                        abort_if($this->record->isEnded(), 422, 'الطلب منتهٍ أو مغلق');
                        $this->record->messages()->create(['sender' => 'staff', 'user_id' => auth()->id(), 'body' => $data['body']]);
                        $this->record->update(['status' => 'contacted', 'assigned_to' => $this->record->assigned_to ?? auth()->id()]);
                    });
                    $this->record->refresh();
                    app(\App\Services\RequestNotifications::class)->staffReplied($this->record);
                    Notification::make()->title('تم إرسال الرد إلى صفحة متابعة صاحب الطلب')->success()->send();
                }),
            EditAction::make(), DeleteAction::make(),
        ];
    }
}
