<?php

namespace App\Filament\Resources\ServiceSections;

use App\Models\ServiceSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceSectionResource extends Resource
{
    protected static ?string $model = ServiceSection::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->roles()->whereIn('name', ['super-admin', 'content-manager'])->exists() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && ! $record->services()->exists();
    }

    public static function getNavigationGroup(): ?string { return __('billing.billing'); }
    public static function getNavigationLabel(): string { return __('billing.sections'); }
    public static function getModelLabel(): string { return __('billing.section'); }
    public static function getPluralModelLabel(): string { return __('billing.sections'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_ar')->label(__('billing.name').' ('.__('billing.ar').')')->required()->maxLength(255),
            TextInput::make('name_en')->label(__('billing.name').' ('.__('billing.en').')')->maxLength(255),
            TextInput::make('slug')->label(__('billing.slug'))->required()->regex('/^[a-z0-9-]+$/')->maxLength(100)->unique(ignoreRecord: true),
            TextInput::make('sort_order')->label(__('billing.sort'))->numeric()->minValue(0)->maxValue(65535)->default(0)->required(),
            Textarea::make('description_ar')->label(__('billing.description').' ('.__('billing.ar').')')->maxLength(2000)->rows(3),
            Textarea::make('description_en')->label(__('billing.description').' ('.__('billing.en').')')->maxLength(2000)->rows(3),
            Toggle::make('is_active')->label(__('billing.active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->reorderable('sort_order')->columns([
            TextColumn::make(app()->getLocale() === 'en' ? 'name_en' : 'name_ar')->label(__('billing.name'))->searchable(['name_ar', 'name_en']),
            TextColumn::make('services_count')->counts('services')->label(__('billing.services_count')),
            IconColumn::make('is_active')->label(__('billing.active'))->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageServiceSections::route('/')];
    }
}
