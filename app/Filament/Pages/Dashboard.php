<?php

namespace App\Filament\Pages;

use Illuminate\Contracts\View\View;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getHeader(): ?View
    {
        return view('filament.dashboard-header');
    }
}
