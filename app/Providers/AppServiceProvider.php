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
        foreach ([TextInput::class, Textarea::class, Select::class, Toggle::class, DatePicker::class, DateTimePicker::class, FileUpload::class, KeyValue::class, TextColumn::class, IconColumn::class] as $component) {
            $component::configureUsing(fn ($field) => $field->translateLabel());
        }
    }
}
