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
            Section::make('طلبات المعلومات والمرفقات الآمنة')->description('لا تُعرض المحتويات هنا. افتح مراجعة الإرسالات لتسجيل الوصول وفك التشفير للمختص المخوّل فقط.')->schema([
                RepeatableEntry::make('evidenceRequests')->label('النماذج المرسلة للمستخدم')->columns(3)->schema([
                    TextEntry::make('instructions')->label('المطلوب')->columnSpanFull(),
                    TextEntry::make('kind')->label('النوع'),
                    TextEntry::make('status')->label('حالة النموذج'),
                    TextEntry::make('expires_at')->label('آخر وقت للإرسال')->dateTime(),
                ]),
            ]),
            Section::make('ملاحظات داخلية — للفريق فقط')->collapsed()->schema([
                RepeatableEntry::make('internalNotes')->label('ملاحظات المختصين')->schema([
                    TextEntry::make('author.name')->label('المختص'),
                    TextEntry::make('body')->label('الملاحظة'),
                    TextEntry::make('created_at')->label('الوقت')->dateTime(),
                ]),
            ]),
            Section::make('خطة المتابعة للمستخدم')->schema([
                RepeatableEntry::make('tasks')->label('المهام')->columns(2)->schema([
                    TextEntry::make('title')->label('الخطوة'),
                    TextEntry::make('completed_at')->label('أنجزها المستخدم في')->dateTime()->placeholder('لم تُنجز بعد'),
                ]),
            ]),
            Section::make('حساب صاحب الطلب')->schema([
                TextEntry::make('user.name')->label('المستخدم')->placeholder('زائر دون حساب'),
                TextEntry::make('user.email')->label('البريد')->placeholder('غير مرتبط بحساب'),
            ])->columns(2),
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
                TextEntry::make('request_number')->label('رقم الطلب')->copyable(),
                TextEntry::make('urgency')->label('الأولوية')->badge()->formatStateUsing(fn (string $state): string => ['normal' => 'عادي', 'urgent' => 'عاجل', 'critical' => 'حرج'][$state] ?? $state)->color(fn (string $state): string => match ($state) { 'critical' => 'danger', 'urgent' => 'warning', default => 'gray' }),
                TextEntry::make('status')->label('الحالة')->badge()->formatStateUsing(fn (string $state): string => \App\Models\ConsultationRequest::statusOptions()[$state] ?? $state),
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
            Section::make('إغلاق الطلب وتقييم المستخدم')->columns(2)->schema([
                TextEntry::make('closed_at')->label('وقت الإغلاق / الانتهاء')->dateTime()->placeholder('الطلب مفتوح'),
                TextEntry::make('closed_by_role')->label('تم الإغلاق بواسطة')->formatStateUsing(fn (string $state) => $state === 'requester' ? 'صاحب الطلب' : 'الإدارة')->placeholder('—'),
                TextEntry::make('rating')->label('تقييم المستخدم من 5')->placeholder('لم يُقيّم بعد'),
                TextEntry::make('rated_at')->label('وقت التقييم')->dateTime()->placeholder('—'),
                TextEntry::make('rating_comment')->label('تعليق المستخدم')->placeholder('لا يوجد تعليق')->columnSpanFull(),
            ]),
            Section::make('سجل حالات الطلب')->collapsed()->schema([
                RepeatableEntry::make('statusHistory')->label('التغييرات')->columns(4)->schema([
                    TextEntry::make('from_status')->label('من')->formatStateUsing(fn (string $state) => \App\Models\ConsultationRequest::statusOptions()[$state] ?? $state),
                    TextEntry::make('to_status')->label('إلى')->formatStateUsing(fn (string $state) => \App\Models\ConsultationRequest::statusOptions()[$state] ?? $state),
                    TextEntry::make('changedBy.name')->label('بواسطة')->placeholder('صاحب الطلب / النظام'),
                    TextEntry::make('created_at')->label('الوقت')->dateTime(),
                ]),
            ]),
            Section::make('الخصوصية والاحتفاظ')->columns(2)->collapsed()->schema([
                TextEntry::make('consented_at')->label('وقت الموافقة')->dateTime(),
                TextEntry::make('retain_until')->label('الحذف المجدول')->dateTime()->placeholder('غير محدد'),
            ]),
        ]);
    }
}
