<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.system');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.users');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.users');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('ملف المستخدم')->columns(3)->schema([
                TextEntry::make('name')->label('الاسم'),
                TextEntry::make('email')->label('البريد')->copyable(),
                TextEntry::make('roles.label_ar')->label('الأدوار')->badge(),
                IconEntry::make('is_active')->label('الحساب نشط')->boolean(),
                TextEntry::make('created_at')->label('تاريخ التسجيل')->dateTime(),
                TextEntry::make('last_login_at')->label('آخر دخول للموقع')->dateTime()->placeholder('غير مسجل'),
                TextEntry::make('email_verified_at')->label('تأكيد البريد')->dateTime()->placeholder('غير مؤكد'),
                TextEntry::make('mfa_confirmed_at')->label('تأكيد المصادقة الثنائية')->dateTime()->placeholder('غير مسجل'),
            ]),
            Section::make('طلبات المساعدة والاستشارات')->schema([
                RepeatableEntry::make('consultationRequests')->label('الطلبات المرتبطة')->columns(3)->schema([
                    TextEntry::make('request_number')->label('رقم الطلب')->url(fn ($record) => \App\Filament\Resources\ConsultationRequests\ConsultationRequestResource::getUrl('view', ['record' => $record])),
                    TextEntry::make('category')->label('نوع الخدمة'),
                    TextEntry::make('status_label')->label('الحالة')->badge(),
                    TextEntry::make('urgency')->label('الأولوية'),
                    TextEntry::make('country.name_ar')->label('الدولة')->placeholder('غير محددة'),
                    TextEntry::make('assignedTo.name')->label('الموظف المسؤول')->placeholder('غير معيّن'),
                    TextEntry::make('contact_details')->label('وسيلة التواصل المقدمة'),
                    TextEntry::make('safe_contact_time')->label('وقت التواصل الآمن')->placeholder('غير محدد'),
                    TextEntry::make('technical_metadata.ip_address')->label('عنوان IP عند تقديم الطلب')->placeholder('غير مسجل'),
                    TextEntry::make('technical_metadata.user_agent')->label('المتصفح والجهاز المعلن')->columnSpanFull()->placeholder('غير مسجل'),
                    TextEntry::make('created_at')->label('وقت الاستلام')->dateTime(),
                ]),
            ]),
            Section::make('سجل إرسال البريد')->schema([
                RepeatableEntry::make('emailDeliveries')->label('عمليات الإرسال')->columns(3)->schema([
                    TextEntry::make('template_key')->label('القالب'),
                    TextEntry::make('status')->label('نتيجة الإرسال')->badge(),
                    TextEntry::make('created_at')->label('التاريخ')->dateTime(),
                ]),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
