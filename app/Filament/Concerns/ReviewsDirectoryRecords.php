<?php
namespace App\Filament\Concerns;
trait ReviewsDirectoryRecords
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $model=static::getModel();
        return \App\Services\DirectoryVerification::prepare(new $model,$data);
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return \App\Services\DirectoryVerification::prepare($this->getRecord(),$data);
    }
}
