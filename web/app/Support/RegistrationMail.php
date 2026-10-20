<?php

namespace App\Support;

use App\Models\PlatformMailSetting;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Eng begrenzter Versandweg für die lokale Nova-Registrierung.
 * STRATO-Host und sichere Port-/TLS-Kombinationen sind fest vorgegeben: Einstellungen können
 * keine internen Netzadressen oder unverschlüsselten Transporte einschleusen.
 * Kein stiller Log-Fallback bei SMTP-Fehlern und keine globalen .env-Änderungen.
 * Der benannte Mailer wird vor Nutzung erneuert, damit ein Passwortwechsel auch
 * bei länger laufenden Prozessen greift. Tests bleiben ohne Aktivierung lokal.
 */
class RegistrationMail
{
    public function usesSmtp(): bool
    {
        return PlatformMailSetting::find(1)?->enabled ?? false;
    }

    /** Prüft Vollständigkeit vor Datenerfassung; es wird noch keine Verbindung aufgebaut. */
    public function ensureAvailable(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 503);
        $settings = PlatformMailSetting::find(1);
        if ($settings?->enabled) {
            abort_unless($settings->password && $settings->username && $settings->from_address, 503, 'SMTP-Einstellungen sind unvollständig.');

            return;
        }
        $mailer = config('mail.default');
        abort_unless(in_array(config("mail.mailers.{$mailer}.transport"), ['log', 'array'], true), 503, 'Bitte E-Mail-Versand im Superadmin einrichten.');
    }

    /** Nur intern verwenden: Konfiguration enthält ein entschlüsseltes Geheimnis. */
    public function mailer(): string
    {
        $this->ensureAvailable();
        $settings = PlatformMailSetting::find(1);
        if (! $settings?->enabled) {
            return config('mail.default');
        }
        config(['mail.mailers.nova_registration' => [
            'transport' => 'smtp', 'scheme' => $settings->port === 587 ? 'smtp' : 'smtps', 'host' => 'smtp.strato.de',
            // Port 587 darf niemals ohne erfolgreiches STARTTLS authentifizieren.
            // require_tls verhindert auch bei fehlendem Serverangebot einen
            // unverschlüsselten Fallback; 465 bleibt direktes TLS.
            'port' => $settings->port === 587 ? 587 : 465, 'require_tls' => true,
            'username' => $settings->username, 'password' => $settings->password,
            'timeout' => 15,
        ]]);
        Mail::purge('nova_registration');

        // Die eigene Windows-PHP-Laufzeit besitzt keinen eingebauten Zugriff auf
        // den Windows-Zertifikatsspeicher. Ein ausdrücklich lokal vorbereitetes
        // CA-Bündel gilt nur für diesen SMTP-Stream, nicht global für PHP/cURL.
        // Zertifikatskette und Hostname müssen weiterhin beide gültig sein.
        $caFile = base_path('../.local/mail/ca-bundle.pem');
        if (app()->environment('local') && is_file($caFile)) {
            $stream = Mail::mailer('nova_registration')->getSymfonyTransport()->getStream();
            $options = $stream->getStreamOptions();
            $options['ssl'] = array_merge($options['ssl'] ?? [], [
                'cafile' => $caFile, 'verify_peer' => true, 'verify_peer_name' => true,
            ]);
            $stream->setStreamOptions($options);
        }

        return 'nova_registration';
    }

    /**
     * Speichert ausschließlich einen festen Diagnosecode, keine Serverantwort.
     * SMTP-Ausnahmen können Adressen und AUTH-Daten enthalten; diese dürfen weder
     * in Personalakten noch in Sitzungen oder Benachrichtigungen gelangen.
     */
    public function failureCode(TransportExceptionInterface $error): string
    {
        $message = strtolower($error->getMessage());

        return match (true) {
            str_contains($message, 'certificate'), str_contains($message, 'crypto') => 'tls',
            str_contains($message, 'timed out'), str_contains($message, 'timeout') => 'timeout',
            str_contains($message, 'authenticat'), str_contains($message, '535') => 'auth',
            str_contains($message, 'rcpt to'), str_contains($message, 'recipient') => 'recipient',
            str_contains($message, 'mail from'), str_contains($message, 'sender') => 'sender',
            str_contains($message, 'connection'), str_contains($message, 'connect to') => 'connection',
            default => 'rejected',
        };
    }

    /** Nur vertrauenswürdige feste Texte ausgeben, auch bei unbekanntem Altstatus. */
    public function deliveryFailureMessage(?string $code): string
    {
        return match ($code) {
            'tls' => 'Die TLS-Zertifikatsprüfung ist fehlgeschlagen. Bitte den lokalen CA-Zertifikatsspeicher prüfen.',
            'timeout' => 'Der Mailserver antwortet nicht rechtzeitig. Eine erfolgreiche Übergabe ist nicht bestätigt. Vor erneutem Versand den Posteingang prüfen.',
            'auth' => 'Der Mailserver hat die Anmeldung abgelehnt. Bitte SMTP-Benutzername und Passwort im Superadmin prüfen.',
            'recipient' => 'Der Mailserver hat den Empfänger abgelehnt. Bitte die Empfängeradresse und das Postfach prüfen.',
            'sender' => 'Der Mailserver hat den Absender abgelehnt. Bitte den Absender im Superadmin prüfen.',
            'connection' => 'Der Mailserver ist nicht erreichbar. Bitte Netzwerk und SMTP-Verbindung prüfen.',
            'internal' => 'Die Nachricht konnte nicht erstellt oder übergeben werden. Bitte die lokale Anwendung prüfen lassen.',
            default => 'Versand fehlgeschlagen. Bitte Postfach und SMTP-Verbindung prüfen.',
        };
    }

    /**
     * Nur feste Fehlerkategorien anzeigen, niemals rohe SMTP-Serverantworten:
     * diese können Benutzernamen, Empfänger und AUTH-Protokolldaten enthalten.
     * Ein TLS-Fehler ist keine Aussage über die Richtigkeit des Passworts.
     */
    public function failureMessage(TransportExceptionInterface $error): string
    {
        $message = strtolower($error->getMessage());

        return match (true) {
            str_contains($message, 'certificate'), str_contains($message, 'crypto') => 'Die TLS-Zertifikatsprüfung ist fehlgeschlagen. Es wurde keine Testmail versendet. Bitte den lokalen CA-Zertifikatsspeicher prüfen.',
            str_contains($message, 'timed out'), str_contains($message, 'timeout') => 'Der Mailserver antwortet nicht rechtzeitig. Es wurde keine erfolgreiche Übergabe bestätigt. Bitte später erneut versuchen.',
            str_contains($message, 'authenticat'), str_contains($message, '535') => 'Der Mailserver hat die Anmeldung abgelehnt. Bitte SMTP-Benutzername und Passwort prüfen.',
            default => 'Versand fehlgeschlagen. Bitte Postfach und SMTP-Verbindung prüfen.',
        };
    }
}
