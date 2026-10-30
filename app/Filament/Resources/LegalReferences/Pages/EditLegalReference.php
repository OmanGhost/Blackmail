<?php

namespace App\Filament\Resources\LegalReferences\Pages;

use App\Filament\Resources\LegalReferences\LegalReferenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLegalReference extends EditRecord
{
    protected static string $resource = LegalReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
