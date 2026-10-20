<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrationVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Prüft die tatsächlich gerenderten HTML-/Textteile über den Mailkanal, nicht
 * nur Viewnamen. Ausschließlich synthetischer Nutzer und Arraytransport;
 * weder SMTP noch echte personenbezogene Daten oder Änderungen an Dev-Konten.
 */
class RegistrationMailDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_html_and_plaintext_share_the_original_signed_link_and_german_copy(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->create(['email' => 'mail-design@example.test', 'email_verified_at' => null]);
        $user->notify(new RegistrationVerification);
        $sent = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $html = $sent->getHtmlBody();
        $text = $sent->getTextBody();
        $this->assertSame('Merlin Nova: E-Mail-Adresse bestätigen', $sent->getSubject());
        $this->assertStringContainsString('lang="de"', $html);
        $this->assertStringContainsString('#007e8c', $html);
        $this->assertStringContainsString('#101a42', $html);
        $this->assertStringContainsString('max-width:600px', $html);
        $this->assertStringContainsString('<!--[if mso]>', $html);
        $this->assertStringContainsString('Viele Grüße', $html);
        $this->assertStringContainsString('Viele Grüße', $text);
        $this->assertStringContainsString('60 Minuten', $text);
        $this->assertStringContainsString('Ein kostenpflichtiges Abo wird dadurch nicht abgeschlossen.', $text);
        foreach (['Regards', 'All rights reserved', 'If you', '<script', '<img', '@import', 'fonts.googleapis'] as $unwanted) {
            $this->assertStringNotContainsString($unwanted, $html);
        }
        preg_match_all('/href="([^"]+)"/', $html, $links);
        $this->assertCount(2, $links[1]);
        $url = html_entity_decode($links[1][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertSame($links[1][0], $links[1][1]);
        $this->assertStringContainsString($url, $text);
        $this->assertStringNotContainsString('&amp;', $text);
        $this->get($url)->assertRedirect('/login');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
