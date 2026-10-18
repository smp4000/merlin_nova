<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sitzungseinstieg für bereits angelegte persönliche Zugänge.
 *
 * Allgemeines IP-Limit an der Route plus eigenes Limit pro E-Mail/IP-Paar
 * begrenzen Passwortversuche. Fehlermeldungen verraten keine Kontoexistenz.
 * Erfolgreiche Anmeldung rotiert die Sitzungs-ID, Abmeldung verwirft Sitzung
 * und CSRF-Token. E-Mail-Bestätigung bleibt eine nachgelagerte Zugriffssperre.
 * Registrierung, Passwortzurücksetzung und MFA sind eigene spätere Pakete.
 */
class SessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']]);
        $key = 'nova-login:'.hash('sha256', Str::lower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Zu viele Anmeldeversuche. Bitte später erneut versuchen.']);
        }

        if (! Auth::attempt(['email' => Str::lower($data['email']), 'password' => $data['password'], 'is_active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Die Anmeldung ist mit diesen Daten nicht möglich.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route('workspace');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
