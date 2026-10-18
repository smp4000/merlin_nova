<?php

namespace App\Http\Controllers\Auth;

use App\Billing\BillingCatalog;
use App\Http\Controllers\Controller;
use App\Notifications\RegistrationVerification;
use App\Registration\PartnerRegistration;
use App\Rules\Iban;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Lokaler Registrierungseinstieg mit serverseitig gebundenem Formularschlüssel.
 * Nach dem Anlegen ist der Benutzer angemeldet, aber noch nicht bestätigt.
 * Echte Mailtransporte sind für dieses Paket gesperrt. Bankwerte werden weder
 * in Antworten noch Fehlermeldungen/alten Formulareingaben wieder ausgegeben.
 */
class RegistrationController extends Controller
{
    private function requireLocalMail(): void
    {
        $mailer = config('mail.default');
        abort_unless(app()->environment(['local', 'testing'])
            && in_array(config("mail.mailers.{$mailer}.transport"), ['log', 'array'], true), 503, 'Registrierung nur mit lokalem Mailprotokoll freigegeben.');
    }

    public function show(Request $request, BillingCatalog $catalog)
    {
        // Der öffentliche Einstieg bleibt auch neben einer Admin-Sitzung erreichbar.
        // Niemals automatisch abmelden oder das bestehende Konto durch einen neuen
        // Partner ersetzen. Nur die Erklärung ist öffentlich; POST bleibt gastgeschützt.
        if ($request->user()) {
            return response()->view('auth.registration-session')->header('Cache-Control', 'private, no-store');
        }

        $this->requireLocalMail();
        $policy = $catalog->activeTrialPolicy();
        if ($policy) {
            $request->session()->put('registration_form', ['token' => bin2hex(random_bytes(32)), 'policy_id' => $policy->id]);
        }

        return response()->view('auth.register', ['policy' => $policy, 'declaration' => PartnerRegistration::DECLARATION, 'ibanLengths' => Iban::LENGTHS], $policy ? 200 : 503)
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, PartnerRegistration $registration)
    {
        $this->requireLocalMail();
        $form = $request->session()->get('registration_form');
        if (! $form || ! is_string($request->input('form_token')) || ! hash_equals($form['token'], $request->input('form_token'))) {
            throw ValidationException::withMessages(['registration' => 'Das Formular ist abgelaufen. Bitte die Registrierung neu laden.']);
        }
        $result = $registration->register($request->all(), $form['token'], $form['policy_id']);
        // Ein Wiederholungsformular darf eine spätere Kontosperre oder
        // Passwortänderung nicht als alternativer Anmeldeweg umgehen.
        abort_unless($result['user']->is_active, 403);
        if (! $result['created'] && ! Hash::check($request->string('password')->toString(), $result['user']->password)) {
            throw ValidationException::withMessages(['registration' => 'Bitte mit dem aktuellen Passwort anmelden.']);
        }
        Auth::login($result['user']);
        $request->session()->regenerate();
        if ($result['created']) {
            // Keine Standard-Registered-Listener auslösen, die einen fremden Mailweg
            // nachrüsten könnten. Die Nachricht hat hier einen expliziten lokalen Weg.
            $result['user']->notify(new RegistrationVerification);
        }

        return redirect()->route('verification.notice')->with('status', 'Die Bestätigungsnachricht wurde im lokalen Mailprotokoll abgelegt.');
    }

    public function notice()
    {
        return response()->view('auth.verify-email')->header('Cache-Control', 'private, no-store');
    }

    public function resend(Request $request)
    {
        $this->requireLocalMail();
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->notify(new RegistrationVerification);
        }

        return back()->with('status', 'Falls noch erforderlich, wurde eine neue Bestätigungsnachricht im lokalen Mailprotokoll abgelegt.');
    }

    public function verify(EmailVerificationRequest $request, PartnerRegistration $registration)
    {
        $registration->confirm($request->user());
        $request->user()->refresh();

        return redirect()->route('workspace')->with('status', 'E-Mail-Adresse bestätigt. Den aktuellen Teststatus sehen Sie im Partnerbereich.');
    }
}
