<?php
namespace App\Filament\Resources\PrivacyRequests\Pages;

use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\PrivacyRequests\PrivacyRequestResource;

class ManagePrivacyRequests extends ManageRecords
{
    protected static string $resource = PrivacyRequestResource::class;
}
