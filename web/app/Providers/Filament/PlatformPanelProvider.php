<?php

namespace App\Providers\Filament;

use App\Filament\Pages\PlatformOverview;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Eigener Plattformbereich, ausdrücklich kein Partner-Mandantenpanel.
 *
 * Die Berechtigung wird über User::canAccessPanel geprüft, auch lokal.
 * Persistente Authentifizierung schützt zusätzlich spätere Livewire-Anfragen,
 * wenn während einer laufenden Sitzung Plattformrechte entzogen werden.
 * Hier werden keine Personalakten oder Bankdaten als Ressourcen registriert.
 */
class PlatformPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('platform')->path('superadmin')->login()
            ->brandName('Merlin Nova · Plattform')
            ->colors(['primary' => Color::Blue])
            ->defaultThemeMode(ThemeMode::Light)->darkMode(false)
            ->maxContentWidth(Width::Full)->font(null)
            ->pages([PlatformOverview::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class], isPersistent: true);
    }
}
