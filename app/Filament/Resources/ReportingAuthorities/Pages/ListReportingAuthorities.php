<?php

namespace App\Filament\Resources\ReportingAuthorities\Pages;

use App\Filament\Resources\ReportingAuthorities\ReportingAuthorityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportingAuthorities extends ListRecords
{
    protected static string $resource = ReportingAuthorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
