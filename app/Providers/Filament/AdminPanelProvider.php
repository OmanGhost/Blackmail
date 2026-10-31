<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authenticatedRoutes(function () {
                \Illuminate\Support\Facades\Route::get('/secure-evidence/{submission}', [\App\Http\Controllers\SecureEvidenceController::class,'staffShow'])->name('evidence.show');
                \Illuminate\Support\Facades\Route::get('/secure-evidence/{submission}/download', [\App\Http\Controllers\SecureEvidenceController::class,'staffDownload'])->name('evidence.download');
            })
            ->login()
            ->profile()
            ->multiFactorAuthentication(AppAuthentication::make()->recoverable(), isRequired: true)
            ->brandName(fn () => config('app.name').' — '.(app()->getLocale()==='en' ? 'Administration' : 'الإدارة'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn (): string => view('filament.language-switcher')->render())
            ->colors([
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
