<?php

namespace App\Filament\Resources\Educationals\Pages;

use App\Filament\Resources\Educationals\EducationalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEducational extends EditRecord
{
    protected static string $resource = EducationalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
