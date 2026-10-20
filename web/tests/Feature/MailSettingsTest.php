<?php

namespace Tests\Feature;

use App\Filament\Pages\MailSettings;
use App\Models\PlatformMailSetting;
use App\Models\User;
use App\Notifications\RegistrationVerification;
use App\Support\RegistrationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/** Nur synthetische Postfächer/Passwörter; sämtliche SMTP-Aufrufe sind gemockt. */
class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $changes = []): array
    {
        return array_replace(['username' => 'smtp@example.test', 'from_address' => 'smtp@example.test',
            'from_name' => 'Merlin Test', 'smtp_password' => 'Synthetic-Only!42', 'enabled' => '1'], $changes);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    public function test_admin_saves_encrypted_password_without_exposing_it_in_page_or_livewire(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/superadmin/email/settings', $this->data())->assertRedirect('/superadmin/email');
        $settings = PlatformMailSetting::findOrFail(1);
        $this->assertSame('Synthetic-Only!42', $settings->password);
        $this->assertStringNotContainsString('Synthetic-Only!42', DB::table('platform_mail_settings')->value('password'));
        $this->assertArrayNotHasKey('password', $settings->toArray());
        $this->get('/superadmin/email')->assertOk()->assertSee('Passwort hinterlegt')->assertDontSee('Synthetic-Only!42');
        Livewire::test(MailSettings::class)->assertSee('smtp@example.test')->assertDontSee('Synthetic-Only!42');
    }

    public function test_blank_password_keeps_secret_but_changing_mailbox_requires_new_password(): void
    {
        $this->actingAs($this->admin())->post('/superadmin/email/settings', $this->data())->assertRedirect();
        $this->post('/superadmin/email/settings', $this->data(['smtp_password' => '']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Synthetic-Only!42', PlatformMailSetting::find(1)->password);
        $this->post('/superadmin/email/settings', $this->data(['smtp_password' => '', 'username' => 'other@example.test', 'from_address' => 'other@example.test']))->assertSessionHasErrors('username');
        $this->assertSame('smtp@example.test', PlatformMailSetting::find(1)->username);
    }

    public function test_activation_requires_password_and_validation_does_not_flash_secret(): void
    {
        $this->actingAs($this->admin())->post('/superadmin/email/settings', $this->data(['smtp_password' => '']))->assertSessionHasErrors('enabled');
        $this->post('/superadmin/email/settings', $this->data(['from_address' => 'invalid']))->assertSessionHasErrors('from_address')->assertSessionMissing('_old_input.smtp_password');
        $this->assertDatabaseCount('platform_mail_settings', 0);
    }

    public function test_guests_partners_unverified_and_disabled_admins_cannot_change_mail(): void
    {
        $this->post('/superadmin/email/settings', $this->data())->assertRedirect('/login');
        foreach ([[], ['is_super_admin' => true, 'email_verified_at' => null], ['is_super_admin' => true, 'is_active' => false]] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $status = array_key_exists('email_verified_at', $attributes) ? 302 : 403;
            $this->post('/superadmin/email/settings', $this->data())->assertStatus($status);
            $this->post('/superadmin/email/test')->assertStatus($status);
            $this->get('/superadmin/email')->assertForbidden();
        }
        $this->assertDatabaseCount('platform_mail_settings', 0);
    }

    public function test_strato_uses_fixed_direct_tls_and_explicit_sender_with_no_log_fallback(): void
    {
        $this->actingAs($this->admin())->post('/superadmin/email/settings', $this->data(['host' => '127.0.0.1', 'port' => 465]));
        $message = (new RegistrationVerification)->toMail(User::factory()->create());
        $this->assertSame('nova_registration', $message->mailer);
        $this->assertSame('smtp@example.test', $message->from[0]);
        $this->assertSame('smtp.strato.de', config('mail.mailers.nova_registration.host'));
        $this->assertSame(465, config('mail.mailers.nova_registration.port'));
        $this->assertSame('smtps', config('mail.mailers.nova_registration.scheme'));
        $this->get('/register')->assertSee('Sie sind bereits angemeldet.');
    }

    public function test_smtp_notice_is_accurate_and_transport_failure_is_generic(): void
    {
        $this->actingAs($this->admin())->post('/superadmin/email/settings', $this->data());
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user)->get('/email/verify')->assertOk()->assertSee('Posteingang')->assertDontSee('keine echte E-Mail');
        Mail::shouldReceive('purge')->with('nova_registration');
        $transport = \Mockery::mock();
        Mail::shouldReceive('mailer')->with('nova_registration')->andReturn($transport);
        $transport->shouldReceive('send')->andThrow(new TransportException('SECRET-SMTP-DETAIL'));
        $this->from('/email/verify')->post('/email/verification-notification')->assertRedirect('/email/verify')
            ->assertSessionHas('status', 'Die Bestätigungsmail konnte nicht versendet werden. Bitte später erneut versuchen.');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_explicit_test_mail_uses_saved_sender_and_hides_transport_errors(): void
    {
        $this->actingAs($this->admin())->post('/superadmin/email/settings', $this->data());
        Mail::shouldReceive('purge')->with('nova_registration');
        $transport = \Mockery::mock();
        Mail::shouldReceive('mailer')->with('nova_registration')->andReturn($transport);
        $transport->shouldReceive('raw')->once()->with(\Mockery::type('string'), \Mockery::on(function ($callback) {
            $message = new Message(new Email);
            $callback($message);
            $this->assertSame('smtp@example.test', $message->getSymfonyMessage()->getTo()[0]->getAddress());
            $this->assertSame('smtp@example.test', $message->getSymfonyMessage()->getFrom()[0]->getAddress());

            return true;
        }));
        $this->from('/superadmin/email')->post('/superadmin/email/test', ['recipient' => 'attacker@example.test'])
            ->assertRedirect('/superadmin/email')->assertSessionHas('status', 'STRATO hat die Testnachricht angenommen. Bitte Posteingang und Spamordner prüfen.');
    }

    public function test_disabled_smtp_does_not_send_test_mail(): void
    {
        Mail::shouldReceive('mailer')->never();
        $this->actingAs($this->admin())->post('/superadmin/email/test')->assertStatus(422);
    }

    public function test_transport_diagnostics_distinguish_tls_auth_and_timeout_without_leaking_details(): void
    {
        $mail = app(RegistrationMail::class);
        foreach ([
            'certificate verify failed SECRET-AUTH' => 'TLS-Zertifikatsprüfung',
            '535 authentication failed SECRET-AUTH' => 'Anmeldung abgelehnt',
            'SSL: Handshake timed out SECRET-AUTH' => 'nicht rechtzeitig',
        ] as $reason => $expected) {
            $message = $mail->failureMessage(new TransportException($reason));
            $this->assertStringContainsString($expected, $message);
            $this->assertStringNotContainsString('SECRET-AUTH', $message);
        }
    }

    public function test_alternative_port_requires_tls_and_rejects_unencrypted_ports(): void
    {
        $this->actingAs($this->admin())->post('/superadmin/email/settings', $this->data(['port' => '587']))->assertSessionHasNoErrors();
        $message = (new RegistrationVerification)->toMail(User::factory()->create());
        $this->assertSame('nova_registration', $message->mailer);
        $this->assertSame(587, config('mail.mailers.nova_registration.port'));
        $this->assertSame('smtp', config('mail.mailers.nova_registration.scheme'));
        $this->assertTrue(config('mail.mailers.nova_registration.require_tls'));
        $transport = Mail::mailer('nova_registration')->getSymfonyTransport();
        $property = new \ReflectionProperty($transport, 'requireTls');
        $this->assertTrue($property->getValue($transport));
        $this->get('/superadmin/email')->assertOk()->assertSee('verpflichtendes STARTTLS');
        $this->post('/superadmin/email/settings', $this->data(['port' => 25]))->assertSessionHasErrors('port');
        $this->assertSame(587, PlatformMailSetting::find(1)->port);
    }
}
