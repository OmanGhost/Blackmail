<?php

namespace App\Filament\Resources\Educationals\Pages;

use App\Filament\Resources\Educationals\EducationalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEducationals extends ListRecords
{
    protected static string $resource = EducationalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
