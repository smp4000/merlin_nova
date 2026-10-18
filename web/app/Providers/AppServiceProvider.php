<?php

namespace App\Providers;

use App\Support\LocalEnvironment;
use App\Tenancy\PartnerContext;
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
    }

    /**
     * Zusätzliche Startaktionen sind derzeit bewusst nicht erforderlich.
     */
    public function boot(): void
    {
        //
    }
}
