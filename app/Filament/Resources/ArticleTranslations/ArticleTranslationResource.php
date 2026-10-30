<?php

namespace App\Filament\Resources\ArticleTranslations;

use App\Filament\Resources\ArticleTranslations\Pages\CreateArticleTranslation;
use App\Filament\Resources\ArticleTranslations\Pages\EditArticleTranslation;
use App\Filament\Resources\ArticleTranslations\Pages\ListArticleTranslations;
use App\Filament\Resources\ArticleTranslations\Schemas\ArticleTranslationForm;
use App\Filament\Resources\ArticleTranslations\Tables\ArticleTranslationsTable;
use App\Models\ArticleTranslation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArticleTranslationResource extends Resource
{
    protected static ?string $model = ArticleTranslation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.content');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.article_translations');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.article_translations');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->roles()->whereIn('name', ['super-admin', 'content-manager', 'legal-reviewer'])->exists() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleTranslationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleTranslationsTable::configure($table);
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
            'index' => ListArticleTranslations::route('/'),
            'create' => CreateArticleTranslation::route('/create'),
            'edit' => EditArticleTranslation::route('/{record}/edit'),
        ];
    }
}
