<?php

namespace App\Filament\Resources\Educationals;

use App\Filament\Resources\Educationals\Pages\CreateEducational;
use App\Filament\Resources\Educationals\Pages\EditEducational;
use App\Filament\Resources\Educationals\Pages\ListEducationals;
use App\Filament\Resources\Educationals\Schemas\EducationalForm;
use App\Filament\Resources\Educationals\Tables\EducationalsTable;
use App\Models\EducationalResource as EducationalResourceModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EducationalResource extends Resource
{
    protected static ?string $model = EducationalResourceModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.content');
    }

    public static function getNavigationSort(): ?int
    {
        return 40;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.educationals');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.educationals');
    }

    public static function form(Schema $schema): Schema
    {
        return EducationalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducationalsTable::configure($table);
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
            'index' => ListEducationals::route('/'),
            'create' => CreateEducational::route('/create'),
            'edit' => EditEducational::route('/{record}/edit'),
        ];
    }
}
