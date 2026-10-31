<?php

namespace App\Filament\Resources\ReportingAuthorities\Pages;

use App\Filament\Resources\ReportingAuthorities\ReportingAuthorityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReportingAuthority extends CreateRecord
{
    use \App\Filament\Concerns\ReviewsDirectoryRecords;
    protected static string $resource = ReportingAuthorityResource::class;
}
