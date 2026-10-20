<?php

namespace App\Employees;

use App\Models\PlatformMailSetting;
use App\Support\RegistrationMail;
use Illuminate\Mail\Mailable;

/** Neutrale Einladungsnachricht ohne Steuer-, Bank- oder Vertragsangaben. */
class EmployeeInvitationMail extends Mailable
{
    public function __construct(public string $invitationUrl) {}

    public function build(): self
    {
        // Derselbe ausdrücklich konfigurierte Absender wie bei der Registrierung;
        // sonst würde SMTP zwar authentifizieren, aber einen .env-Default verwenden.
        if (app(RegistrationMail::class)->usesSmtp()) {
            $settings = PlatformMailSetting::findOrFail(1);
            $this->from($settings->from_address, $settings->from_name);
        }

        return $this->text('employees.public.invitation-text')->subject('Merlin Nova: Einladung zur Selbstauskunft')->view('mail.registration-verification', [
            'mailTitle' => 'Merlin Nova · Persönliche Selbstauskunft', 'preheader' => 'Persönliche Angaben geschützt ausfüllen und zur Arbeitgeberprüfung einreichen.', 'eyebrow' => 'IHRE PERSÖNLICHE SELBSTAUSKUNFT',
            'greeting' => 'Ihre Angaben für den Einstieg',
            'introLines' => ['Ihr Arbeitgeber lädt Sie ein, Ihre persönlichen Stammdaten geschützt in Merlin Nova auszufüllen. Sie können zwischenspeichern und die Angaben anschließend zur Prüfung einreichen.'],
            'actionUrl' => $this->invitationUrl, 'actionText' => 'Selbstauskunft öffnen',
            'outroLines' => ['Der Link gilt sieben Tage und lässt sich einmalig in diesem Browser aktivieren. Er ist persönlich: Bitte nicht weiterleiten. Für einen anderen Browser benötigen Sie eine neue Einladung.',
                'Nach der Aktivierung können Sie die Seite in demselben Browser bis zum Ablauf wieder öffnen. Falls Sie diese Einladung nicht erwartet haben, wenden Sie sich an Ihren Arbeitgeber.'],
        ]);
    }
}
