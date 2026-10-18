<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Tenancy\PartnerContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Löst die Partner-ID aus der Route auf, niemals aus Formular-/Sessiondaten.
 *
 * Läuft nach Konto-/E-Mail-Prüfung und vor Route Model Binding. Dadurch kann
 * eine manipulierte Stations-ID nur innerhalb des erlaubten Partners suchen.
 * Fremde und unbekannte Firmen antworten identisch mit 404, um über diesen
 * Endpunkt keine Existenzinformationen preiszugeben. finally leert auch bei
 * Validierungsfehlern oder Exceptions den kurzlebigen Partnerkontext.
 */
class ResolvePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(PartnerContext::class);
        $context->clear();

        try {
            $identifier = (string) $request->route('partner');
            abort_unless(ctype_digit($identifier), 404);
            $partner = Partner::findOrFail($identifier);
            abort_unless(Gate::allows('view', $partner), 404);
            abort_if(! $request->isMethodSafe() && ! $partner->allowsOperationalWrites(), 403, 'Der Test ist noch nicht gestartet oder abgelaufen. Der Zugriff ist nur lesend.');
            $context->set($partner);

            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
