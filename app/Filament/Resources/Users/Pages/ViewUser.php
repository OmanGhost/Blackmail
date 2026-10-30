<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\EmailTemplate;
use App\Services\PlatformMail;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('send_email')->label('إرسال بريد')->icon('heroicon-o-envelope')
                ->schema([
                    Select::make('template')->label('القالب')->options(fn () => EmailTemplate::where('is_active', true)->pluck('name', 'key'))->required(),
                    Textarea::make('message')->label('نص الرسالة الإضافي')->helperText('يظهر مكان {{message}} في القالب.')->maxLength(5000)->rows(6),
                ])->requiresConfirmation()->modalDescription('سيتم إرسال القالب المحدد إلى بريد هذا المستخدم.')
                ->action(function (array $data): void {
                    $sent = app(PlatformMail::class)->send($this->record, $data['template'], $data['message'] ?? '');
                    $notification = Notification::make()->title($sent ? 'تم تسليم البريد لخادم الإرسال' : 'لم يتم الإرسال؛ راجع تفعيل البريد وإعدادات SMTP والقالب');
                    ($sent ? $notification->success() : $notification->danger())->send();
                }),
        ];
    }
}
