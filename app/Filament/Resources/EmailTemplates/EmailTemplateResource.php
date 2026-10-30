<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Models\EmailTemplate;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    public static function canAccess(): bool { return auth()->user()?->hasRole('super-admin') ?? false; }
    public static function getNavigationLabel(): string { return 'قوالب البريد'; }
    public static function getModelLabel(): string { return 'قالب البريد'; }
    public static function getPluralModelLabel(): string { return 'قوالب البريد'; }
    public static function getNavigationGroup(): ?string { return __('admin.system'); }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('المفتاح')->required()->maxLength(100)->regex('/^[a-z0-9_]+$/')->unique(ignoreRecord: true)->disabled(fn (string $operation) => $operation === 'edit'),
            TextInput::make('name')->label('اسم القالب')->required()->maxLength(255),
            TextInput::make('subject')->label('عنوان الرسالة')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('body')->label('نص الرسالة')->required()->maxLength(10000)->rows(12)->columnSpanFull()->helperText('المتغيرات: {{name}} اسم المستخدم، {{account_url}} رابط الحساب، {{message}} النص الإضافي. تُرسل كنص عادي؛ لا يتم تنفيذ أكواد القالب.'),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('القالب')->searchable(),
            TextColumn::make('key')->label('المفتاح'),
            TextColumn::make('subject')->label('العنوان'),
            IconColumn::make('is_active')->label('مفعّل')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()->visible(fn (EmailTemplate $record) => ! in_array($record->key, ['request_received', 'request_replied', 'general']))]);
    }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess() && ! in_array($record->key, ['request_received', 'request_replied', 'general']);
    }
    public static function getPages(): array { return ['index' => Pages\ManageEmailTemplates::route('/')]; }
}
