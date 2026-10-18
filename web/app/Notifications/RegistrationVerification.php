<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Deutsche Bestätigungsnachricht ohne Bank-, Adress- oder Passwortdaten.
 * Link ist zeitlich begrenzt und signiert; die Zielroute verlangt zusätzlich
 * die Anmeldung desselben Kontos. Lokal wird ausschließlich log/array erlaubt.
 */
class RegistrationVerification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Merlin Nova: E-Mail-Adresse bestätigen')
            ->greeting('Willkommen bei Merlin Nova!')
            ->line('Bestätigen Sie Ihre E-Mail-Adresse, um Ihren kostenlosen Test zu starten.')
            ->action('E-Mail-Adresse bestätigen', self::url($notifiable))
            ->line('Der Link gilt 60 Minuten. Ein kostenpflichtiges Abo wird dadurch nicht abgeschlossen.')
            ->line('Wenn Sie kein Konto angelegt haben, ignorieren Sie diese Nachricht.');
    }

    public static function url(object $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]);
    }
}
