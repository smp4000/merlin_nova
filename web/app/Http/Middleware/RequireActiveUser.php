<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontosperre auch bei bereits angemeldeten Partner-Sitzungen durchsetzen.
 *
 * Authentifizierung allein bedeutet nicht, dass ein Konto noch aktiv ist.
 * Diese Prüfung läuft vor der Partnerauflösung. Antworten aus geschützten
 * Bereichen sollen nicht in gemeinsam benutzten Browser-/Proxycaches landen.
 * Das Plattformpanel prüft dieselbe Voraussetzung über canAccessPanel.
 */
class RequireActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_active, 403);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
