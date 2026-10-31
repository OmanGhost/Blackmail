<?php

namespace App\Filament\Resources\MailSettings;

use App\Models\MailSetting;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;

class MailSettingResource extends Resource
{
    protected static ?string $model = MailSetting::class;
    public static function canAccess(): bool { return auth()->user()?->hasRole('super-admin') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function getNavigationLabel(): string { return 'إعدادات البريد'; }
    public static function getModelLabel(): string { return 'إعدادات البريد'; }
    public static function getPluralModelLabel(): string { return 'إعدادات البريد'; }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';
    public static function getNavigationGroup(): ?string { return __('admin.system'); }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('enabled')->label('تفعيل إرسال البريد')->columnSpanFull(),
            TextInput::make('host')->label('خادم SMTP')->required()->maxLength(255),
            TextInput::make('port')->label('المنفذ')->numeric()->minValue(1)->maxValue(65535)->required(),
            Select::make('scheme')->label('نوع الاتصال')->options(['smtp' => 'SMTP / STARTTLS (587)', 'smtps' => 'TLS (465)'])->required(),
            TextInput::make('username')->label('اسم المستخدم')->maxLength(255),
            TextInput::make('password')->label('كلمة مرور SMTP')->password()->autocomplete('new-password')->afterStateHydrated(fn ($component) => $component->state(''))->dehydrated(fn ($state) => filled($state))->helperText('اتركه فارغًا للاحتفاظ بالكلمة الحالية. تُحفظ مشفّرة.'),
            TextInput::make('from_address')->label('عنوان المرسل')->email()->required()->maxLength(255),
            TextInput::make('from_name')->label('اسم المرسل')->required()->maxLength(255),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('host')->label('خادم SMTP')->placeholder('لم يُضبط بعد'),
            TextColumn::make('from_address')->label('بريد المرسل')->placeholder('غير محدد'),
            IconColumn::make('enabled')->label('الإرسال مفعّل')->boolean(),
        ])->recordActions([EditAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ManageMailSettings::route('/')]; }
}
