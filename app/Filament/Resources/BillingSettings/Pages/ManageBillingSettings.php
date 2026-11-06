<?php

namespace App\Filament\Resources\BillingSettings\Pages;

use App\Filament\Resources\BillingSettings\BillingSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBillingSettings extends ManageRecords
{
    protected static string $resource = BillingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
