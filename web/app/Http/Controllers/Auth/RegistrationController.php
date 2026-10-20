<?php

namespace App\Http\Controllers\Auth;

use App\Billing\BillingCatalog;
use App\Http\Controllers\Controller;
use App\Notifications\RegistrationVerification;
use App\Registration\PartnerRegistration;
use App\Rules\Iban;
use App\Support\RegistrationMail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Lokaler Registrierungseinstieg mit serverseitig gebundenem Formularschlüssel.
 * Nach dem Anlegen ist der Benutzer angemeldet, aber noch nicht bestätigt.
 * SMTP wird ausschließlich über die freigegebenen Plattformsettings aktiviert.
 * Bankwerte werden weder
 * in Antworten noch Fehlermeldungen/alten Formulareingaben wieder ausgegeben.
 */
class RegistrationController extends Controller
{
    private function requireMailAvailability(): void
    {
        app(RegistrationMail::class)->ensureAvailable();
    }

    public function show(Request $request, BillingCatalog $catalog)
    {
        // Der öffentliche Einstieg bleibt auch neben einer Admin-Sitzung erreichbar.
        // Niemals automatisch abmelden oder das bestehende Konto durch einen neuen
        // Partner ersetzen. Nur die Erklärung ist öffentlich; POST bleibt gastgeschützt.
        if ($request->user()) {
            return response()->view('auth.registration-session')->header('Cache-Control', 'private, no-store');
        }

        $this->requireMailAvailability();
        $policy = $catalog->activeTrialPolicy();
        if ($policy) {
            $request->session()->put('registration_form', ['token' => bin2hex(random_bytes(32)), 'policy_id' => $policy->id]);
        }

        return response()->view('auth.register', ['policy' => $policy, 'declaration' => PartnerRegistration::DECLARATION, 'ibanLengths' => Iban::LENGTHS, 'smtpEnabled' => app(RegistrationMail::class)->usesSmtp()], $policy ? 200 : 503)
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, PartnerRegistration $registration)
    {
        $this->requireMailAvailability();
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
            // Die Registrierung ist bereits atomar gespeichert. Ein SMTP-Ausfall
            // darf keinen zweiten Partner erzeugen oder den Test vorzeitig starten.
            // Keine Transportdetails protokollieren; sie können Geheimnisse enthalten.
            try {
                $result['user']->notify(new RegistrationVerification);
            } catch (TransportExceptionInterface) {
                return redirect()->route('verification.notice')->with('status_type', 'error')->with('status', 'Ihr Zugang wurde angelegt, die Bestätigungsmail konnte aber nicht versendet werden. Bitte später erneut anfordern.');
            }
        }

        return redirect()->route('verification.notice')->with('status', app(RegistrationMail::class)->usesSmtp()
            ? 'Die Bestätigungsnachricht wurde an den Mailserver übergeben. Bitte auch den Spamordner prüfen.'
            : 'Die Bestätigungsnachricht wurde im lokalen Mailprotokoll abgelegt.');
    }

    public function notice()
    {
        return response()->view('auth.verify-email', ['smtpEnabled' => app(RegistrationMail::class)->usesSmtp()])->header('Cache-Control', 'private, no-store');
    }

    public function resend(Request $request)
    {
        $this->requireMailAvailability();
        if (! $request->user()->hasVerifiedEmail()) {
            try {
                $request->user()->notify(new RegistrationVerification);
            } catch (TransportExceptionInterface) {
                return back()->with('status_type', 'error')->with('status', 'Die Bestätigungsmail konnte nicht versendet werden. Bitte später erneut versuchen.');
            }
        }

        return back()->with('status', app(RegistrationMail::class)->usesSmtp()
            ? 'Falls noch erforderlich, wurde eine neue Bestätigungsnachricht an den Mailserver übergeben. Bitte auch den Spamordner prüfen.'
            : 'Falls noch erforderlich, wurde eine neue Bestätigungsnachricht im lokalen Mailprotokoll abgelegt.');
    }

    public function verify(EmailVerificationRequest $request, PartnerRegistration $registration)
    {
        $registration->confirm($request->user());
        $request->user()->refresh();

        return redirect()->route('workspace')->with('status', 'E-Mail-Adresse bestätigt. Den aktuellen Teststatus sehen Sie im Partnerbereich.');
    }
}
