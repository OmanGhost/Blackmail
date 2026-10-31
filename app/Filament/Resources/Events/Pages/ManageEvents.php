<?php
namespace App\Filament\Resources\Events\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Events\EventResource;
class ManageEvents extends ManageRecords
{
    protected static string $resource=EventResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
