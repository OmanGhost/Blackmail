<?php

namespace App\Filament\Resources\MailSettings\Pages;

use App\Filament\Resources\MailSettings\MailSettingResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\PlatformMail;

class ManageMailSettings extends ManageRecords
{
    protected static string $resource = MailSettingResource::class;
    protected function getHeaderActions(): array
    {
        return [Action::make('test')->label('إرسال رسالة اختبار إلى بريدي')->action(function () {
            $sent = app(PlatformMail::class)->send(auth()->user(), 'general', 'رسالة اختبار لإعدادات البريد في المنصة.');
            $notification = Notification::make()->title($sent ? 'تم تسليم الرسالة لخادم SMTP' : 'تعذر الإرسال؛ راجع الإعدادات وتفعيل القالب');
            ($sent ? $notification->success() : $notification->danger())->send();
        })];
    }
}
