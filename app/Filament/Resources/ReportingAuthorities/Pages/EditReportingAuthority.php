<?php

namespace App\Filament\Resources\ReportingAuthorities\Pages;

use App\Filament\Resources\ReportingAuthorities\ReportingAuthorityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportingAuthority extends EditRecord
{
    protected static string $resource = ReportingAuthorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
