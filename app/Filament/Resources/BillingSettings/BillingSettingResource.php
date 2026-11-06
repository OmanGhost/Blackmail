<?php

namespace App\Filament\Resources\BillingSettings;

use App\Models\BillingSetting;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BillingSettingResource extends Resource
{
    protected static ?string $model = BillingSetting::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool { return auth()->user()?->hasRole('super-admin') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function getNavigationGroup(): ?string { return __('billing.billing'); }
    public static function getNavigationLabel(): string { return __('billing.settings'); }
    public static function getModelLabel(): string { return __('billing.settings'); }
    public static function getPluralModelLabel(): string { return __('billing.settings'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('issuer_name')->label(__('billing.issuer_name'))->required()->maxLength(255),
            TextInput::make('tax_number')->label(__('billing.tax_number'))->maxLength(100),
            TextInput::make('issuer_email')->label(__('billing.issuer_email'))->email()->maxLength(255),
            TextInput::make('issuer_phone')->label(__('billing.issuer_phone'))->maxLength(50),
            TextInput::make('tax_rate')->label(__('billing.tax_rate'))->numeric()->minValue(0)->maxValue(100)->step('0.01')->required(),
            TextInput::make('due_days')->label(__('billing.due_days'))->integer()->minValue(0)->maxValue(365)->required(),
            Textarea::make('issuer_address')->label(__('billing.issuer_address'))->maxLength(1000)->rows(3)->columnSpanFull(),
            Textarea::make('payment_instructions')->label(__('billing.payment_instructions'))->helperText(__('billing.payment_instructions_hint'))->maxLength(2000)->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->description(__('billing.settings_note'))->paginated(false)->columns([
            TextColumn::make('issuer_name')->label(__('billing.issuer_name')),
            TextColumn::make('tax_rate')->label(__('billing.tax_rate')),
            TextColumn::make('due_days')->label(__('billing.due_days')),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBillingSettings::route('/')];
    }
}
