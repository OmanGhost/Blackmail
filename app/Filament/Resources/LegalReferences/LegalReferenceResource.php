<?php

namespace App\Filament\Resources\LegalReferences;

use App\Filament\Resources\LegalReferences\Pages\CreateLegalReference;
use App\Filament\Resources\LegalReferences\Pages\EditLegalReference;
use App\Filament\Resources\LegalReferences\Pages\ListLegalReferences;
use App\Filament\Resources\LegalReferences\Schemas\LegalReferenceForm;
use App\Filament\Resources\LegalReferences\Tables\LegalReferencesTable;
use App\Models\LegalReference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LegalReferenceResource extends Resource
{
    protected static ?string $model = LegalReference::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.directory');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.legal');
    }

    // Labels are already plural Arabic nouns; Filament would otherwise append an English "s".
    public static function getPluralModelLabel(): string { return static::getModelLabel(); }

    public static function getModelLabel(): string
    {
        return __('admin.resources.legal');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->roles()->whereIn('name', ['super-admin', 'legal-reviewer'])->exists() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return LegalReferenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalReferencesTable::configure($table);
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
            'index' => ListLegalReferences::route('/'),
            'create' => CreateLegalReference::route('/create'),
            'edit' => EditLegalReference::route('/{record}/edit'),
        ];
    }
}
