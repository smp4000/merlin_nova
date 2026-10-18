<?php

namespace App\Providers;

use App\Support\LocalEnvironment;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LocalEnvironment::validate([
            'enabled' => config('nova.local_only'),
            'environment' => config('app.env'),
            'connection' => config('database.default'),
            ...config('database.connections.mysql'),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
