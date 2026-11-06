<?php

namespace App\Filament\Resources\Services;

use App\Models\Service;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->roles()->whereIn('name', ['super-admin', 'content-manager'])->exists() ?? false;
    }

    public static function getNavigationGroup(): ?string { return __('billing.billing'); }
    public static function getNavigationLabel(): string { return __('billing.services'); }
    public static function getModelLabel(): string { return __('billing.service_singular'); }
    public static function getPluralModelLabel(): string { return __('billing.services'); }

    public static function form(Schema $schema): Schema
    {
        $fields = [
            Select::make('service_section_id')->label(__('billing.section'))->relationship('section', app()->getLocale() === 'en' ? 'name_en' : 'name_ar')->required()->preload(),
            TextInput::make('slug')->label(__('billing.slug'))->required()->regex('/^[a-z0-9-]+$/')->maxLength(100)->unique(ignoreRecord: true),
            TextInput::make('price_minor')->label(__('billing.price').' (OMR)')->required()->helperText(__('billing.price_hint'))
                ->regex('/^\d{1,9}(\.\d{1,3})?$/')
                ->formatStateUsing(fn ($state) => $state === null ? null : Money::decimal((int) $state))
                ->dehydrateStateUsing(fn ($state) => Money::toMinor($state)),
            TextInput::make('sort_order')->label(__('billing.sort'))->numeric()->minValue(0)->maxValue(65535)->default(0)->required(),
        ];
        foreach (['ar', 'en'] as $locale) {
            $suffix = ' ('.__('billing.'.$locale).')';
            $fields[] = TextInput::make('name_'.$locale)->label(__('billing.name').$suffix)->required($locale === 'ar')->maxLength(255);
            $fields[] = TextInput::make('duration_'.$locale)->label(__('billing.duration').$suffix)->placeholder(__('billing.duration_hint'))->maxLength(255);
            $fields[] = Textarea::make('summary_'.$locale)->label(__('billing.summary').$suffix)->required($locale === 'ar')->maxLength(500)->rows(2)->columnSpanFull();
            $fields[] = Textarea::make('details_'.$locale)->label(__('billing.details').$suffix)->helperText(__('billing.details_hint'))->maxLength(20000)->rows(10)->columnSpanFull();
        }
        $fields[] = Toggle::make('is_active')->label(__('billing.active'))->default(true);

        return $schema->components($fields);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            TextColumn::make(app()->getLocale() === 'en' ? 'name_en' : 'name_ar')->label(__('billing.name'))->searchable(['name_ar', 'name_en'])->limit(50),
            TextColumn::make('section.'.(app()->getLocale() === 'en' ? 'name_en' : 'name_ar'))->label(__('billing.section')),
            TextColumn::make('price_minor')->label(__('billing.price'))->formatStateUsing(fn ($state, Service $record) => $record->formattedPrice())->sortable(),
            IconColumn::make('is_active')->label(__('billing.active'))->boolean(),
        ])->filters([SelectFilter::make('service_section_id')->label(__('billing.section'))->relationship('section', 'name_ar')])
            ->recordActions([
                EditAction::make()->slideOver(),
                Action::make('public')->label(__('billing.public_page'))->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Service $record) => route('services.show', $record))->openUrlInNewTab()->visible(fn (Service $record) => $record->is_active),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageServices::route('/')];
    }
}
