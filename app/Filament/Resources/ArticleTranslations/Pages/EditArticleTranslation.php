<?php

namespace App\Filament\Resources\ArticleTranslations\Pages;

use App\Filament\Resources\ArticleTranslations\ArticleTranslationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticleTranslation extends EditRecord
{
    protected static string $resource = ArticleTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
