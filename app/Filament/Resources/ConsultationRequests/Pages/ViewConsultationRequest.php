<?php

namespace App\Filament\Resources\ConsultationRequests\Pages;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultationRequest extends ViewRecord
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')->label('إرسال رد لصاحب الطلب')->icon('heroicon-o-chat-bubble-left-right')
                ->schema([Textarea::make('body')->label('الرد')->required()->minLength(3)->maxLength(5000)->rows(8)])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        $this->record->messages()->create(['sender' => 'staff', 'user_id' => auth()->id(), 'body' => $data['body']]);
                        $this->record->update(['status' => 'contacted', 'assigned_to' => $this->record->assigned_to ?? auth()->id()]);
                    });
                    $this->record->refresh();
                    Notification::make()->title('تم إرسال الرد إلى صفحة متابعة صاحب الطلب')->success()->send();
                }),
            EditAction::make(), DeleteAction::make(),
        ];
    }
}
