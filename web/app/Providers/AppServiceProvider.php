<?php

namespace App\Providers;

use App\Models\User;
use App\Stations\Providers\BenzinpreisProvider;
use App\Stations\Providers\StationDataProvider;
use App\Support\LocalEnvironment;
use App\Tenancy\PartnerContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Vor jedem Anwendungsstart die isolierte lokale Datenbank erzwingen.
     *
     * Erst nach dieser Fehlkonfigurationssperre wird der Partnerkontext als
     * kurzlebiger scoped Service registriert. Er besitzt keinen Standard-
     * partner, damit fehlende Autorisierung nicht durch einen Fallback
     * verdeckt wird. Kein globaler Superadmin-Policy-Bypass wird eingerichtet.
     */
    public function register(): void
    {
        LocalEnvironment::validate([
            'enabled' => config('nova.local_only'),
            'environment' => config('app.env'),
            'connection' => config('database.default'),
            ...config('database.connections.mysql'),
        ]);
        $this->app->scoped(PartnerContext::class);
        // Nutzerinitiierter Adapter auf ausdrücklichen Auftrag. Anbieterrechte
        // bleiben gesonderter Prüfbedarf; kein automatisches Hintergrundpolling.
        $this->app->bind(StationDataProvider::class, BenzinpreisProvider::class);
    }

    /**
     * Eng begrenztes Plattformrecht statt globalem Policy-Bypass registrieren.
     */
    public function boot(): void
    {
        Gate::define('manage-billing', fn (User $user): bool => $user->is_active && $user->is_super_admin && $user->hasVerifiedEmail());
        Gate::define('manage-mail', fn (User $user): bool => $user->is_active && $user->is_super_admin && $user->hasVerifiedEmail());
    }
}
