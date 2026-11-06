<?php

namespace App\Filament\Resources\ServiceSections\Pages;

use App\Filament\Resources\ServiceSections\ServiceSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceSections extends ManageRecords
{
    protected static string $resource = ServiceSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
