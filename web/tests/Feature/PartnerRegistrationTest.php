<?php

namespace Tests\Feature;

use App\Billing\BillingCatalog;
use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\SepaMandate;
use App\Models\User;
use App\Notifications\RegistrationVerification;
use App\Registration\PartnerRegistration;
use App\Rules\Iban;
use App\Tenancy\PartnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Durchgängige lokale Registrierung mit synthetischen Daten. Bankformatwerte
 * sind öffentliche IBAN-Beispiele, keine Bankzugänge. MySQL ist getrennt;
 * Notifications werden abgefangen, niemals echte E-Mails versendet.
 */
class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_visitor_gets_registration_guidance_without_losing_session(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($user)->get('/register')->assertOk()
            ->assertSee('Sie sind bereits angemeldet.')
            ->assertSee('privates Browserfenster')
            ->assertDontSee('name="form_token"', false);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('partners', 0);
        $this->post('/register', $this->data())->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('partners', 0);
    }

    private function policy(int $days = 14, int $stations = 2)
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $catalog = app(BillingCatalog::class);

        return $catalog->trialPolicy($admin, ['duration_days' => $days, 'station_limit' => $stations], $catalog->activeTrialPolicy()?->id);
    }

    private function data(array $changes = []): array
    {
        return array_replace([
            'company_name' => 'Synthetische Testfirma', 'contact_name' => 'Test Kontakt',
            'email' => 'partner@example.test', 'password' => 'Local-Testing-Password!42',
            'password_confirmation' => 'Local-Testing-Password!42',
            'iban' => 'DE89 3704 0044 0532 0130 00', 'account_holder' => 'Synthetischer Kontoinhaber',
            'street' => 'Teststraße 1', 'postal_code' => '12345', 'city' => 'Teststadt', 'country' => 'DE',
            'sepa_accepted' => '1', 'test_accepted' => '1',
        ], $changes);
    }

    private function registered(): User
    {
        Notification::fake();
        $this->policy();
        $this->get('/register')->assertOk();
        $this->post('/register', [...$this->data(), 'form_token' => session('registration_form.token')])->assertRedirect('/email/verify');

        return User::where('email', 'partner@example.test')->firstOrFail();
    }

    public function test_unconfigured_registration_does_not_create_a_default_trial(): void
    {
        $this->get('/register')->assertServiceUnavailable()->assertSee('Testbedingungen werden vorbereitet.');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('partners', 0);
    }

    public function test_registration_creates_one_unverified_owner_and_encrypted_prepared_mandate(): void
    {
        $user = $this->registered();
        $partner = Partner::where('registration_owner_id', $user->id)->sole();
        $this->assertFalse($user->is_super_admin);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNull($partner->trial_started_at);
        $this->assertNull($partner->trial_ends_at);
        $this->assertSame(14, $partner->trial_duration_days);
        $this->assertSame(PartnerRole::Owner, PartnerMembership::where('user_id', $user->id)->sole()->role);
        $raw = DB::table('sepa_mandates')->sole();
        $this->assertNotSame(Iban::normalize($this->data()['iban']), $raw->iban);
        $this->assertStringNotContainsString('Synthetischer Kontoinhaber', $raw->account_holder);
        $this->assertStringNotContainsString('Teststraße', $raw->debtor_address);
        app(PartnerContext::class)->set($partner);
        try {
            $mandate = SepaMandate::sole();
            $this->assertSame('DE89370400440532013000', $mandate->iban);
            $this->assertSame('prepared', $mandate->status);
            $this->assertFalse($mandate->canCollect());
            $this->assertArrayNotHasKey('iban', $mandate->toArray());
            $this->assertArrayNotHasKey('account_holder', $mandate->toArray());
        } finally {
            app(PartnerContext::class)->clear();
        }
        Notification::assertSentTo($user, RegistrationVerification::class);
        $this->assertArrayNotHasKey('registration_payload_hash', $user->toArray());
        $this->get('/app')->assertRedirect('/email/verify');
    }

    public function test_confirmation_starts_the_promised_trial_once_even_after_standard_changes(): void
    {
        $user = $this->registered();
        $this->policy(30, 5);
        $url = RegistrationVerification::url($user);
        $this->get($url)->assertRedirect('/app');
        $partner = Partner::where('registration_owner_id', $user->id)->sole();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(14, (int) $partner->trial_started_at->diffInDays($partner->trial_ends_at));
        $start = $partner->trial_started_at->toISOString();
        $end = $partner->trial_ends_at->toISOString();
        $this->travel(10)->minutes();
        $this->get($url)->assertRedirect('/app');
        $this->assertSame($start, $partner->fresh()->trial_started_at->toISOString());
        $this->assertSame($end, $partner->fresh()->trial_ends_at->toISOString());
        $this->assertSame(2, $partner->fresh()->trial_station_limit);
    }

    public function test_expired_trial_is_read_only_at_the_exact_end_boundary(): void
    {
        $user = $this->registered();
        $this->get(RegistrationVerification::url($user))->assertRedirect('/app');
        $partner = Partner::where('registration_owner_id', $user->id)->sole();
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Erlaubte Station'])->assertCreated();
        $this->travelTo($partner->trial_ends_at);
        $this->getJson("/app/partners/{$partner->id}")->assertOk();
        $this->getJson("/app/partners/{$partner->id}/stations")->assertOk();
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Verbotene Station'])->assertForbidden();
        $this->patchJson("/app/partners/{$partner->id}", ['name' => 'Verboten'])->assertForbidden();
        $this->assertFalse(Gate::forUser($user->fresh())->allows('update', $partner->fresh()));
        $this->assertDatabaseMissing('stations', ['name' => 'Verbotene Station']);
    }

    public function test_expired_or_tampered_verification_links_do_not_start_a_trial(): void
    {
        $user = $this->registered();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($expired)->assertForbidden();
        $this->get(RegistrationVerification::url($user).'x')->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertNull(Partner::where('registration_owner_id', $user->id)->sole()->trial_started_at);
    }

    public function test_a_signed_link_for_another_account_is_not_accepted(): void
    {
        $user = $this->registered();
        $other = User::factory()->create();
        $this->actingAs($other)->get(RegistrationVerification::url($user))->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_registration_is_idempotent_and_changed_payload_is_rejected(): void
    {
        $policy = $this->policy();
        $service = app(PartnerRegistration::class);
        $first = $service->register($this->data(), str_repeat('a', 64), $policy->id);
        $again = $service->register($this->data(), str_repeat('a', 64), $policy->id);
        $this->assertSame($first['user']->id, $again['user']->id);
        $this->assertFalse($again['created']);
        $this->assertDatabaseCount('partners', 1);
        $this->assertDatabaseCount('sepa_mandates', 1);
        $this->expectException(ValidationException::class);
        $service->register($this->data(['company_name' => 'Geändert']), str_repeat('a', 64), $policy->id);
    }

    #[DataProvider('invalidRegistration')]
    public function test_invalid_registration_is_atomic(array $changes): void
    {
        $policy = $this->policy();
        try {
            app(PartnerRegistration::class)->register($this->data($changes), str_repeat('b', 64), $policy->id);
            $this->fail('Ungültige Angaben akzeptiert.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('partners', 0);
            $this->assertDatabaseCount('sepa_mandates', 0);
            $this->assertDatabaseCount('partner_memberships', 0);
            $this->assertDatabaseCount('users', 1);
        }
    }

    public static function invalidRegistration(): array
    {
        return [[['iban' => 'DE00370400440532013000']], [['password' => 'password', 'password_confirmation' => 'password']], [['sepa_accepted' => '0']], [['test_accepted' => '0']], [['is_super_admin' => true]], [['role' => 'owner']], [['partner_id' => 1]]];
    }

    public function test_stale_test_rules_reject_registration_without_partial_records(): void
    {
        $policy = $this->policy();
        $this->policy(30, 4);
        $this->expectException(ValidationException::class);
        app(PartnerRegistration::class)->register($this->data(), str_repeat('c', 64), $policy->id);
    }

    public function test_sensitive_inputs_are_not_flashed_or_echoed_after_errors(): void
    {
        $this->policy();
        $this->get('/register')->assertOk();
        $this->post('/register', [...$this->data(['company_name' => '']), 'form_token' => session('registration_form.token')])
            ->assertSessionHasErrors('company_name')->assertSessionMissing('_old_input.iban')
            ->assertSessionMissing('_old_input.account_holder')->assertSessionMissing('_old_input.password');
        $this->get('/register')->assertOk()->assertDontSee('DE89 3704 0044 0532 0130 00')
            ->assertDontSee('Synthetischer Kontoinhaber')->assertDontSee('Local-Testing-Password!42');
    }

    public function test_untrusted_form_token_cannot_create_records(): void
    {
        $this->policy();
        $this->post('/register', [...$this->data(), 'form_token' => str_repeat('x', 64)])->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('partners', 0);
    }

    public function test_external_mail_transport_is_blocked_before_registration(): void
    {
        $this->policy();
        config(['mail.default' => 'smtp']);
        $this->get('/register')->assertServiceUnavailable();
        $this->post('/register', $this->data())->assertServiceUnavailable();
        $this->assertDatabaseCount('partners', 0);
    }

    public function test_resend_is_authenticated_and_contains_no_bank_data(): void
    {
        $user = $this->registered();
        $this->post('/email/verification-notification')->assertRedirect();
        Notification::assertSentToTimes($user, RegistrationVerification::class, 2);
        $mail = (new RegistrationVerification)->toMail($user)->toArray();
        $this->assertStringNotContainsString('DE89370400440532013000', json_encode($mail));
        $this->assertStringNotContainsString('Kontoinhaber', json_encode($mail));
    }

    public function test_inactive_partner_cannot_be_confirmed(): void
    {
        $user = $this->registered();
        $partner = Partner::where('registration_owner_id', $user->id)->sole();
        $partner->forceFill(['is_active' => false])->save();
        $this->get(RegistrationVerification::url($user))->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertNull($partner->fresh()->trial_started_at);
    }

    public function test_duplicate_email_cannot_promote_or_modify_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'partner@example.test']);
        $policy = $this->policy();
        try {
            app(PartnerRegistration::class)->register($this->data(), str_repeat('d', 64), $policy->id);
            $this->fail('Bestehendes Konto übernommen.');
        } catch (ValidationException) {
            $this->assertFalse($user->fresh()->is_super_admin);
            $this->assertDatabaseCount('partners', 0);
        }
    }

    public function test_promised_station_limit_is_enforced_without_changing_the_snapshot(): void
    {
        $user = $this->registered();
        $this->get(RegistrationVerification::url($user))->assertRedirect('/app');
        $partner = Partner::where('registration_owner_id', $user->id)->sole();
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Erste'])->assertCreated();
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Zweite'])->assertCreated();
        $this->policy(30, 10);
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Dritte'])->assertUnprocessable()->assertJsonValidationErrors('station_limit');
        $this->assertDatabaseCount('stations', 2);
        $this->assertSame(2, $partner->fresh()->trial_station_limit);
    }

    public function test_replayed_registration_cannot_bypass_a_later_password_change(): void
    {
        $user = $this->registered();
        $form = session('registration_form');
        $user->forceFill(['password' => 'A-Different-Password!42'])->save();
        Auth::logout();
        $this->withSession(['registration_form' => $form])->post('/register', [...$this->data(), 'form_token' => $form['token']])
            ->assertSessionHasErrors('registration');
        $this->assertGuest();
        $this->assertDatabaseCount('partners', 1);
    }

    public function test_confirmation_link_is_resumed_after_login(): void
    {
        $user = $this->registered();
        $url = RegistrationVerification::url($user);
        Auth::logout();
        $this->get($url)->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => $this->data()['password']])->assertRedirect($url);
        $this->get($url)->assertRedirect('/app');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    #[DataProvider('ibanExamples')]
    public function test_iban_formal_validation(string $iban, bool $valid): void
    {
        $this->assertSame($valid, Iban::valid($iban));
    }

    public static function ibanExamples(): array
    {
        return [['de89 3704 0044 0532 0130 00', true], ['GB82 WEST 1234 5698 7654 32', true], ['DE00370400440532013000', false], ['ZZ89370400440532013000', false], ['DE8937040044053201300', false], ['<script>', false]];
    }
}
