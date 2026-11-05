<?php

namespace App\Filament\Resources\ReportingAuthorities;

use App\Filament\Resources\ReportingAuthorities\Pages\CreateReportingAuthority;
use App\Filament\Resources\ReportingAuthorities\Pages\EditReportingAuthority;
use App\Filament\Resources\ReportingAuthorities\Pages\ListReportingAuthorities;
use App\Filament\Resources\ReportingAuthorities\Schemas\ReportingAuthorityForm;
use App\Filament\Resources\ReportingAuthorities\Tables\ReportingAuthoritiesTable;
use App\Models\ReportingAuthority;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReportingAuthorityResource extends Resource
{
    protected static ?string $model = ReportingAuthority::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.directory');
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.authorities');
    }

    // Labels are already plural Arabic nouns; Filament would otherwise append an English "s".
    public static function getPluralModelLabel(): string { return static::getModelLabel(); }

    public static function getModelLabel(): string
    {
        return __('admin.resources.authorities');
    }

    public static function form(Schema $schema): Schema
    {
        return ReportingAuthorityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportingAuthoritiesTable::configure($table);
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
            'index' => ListReportingAuthorities::route('/'),
            'create' => CreateReportingAuthority::route('/create'),
            'edit' => EditReportingAuthority::route('/{record}/edit'),
        ];
    }
}
