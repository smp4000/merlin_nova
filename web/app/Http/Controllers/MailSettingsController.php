<?php

namespace App\Http\Controllers;

use App\Models\PlatformMailSetting;
use App\Support\RegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Mailkonfiguration nur für aktive bestätigte Plattformadmins, niemals Partner.
 * Leeres Passwort erhält den bestehenden Geheimwert; Antworten, Fehler und
 * Sitzungs-Old-Input enthalten kein Passwort. Keine automatischen Testmails beim
 * Speichern. Ein gesonderter Test geht ausschließlich an den gespeicherten Absender.
 */
class MailSettingsController extends Controller
{
    public function save(Request $request)
    {
        Gate::authorize('manage-mail');
        $data = $request->validate([
            'username' => ['required', 'email', 'max:255'],
            'from_address' => ['required', 'email', 'max:255', 'same:username'],
            'from_name' => ['required', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'smtp_password' => ['nullable', 'string', 'max:1024'],
            'enabled' => ['sometimes', 'boolean'],
            'port' => ['sometimes', 'integer', 'in:465,587'],
        ]);
        DB::transaction(function () use ($data) {
            $settings = PlatformMailSetting::query()->lockForUpdate()->find(1) ?? new PlatformMailSetting;
            $password = $data['smtp_password'] ?? null;
            if (($data['enabled'] ?? false) && ! $password && ! $settings->password) {
                throw ValidationException::withMessages(['enabled' => 'Bitte vor der Aktivierung das SMTP-Passwort eingeben.']);
            }
            // Ein anderes Postfach muss sein eigenes Passwort ausdrücklich erhalten.
            if ($settings->username && $settings->username !== $data['username'] && ! $password) {
                throw ValidationException::withMessages(['username' => 'Beim Postfachwechsel bitte auch das neue Passwort eingeben.']);
            }
            $settings->forceFill([
                'id' => 1, 'username' => $data['username'], 'from_address' => $data['from_address'],
                'from_name' => $data['from_name'], 'enabled' => $data['enabled'] ?? false,
                'port' => $data['port'] ?? $settings->port ?? 465,
            ]);
            if ($password !== null && $password !== '') {
                $settings->password = $password;
            }
            $settings->save();
        });

        return redirect('/superadmin/email')->with('status_type', 'success')->with('status', 'Einstellungen gespeichert. Es wurde noch keine Testmail versendet.');
    }

    public function test(RegistrationMail $mail)
    {
        Gate::authorize('manage-mail');
        abort_unless($mail->usesSmtp(), 422, 'Bitte zuerst SMTP aktivieren.');
        $settings = PlatformMailSetting::findOrFail(1);
        try {
            Mail::mailer($mail->mailer())->raw('Dies ist eine ausdrücklich angeforderte Testnachricht aus der lokalen Merlin-Nova-Kopie.', function ($message) use ($settings) {
                $message->to($settings->from_address)->from($settings->from_address, $settings->from_name)->subject('Merlin Nova: SMTP-Test');
            });
        } catch (TransportExceptionInterface $error) {
            // SMTP-Antworten können Zugangsdaten/Empfänger enthalten: nicht loggen.
            return back()->withErrors(['mail' => $mail->failureMessage($error)]);
        }

        return back()->with('status_type', 'success')->with('status', 'STRATO hat die Testnachricht angenommen. Bitte Posteingang und Spamordner prüfen.');
    }
}
