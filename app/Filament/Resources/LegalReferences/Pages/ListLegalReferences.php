<?php

namespace App\Filament\Resources\LegalReferences\Pages;

use App\Filament\Resources\LegalReferences\LegalReferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegalReferences extends ListRecords
{
    protected static string $resource = LegalReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
