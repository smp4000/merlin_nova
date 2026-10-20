<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verhindert Browser-/Proxycache, Einbettung und Referrerweitergabe der Personal-
 * oberflächen. Einladungsgeheimnisse liegen nur im Fragment bzw. POST, niemals
 * in Querystrings. Keine Drittinhalte und keine Offline-Serviceworker im Modul.
 */
class ProtectEmployeePages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");

        return $response;
    }
}
