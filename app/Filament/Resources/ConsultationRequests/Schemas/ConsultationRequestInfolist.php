<?php

namespace App\Filament\Resources\ConsultationRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\RepeatableEntry;

class ConsultationRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('السجل التقني للطلب')->description('بيانات اتصال مساندة للمراجعة؛ المتصفح واللغة بيانات يرسلها العميل ولا تثبت الهوية.')->collapsed()->columns(2)
                ->visible(fn (): bool => auth()->user()?->hasRole('super-admin') ?? false)
                ->schema([
                    TextEntry::make('technical_metadata.request_id')->label('معرّف الاستلام')->placeholder('غير مسجل')->copyable(),
                    TextEntry::make('technical_metadata.received_at_utc')->label('وقت الاستلام UTC')->placeholder('غير مسجل'),
                    TextEntry::make('technical_metadata.ip_address')->label('عنوان IP')->placeholder('غير مسجل')->copyable(),
                    TextEntry::make('technical_metadata.transport')->label('بروتوكول الاتصال')->placeholder('غير مسجل'),
                    TextEntry::make('technical_metadata.user_agent')->label('المتصفح ونظام الجهاز المعلن')->placeholder('غير مسجل')->columnSpanFull(),
                    TextEntry::make('technical_metadata.language')->label('اللغات المعلنة')->placeholder('غير مسجل'),
                    TextEntry::make('technical_metadata.notice_version')->label('نسخة إشعار الخصوصية')->placeholder('غير مسجل'),
                ]),
            Section::make('المحادثة مع صاحب الطلب')->schema([
                RepeatableEntry::make('messages')->label('الرسائل')->schema([
                    TextEntry::make('sender')->label('المرسل')->formatStateUsing(fn (string $state): string => $state === 'staff' ? 'فريق الدعم' : 'صاحب الطلب'),
                    TextEntry::make('body')->label('الرسالة')->columnSpanFull(),
                    TextEntry::make('created_at')->label('وقت الإرسال')->dateTime(),
                ]),
            ]),
            Section::make('ملخص الطلب')->columns(4)->schema([
                TextEntry::make('public_id')->label('رقم الطلب')->copyable(),
                TextEntry::make('urgency')->label('الأولوية')->badge()->formatStateUsing(fn (string $state): string => ['normal' => 'عادي', 'urgent' => 'عاجل', 'critical' => 'حرج'][$state] ?? $state)->color(fn (string $state): string => match ($state) { 'critical' => 'danger', 'urgent' => 'warning', default => 'gray' }),
                TextEntry::make('status')->label('الحالة')->badge()->formatStateUsing(fn (string $state): string => ['new' => 'جديد', 'in_review' => 'قيد المراجعة', 'contacted' => 'تم التواصل', 'closed' => 'مغلق'][$state] ?? $state),
                TextEntry::make('created_at')->label('تاريخ الاستلام')->dateTime(),
                TextEntry::make('category')->label('نوع المساعدة'),
                TextEntry::make('incident_stage')->label('مرحلة الحالة')->placeholder('غير محدد'),
                TextEntry::make('country.name_ar')->label('الدولة')->placeholder('غير محددة'),
                TextEntry::make('assignedTo.name')->label('المسؤول')->placeholder('غير معيّن'),
            ]),
            Section::make('التواصل الآمن')->columns(3)->schema([
                TextEntry::make('preferred_contact_method')->label('وسيلة التواصل'),
                TextEntry::make('contact_details')->label('بيانات التواصل')->copyable(),
                TextEntry::make('safe_contact_time')->label('الوقت الآمن للتواصل')->placeholder('غير محدد'),
            ]),
            Section::make('تفاصيل الحالة')->schema([
                TextEntry::make('description')->label('وصف الحالة')->prose()->columnSpanFull(),
            ]),
            Section::make('الخصوصية والاحتفاظ')->columns(2)->collapsed()->schema([
                TextEntry::make('consented_at')->label('وقت الموافقة')->dateTime(),
                TextEntry::make('retain_until')->label('الحذف المجدول')->dateTime()->placeholder('غير محدد'),
            ]),
        ]);
    }
}
