<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    use \App\Filament\Concerns\ReviewsDirectoryRecords;
    protected static string $resource = CountryResource::class;
}
