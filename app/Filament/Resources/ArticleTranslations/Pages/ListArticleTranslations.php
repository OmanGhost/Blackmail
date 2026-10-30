<?php

namespace App\Filament\Resources\ArticleTranslations\Pages;

use App\Filament\Resources\ArticleTranslations\ArticleTranslationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticleTranslations extends ListRecords
{
    protected static string $resource = ArticleTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
