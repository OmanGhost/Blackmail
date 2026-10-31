<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event): void {
            $event->user->forceFill(['last_login_at' => now()])->save();
            session()->put('auth_versions.'.$event->guard, (int)$event->user->auth_version);
        });
        $this->serveLivewireFromSubfolder();
        \Illuminate\Pagination\Paginator::defaultView('pagination.site');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination.site');
        foreach ([TextInput::class, Textarea::class, Select::class, Toggle::class, DatePicker::class, DateTimePicker::class, FileUpload::class, KeyValue::class, TextColumn::class, IconColumn::class] as $component) {
            $component::configureUsing(fn ($field) => $field->translateLabel());
        }
    }

    /**
     * When the app runs under a sub-path (e.g. http://localhost/blackmail), Livewire prints
     * root-relative URLs (/livewire/...) that miss the sub-path. Point the browser at the
     * sub-path; requests still reach Livewire's default /livewire/update route inside Laravel.
     */
    private function serveLivewireFromSubfolder(): void
    {
        $base = trim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
        if ($base === '' || ! preg_match('/^[A-Za-z0-9._\/-]+$/', $base)) {
            return;
        }
        config(['livewire.asset_url' => rtrim((string) config('app.url'), '/').'/livewire/livewire.js']);
        \Livewire\Livewire::setUpdateRoute(fn ($handle) => \Illuminate\Support\Facades\Route::post('/'.$base.'/livewire/update', $handle)
            ->middleware('web')->name('subfolder.livewire.update'));
    }
}
