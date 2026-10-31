<?php
namespace App\Filament\Auth;
class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    protected function afterSave(): void
    {
        \App\Support\SessionCredentials::refreshCurrent($this->getUser());
    }
}
