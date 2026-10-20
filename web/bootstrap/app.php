<?php

use App\Http\Middleware\RequireActiveUser;
use App\Http\Middleware\ResolvePartner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SMTP-Geheimnisse dürfen durch globale Formularnormalisierung weder
        // verändert noch später als alter Formularwert in die Sitzung kopiert werden.
        $middleware->trimStrings(except: ['smtp_password']);
        // Erst Konto und Bestätigung prüfen, dann den Partner freigeben.
        // Das anschließende Route Binding darf nur in diesem Kontext suchen.
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolvePartner::class);
        $middleware->prependToPriorityList(ResolvePartner::class, EnsureEmailIsVerified::class);
        $middleware->prependToPriorityList(EnsureEmailIsVerified::class, RequireActiveUser::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['iban', 'account_holder', 'password', 'password_confirmation', 'smtp_password',
            'token', 'data', 'question', 'identity', 'employer_data', 'personal_data',
            'profile.bank_accounts', 'profile.tax_id', 'profile.vat_id', 'profile.trade_register']);
        // Fehlerprotokolle aus Personalrequests enthalten nur eine technische
        // Kategorie. Keine Exception-Argumente, Token oder entschlüsselten Modelle.
        $exceptions->report(function (Throwable $error) {
            if (request()->is('employee-invitations/*') || request()->is('app/partners/*/employees*')) {
                Log::warning('Nova-Personalaktion fehlgeschlagen', ['category' => $error::class]);

                return false;
            }
        });
        // Personalseiten dürfen auch bei lokaler Debug-Konfiguration weder
        // Eingabe-/Tokenwerte noch entschlüsselte Modelle im Fehlerbild zeigen.
        $exceptions->render(function (Throwable $error, Request $request) {
            if (! $request->is('employee-invitations/*') && ! $request->is('app/partners/*/employees*')) {
                return null;
            }
            if ($error instanceof AuthenticationException) {
                return redirect()->route('login');
            }
            $status = $error instanceof HttpExceptionInterface ? $error->getStatusCode()
                : ($error instanceof ValidationException ? 422
                    : ($error instanceof AuthorizationException ? 403
                        : ($error instanceof ModelNotFoundException ? 404 : 500)));
            $message = match ($status) {
                409 => 'Der Stand wurde inzwischen geändert oder diese Aktion ist nicht mehr möglich. Bitte neu laden.',
                403 => 'Diese Aktion ist derzeit nicht freigegeben. Bitte Rechte und Teststatus prüfen.',
                422 => 'Bitte die erforderlichen Angaben und die Bestätigung prüfen.',
                419 => 'Die Sitzung ist abgelaufen. Bitte die Seite erneut öffnen.',
                429 => 'Zu viele Versuche. Bitte etwas warten und erneut versuchen.',
                default => 'Der Zugang ist nicht verfügbar oder die Aktion konnte nicht abgeschlossen werden. Bitte den Arbeitgeber kontaktieren.',
            };

            return response()->view('employees.error', compact('message'), $status)
                ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')
                ->header('X-Frame-Options', 'DENY');
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
