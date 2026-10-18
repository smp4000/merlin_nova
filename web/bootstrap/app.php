<?php

use App\Http\Middleware\RequireActiveUser;
use App\Http\Middleware\ResolvePartner;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Erst Konto und Bestätigung prüfen, dann den Partner freigeben.
        // Das anschließende Route Binding darf nur in diesem Kontext suchen.
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolvePartner::class);
        $middleware->prependToPriorityList(ResolvePartner::class, EnsureEmailIsVerified::class);
        $middleware->prependToPriorityList(EnsureEmailIsVerified::class, RequireActiveUser::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['iban', 'account_holder', 'password', 'password_confirmation']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
