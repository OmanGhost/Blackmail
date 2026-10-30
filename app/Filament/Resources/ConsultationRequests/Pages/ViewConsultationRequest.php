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
                    if ($this->record->user) {
                        app(\App\Services\PlatformMail::class)->send($this->record->user, 'request_replied');
                    }
                    Notification::make()->title('تم إرسال الرد إلى صفحة متابعة صاحب الطلب')->success()->send();
                }),
            EditAction::make(), DeleteAction::make(),
        ];
    }
}
