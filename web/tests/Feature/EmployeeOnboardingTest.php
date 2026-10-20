<?php

namespace Tests\Feature;

use App\Employees\EmployeeDocuments;
use App\Employees\EmployeeFields;
use App\Employees\EmployeeInvitationMail;
use App\Enums\PartnerRole;
use App\Models\EmployeeInvitation;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\PlatformMailSetting;
use App\Models\User;
use App\Support\RegistrationMail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * Belegt Fachablauf und Vertrauensgrenzen mit synthetischen Daten und Fake-Mail.
 * Keine echten SMTP-Empfänger, DATEV-Aufrufe oder produktiven Personaldaten.
 */
class EmployeeOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Partner $partner;

    private int $station;

    private string $employee;

    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->owner = User::factory()->create();
        $this->partner = Partner::create(['name' => 'Fiktiver Personalpartner']);
        $this->partner->forceFill(['registration_owner_id' => $this->owner->id, 'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(7), 'trial_station_limit' => 3])->save();
        PartnerMembership::forceCreate(['partner_id' => $this->partner->id, 'user_id' => $this->owner->id, 'role' => PartnerRole::Owner]);
        $this->actingAs($this->owner)->postJson('/app/partners/'.$this->partner->id.'/stations', ['name' => 'Fiktive Personalstation'])->assertCreated();
        $this->station = DB::table('stations')->value('id');
        $this->base = '/app/partners/'.$this->partner->id.'/employees';
    }

    private function createEmployee(string $profile = 'general'): void
    {
        $response = $this->get($this->base.'/create')->assertOk();
        preg_match('/name="creation_key" value="([^"]+)"/', $response->getContent(), $match);
        $payload = ['creation_key' => $match[1], 'first_name' => 'Fiktive', 'last_name' => 'Testperson', 'email' => 'personal-test@example.test', 'station_id' => $this->station, 'profile' => $profile];
        $this->post($this->base.'/create', $payload)->assertRedirect();
        $this->employee = DB::table('employees')->latest('id')->value('uuid');
        $this->post($this->base.'/create', $payload)->assertRedirect();
        $this->assertDatabaseCount('employees', 1);
        Mail::assertNothingSent();
    }

    private function record(): object
    {
        return DB::table('employees')->where('uuid', $this->employee)->first();
    }

    private function invite(): array
    {
        $this->post($this->base.'/'.$this->employee.'/invite', ['version' => $this->record()->version, 'confirmed' => '1'])->assertRedirect();
        $url = '';
        Mail::assertSent(EmployeeInvitationMail::class, function ($mail) use (&$url) {
            $url = $mail->invitationUrl;

            return true;
        });
        $invitation = EmployeeInvitation::latest('id')->first();
        // Bei mehreren Nachrichten interessiert der neueste ausgegebene Link.
        $mail = Mail::sent(EmployeeInvitationMail::class)->last();
        $url = $mail->invitationUrl;

        return [$invitation, parse_url($url, PHP_URL_FRAGMENT), '/employee-invitations/'.$invitation->uuid];
    }

    private function accept(): array
    {
        [$invitation, $token, $url] = $this->invite();
        $this->get($url)->assertOk()->assertDontSee('Testperson');
        $this->assertSame('ready', $invitation->fresh()->status);
        $this->post($url.'/accept', ['token' => $token])->assertRedirect();
        $this->get($url)->assertOk()->assertSee('Ihre Selbstauskunft')->assertSee('Testperson')->assertDontSee('person_group_code');

        return [$invitation->fresh(), $url];
    }

    private function personal(): array
    {
        return ['person' => ['first_name' => 'Fiktive', 'last_name' => 'Selbstauskunft', 'birth_date' => '1990-01-01', 'street' => 'Fiktiver Weg', 'house_number' => '7', 'postal_code' => '12345', 'city' => 'Musterort', 'country_code' => 'DE'], 'jobs' => ['has_other_jobs' => 'no']];
    }

    private function employer(): array
    {
        return ['employment' => ['started_on' => '2026-11-01', 'profession_title' => 'Fiktive Verkaufskraft', 'weekly_hours' => '20', 'annual_vacation_days' => '20', 'contract_form' => 'part_time', 'fixed_term_type' => 'permanent'], 'pay_components' => [['label' => 'Fiktive Vergütung', 'hourly_rate' => '25.00', 'hourly_valid_from' => '2026-11']]];
    }

    private function submit(array $invitationAndUrl): void
    {
        [$invitation, $url] = $invitationAndUrl;
        $this->post($url, ['action' => 'submit', 'version' => $invitation->fresh()->version, 'confirmed' => '1', 'data' => $this->personal()])->assertRedirect();
    }

    public function test_complete_flow_return_resubmit_and_explicit_approval(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->post($url, ['action' => 'save', 'version' => $invitation->version, 'data' => $this->personal()])->assertRedirect();
        $this->get($this->base.'/'.$this->employee)->assertOk()->assertDontSee('value="Selbstauskunft"');
        $this->submit([$invitation, $url]);
        $this->assertSame('[]', Crypt::decryptString($this->record()->personal_data));
        $this->get($this->base.'/'.$this->employee)->assertOk()->assertSee('value="Selbstauskunft"', false);
        $this->post($this->base.'/'.$this->employee, ['action' => 'return', 'version' => $this->record()->version, 'submission_version' => $invitation->fresh()->version, 'station_id' => $this->station, 'question' => 'Bitte die Hausnummer prüfen.', 'data' => []])->assertRedirect();
        $this->get($url)->assertOk()->assertSee('Bitte die Hausnummer prüfen.');
        $this->submit([$invitation, $url]);
        $payload = ['action' => 'approve', 'version' => $this->record()->version, 'submission_version' => $invitation->fresh()->version, 'station_id' => $this->station, 'personnel_number' => '0007', 'data' => $this->employer()];
        $missingConfirmation = $this->post($this->base.'/'.$this->employee, $payload)->assertStatus(422)
            ->assertSee('Arbeitgeberangaben')->assertSee('Bitte die Prüfung ausdrücklich bestätigen.')
            ->assertSee('value="0007"', false)->assertSessionMissing('_old_input');
        $this->assertDoesNotMatchRegularExpression('/<details\b[^>]*\bopen\b[^>]*>\s*<summary>Person &amp; Anschrift<\/summary>/', $missingConfirmation->getContent());
        $this->post($this->base.'/'.$this->employee, $payload + ['confirmed' => '1'])->assertRedirect();
        $this->assertSame('approved', $this->record()->status);
        $this->assertSame('0007', $this->record()->personnel_number);
        $this->assertDatabaseCount('employee_reviews', 2);
        $this->get($url)->assertGone();
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame(1, DB::table('partner_memberships')->count());
        $this->assertStringNotContainsString('Selbstauskunft', $this->record()->personal_data);
    }

    public function test_invitation_is_one_time_expiring_and_resend_invalidates_session(): void
    {
        $this->createEmployee();
        [$invitation, $token, $url] = $this->invite();
        $this->post($url.'/accept', ['token' => str_repeat('x', 64)])->assertGone();
        $this->post($url.'/accept', ['token' => $token])->assertRedirect();
        $this->post($url.'/accept', ['token' => $token])->assertGone();
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $this->personal()])->assertRedirect();
        $this->invite();
        $this->get($url)->assertGone();
        $this->assertSame('revoked', $invitation->fresh()->status);
        $latest = EmployeeInvitation::latest('id')->first();
        $this->assertSame('Selbstauskunft', $latest->data['person']['last_name']);
    }

    public function test_wrong_partner_foreign_station_and_roles_cannot_read_or_write(): void
    {
        $this->createEmployee();
        foreach ([PartnerRole::Manager, PartnerRole::Staff, PartnerRole::Viewer] as $role) {
            PartnerMembership::where('user_id', $this->owner->id)->update(['role' => $role->value]);
            $this->get($this->base)->assertForbidden();
            $this->get($this->base.'/'.$this->employee)->assertForbidden();
            $this->post($this->base.'/'.$this->employee.'/invite', ['version' => 1, 'confirmed' => '1'])->assertForbidden();
        }
        PartnerMembership::where('user_id', $this->owner->id)->update(['role' => 'owner']);
        $other = Partner::create(['name' => 'Fremder Partner']);
        $this->get('/app/partners/'.$other->id.'/employees/'.$this->employee)->assertNotFound();
        $super = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($super)->get($this->base.'/'.$this->employee)->assertNotFound();
        Mail::assertNothingSent();
    }

    public function test_expired_test_blocks_self_writes_and_owner_review_but_not_authorized_read(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->get($url)->assertOk()->assertSee('nur lesend');
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $this->personal()])->assertForbidden();
        $this->post($this->base.'/'.$this->employee, ['action' => 'save'])->assertForbidden();
        $this->get($this->base.'/'.$this->employee)->assertOk();
        $this->assertSame([], $invitation->fresh()->data);
        $this->post($this->base.'/'.$this->employee.'/revoke')->assertRedirect();
        $this->get($url)->assertGone();
    }

    public function test_invitation_expiry_revocation_and_no_grant_are_enforced(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->post($this->base.'/'.$this->employee.'/revoke')->assertRedirect();
        $this->post($url, ['action' => 'submit', 'version' => 1, 'data' => $this->personal()])->assertGone();
        $this->assertNull($invitation->fresh()->grant_hash);
        $this->invite();
        $invitation = EmployeeInvitation::latest('id')->first();
        $token = parse_url(Mail::sent(EmployeeInvitationMail::class)->last()->invitationUrl, PHP_URL_FRAGMENT);
        $this->travel(8)->days();
        $this->post('/employee-invitations/'.$invitation->uuid.'/accept', ['token' => $token])->assertGone();
    }

    public function test_selfservice_rejects_employer_keys_and_sensitive_values_never_flash(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $data = $this->personal();
        $data['payroll'] = ['tax_class' => '6'];
        $this->post($url, ['action' => 'submit', 'version' => 1, 'confirmed' => '1', 'data' => $data])->assertStatus(422);
        $this->assertSame([], $invitation->fresh()->data);
        $data = $this->personal();
        $data['insurance'] = ['tax_id' => 'invalid-secret-value'];
        $response = $this->post($url, ['action' => 'submit', 'version' => 1, 'data' => $data])->assertStatus(422)->assertSessionMissing('_old_input');
        $response->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertSame([], $invitation->fresh()->data);
    }

    public function test_stale_forms_missing_required_fields_and_double_submit_are_safe(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->post($url, ['action' => 'submit', 'version' => 1, 'confirmed' => '1', 'data' => []])->assertStatus(422);
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $this->personal()])->assertRedirect();
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => []])->assertConflict();
        $this->submit([$invitation, $url]);
        $this->post($url, ['action' => 'submit', 'version' => 2, 'confirmed' => '1', 'data' => $this->personal()])->assertRedirect();
        $this->assertSame(3, $invitation->fresh()->version);
    }

    public function test_raw_database_and_model_serialization_do_not_reveal_private_data(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $data = $this->personal();
        $data['insurance'] = ['tax_id' => '12345678901'];
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $data])->assertRedirect();
        $raw = DB::table('employee_invitations')->where('id', $invitation->id)->first();
        $this->assertStringNotContainsString('12345678901', $raw->data);
        $this->assertStringNotContainsString('personal-test@example.test', $this->record()->identity);
        $this->assertStringNotContainsString('12345678901', $invitation->fresh()->toJson());
        $this->assertNull($raw->token_hash);
    }

    public function test_profiles_and_conditional_groups_are_validated_without_inventing_legal_rules(): void
    {
        $data = $this->personal();
        $data['bank'] = ['payment_method' => 'transfer'];
        try {
            EmployeeFields::validate($data, 'personal', 'minijob', true);
            $this->fail('Fehlende IBAN wurde akzeptiert.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('data.bank.salary_iban', $error->errors());
        }
        $data = $this->personal();
        $data['insurance'] = ['pension_exemption_request' => 'no'];
        $this->assertSame('no', EmployeeFields::validate($data, 'personal', 'minijob', true)['insurance']['pension_exemption_request']);
        try {
            EmployeeFields::validate($data, 'personal', 'short_term', true);
            $this->fail('Minijob-RV-Feld ist in kurzfristigem Profil nicht zulässig.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $this->assertArrayHasKey('children', EmployeeFields::repeats('personal', 'training'));
        $this->assertArrayNotHasKey('children', EmployeeFields::repeats('personal', 'minijob'));
        $this->assertArrayHasKey('training_year', EmployeeFields::repeats('employer', 'training')['pay_components']['fields']);
    }

    public function test_minijob_and_training_have_working_forms_and_profile_specific_validation(): void
    {
        foreach (['minijob', 'short_term', 'training'] as $profile) {
            $this->get($this->base.'/create')->assertOk();
            $key = (string) Str::uuid();
            $this->withSession(['employee_creation.'.$this->partner->id => [$key]])->post($this->base.'/create', [
                'creation_key' => $key, 'first_name' => 'Fiktive', 'last_name' => 'Profilperson', 'email' => $profile.'@example.test', 'station_id' => $this->station, 'profile' => $profile,
            ])->assertRedirect();
            $this->employee = DB::table('employees')->latest('id')->value('uuid');
            $this->get($this->base.'/'.$this->employee)->assertOk()->assertSee(EmployeeFields::PROFILES[$profile]);
            $data = EmployeeFields::validate($this->personal(), 'personal', $profile, true);
            $this->assertSame('Selbstauskunft', $data['person']['last_name']);
        }
    }

    public function test_cross_partner_station_is_rejected_and_database_constraint_prevents_reassignment(): void
    {
        $other = Partner::create(['name' => 'Fremder Personalpartner']);
        $otherStation = DB::table('stations')->insertGetId(['partner_id' => $other->id, 'name' => 'Fremde Station', 'created_at' => now(), 'updated_at' => now()]);
        $key = (string) Str::uuid();
        $this->withSession(['employee_creation.'.$this->partner->id => [$key]])->post($this->base.'/create', [
            'creation_key' => $key, 'first_name' => 'Fiktive', 'last_name' => 'Fremdzuordnung', 'email' => 'foreign@example.test', 'station_id' => $otherStation, 'profile' => 'general',
        ])->assertNotFound();
        $this->assertDatabaseCount('employees', 0);
        $this->createEmployee();
        try {
            DB::table('employees')->where('uuid', $this->employee)->update(['station_id' => $otherStation]);
            $this->fail('Datenbank hat fremde Station akzeptiert.');
        } catch (QueryException) {
            $this->assertSame($this->station, (int) $this->record()->station_id);
        }
    }

    public function test_revoked_owner_and_inactive_partner_cannot_continue(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        PartnerMembership::where('user_id', $this->owner->id)->update(['is_active' => false]);
        $this->get($this->base.'/'.$this->employee)->assertNotFound();
        $this->partner->forceFill(['is_active' => false])->save();
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $this->personal()])->assertGone();
        $this->assertSame([], $invitation->fresh()->data);
    }

    public function test_sensitive_draft_remains_private_after_return_and_is_copied_only_on_submission(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->submit([$invitation, $url]);
        $this->post($this->base.'/'.$this->employee, ['action' => 'return', 'version' => $this->record()->version, 'submission_version' => $invitation->fresh()->version, 'station_id' => $this->station, 'question' => 'Bitte prüfen.', 'data' => []])->assertRedirect();
        $changed = $this->personal();
        $changed['person']['last_name'] = 'PrivaterZwischenstand';
        $this->post($url, ['action' => 'save', 'version' => $invitation->fresh()->version, 'data' => $changed])->assertRedirect();
        $this->get($this->base.'/'.$this->employee)->assertOk()->assertDontSee('PrivaterZwischenstand')->assertSee('Selbstauskunft');
        $this->assertSame('Selbstauskunft', $invitation->fresh()->submitted_data['person']['last_name']);
    }

    public function test_withdrawn_access_does_not_destroy_an_already_submitted_review(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->submit([$invitation, $url]);
        $this->post($this->base.'/'.$this->employee.'/revoke')->assertRedirect();
        $this->assertSame('submitted', $invitation->fresh()->status);
        $this->get($url)->assertGone();
        $this->post($this->base.'/'.$this->employee, ['action' => 'approve', 'version' => $this->record()->version, 'submission_version' => $invitation->fresh()->version, 'confirmed' => '1', 'station_id' => $this->station, 'personnel_number' => '0008', 'data' => $this->employer()])->assertRedirect();
        $this->assertSame('approved', $this->record()->status);
    }

    public function test_malformed_repeated_values_and_unknown_keys_return_validation_without_flash(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        foreach ([['other_jobs' => 'wrong'], ['other_jobs' => [['employer_name' => ['nested']]]], ['person' => ['user_id' => '1']], ['other_jobs' => array_fill(0, 21, ['employer_name' => 'Fiktiv'])]] as $data) {
            $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $data])->assertStatus(422)->assertSessionMissing('_old_input');
        }
        $this->assertSame([], $invitation->fresh()->data);
    }

    public function test_failed_mail_revokes_new_secret_and_retains_employee_without_exposing_transport_details(): void
    {
        $this->createEmployee();
        $transport = \Mockery::mock();
        $transport->shouldReceive('to')->once()->andReturnSelf();
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('private-auth-diagnostic'));
        Mail::shouldReceive('mailer')->with('array')->once()->andReturn($transport);
        $this->post($this->base.'/'.$this->employee.'/invite', ['version' => 1, 'confirmed' => '1'])->assertRedirect()->assertSessionHas('status', fn ($value) => str_contains($value, 'Versand fehlgeschlagen') && ! str_contains($value, 'private-auth'));
        $invitation = EmployeeInvitation::latest('id')->first();
        $this->assertSame('failed', $invitation->status);
        $this->assertNull($invitation->token_hash);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_invitation_confirmation_and_stale_version_return_notifications_without_personal_flash(): void
    {
        $this->createEmployee();
        $url = $this->base.'/'.$this->employee;
        $this->post($url.'/invite', ['version' => 1])->assertRedirect($url)
            ->assertSessionHas('status_type', 'error')->assertSessionMissing('_old_input');
        $this->get($url)->assertOk()->assertSee('Aktion nicht abgeschlossen')->assertSee('data-dismiss-notification', false);
        Mail::assertNothingSent();
        $this->post($url.'/invite', ['version' => 99, 'confirmed' => '1'])->assertRedirect($url)
            ->assertSessionHas('status_type', 'error')->assertSessionMissing('_old_input');
        $this->assertDatabaseCount('employee_invitations', 0);
    }

    public function test_smtp_failure_category_is_retained_and_visible_after_reload_without_raw_details(): void
    {
        $this->createEmployee();
        $transport = \Mockery::mock();
        $transport->shouldReceive('to')->once()->andReturnSelf();
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('535 authentication failed private-secret@example.test'));
        Mail::shouldReceive('mailer')->with('array')->once()->andReturn($transport);
        $url = $this->base.'/'.$this->employee;
        $this->post($url.'/invite', ['version' => 1, 'confirmed' => '1'])->assertRedirect($url)->assertSessionHas('status_type', 'error');
        $invitation = EmployeeInvitation::latest('id')->first();
        $this->assertSame('auth', $invitation->failure_code);
        $this->assertNull($invitation->token_hash);
        $this->get($url)->assertOk()->assertSee('Anmeldung abgelehnt')->assertDontSee('private-secret');
        $this->get($url)->assertOk()->assertSee('Anmeldung abgelehnt')->assertDontSee('private-secret');
    }

    public function test_incomplete_mail_settings_keep_the_record_and_show_an_actionable_notification(): void
    {
        $this->createEmployee();
        PlatformMailSetting::forceCreate(['id' => 1, 'enabled' => true]);
        $url = $this->base.'/'.$this->employee;
        $this->post($url.'/invite', ['version' => 1, 'confirmed' => '1'])->assertRedirect($url)
            ->assertSessionHas('status_type', 'error')->assertSessionHas('status', fn ($message) => str_contains($message, 'vervollständigen'));
        $this->assertDatabaseCount('employee_invitations', 0);
        $this->assertSame(1, $this->record()->version);
        Mail::assertNothingSent();
    }

    public function test_mail_failure_codes_are_whitelisted_and_never_echo_diagnostics(): void
    {
        $mail = app(RegistrationMail::class);
        foreach (['certificate private-secret' => 'tls', 'timed out private-secret' => 'timeout', '535 private-secret' => 'auth',
            'RCPT TO private-secret' => 'recipient', 'MAIL FROM private-secret' => 'sender', 'connection private-secret' => 'connection',
            'unknown private-secret' => 'rejected'] as $message => $code) {
            $this->assertSame($code, $mail->failureCode(new TransportException($message)));
            $this->assertStringNotContainsString('private-secret', $mail->deliveryFailureMessage($code));
        }
        $this->assertStringNotContainsString('private-secret', $mail->deliveryFailureMessage('private-secret'));
    }

    public function test_selfservice_wizard_keeps_iban_validation_server_side_and_opens_the_error_step(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->get($url)->assertOk()->assertSee('data-employee-wizard', false)
            ->assertSee('data-iban-input', false)->assertSee('data-iban-lengths', false)->assertSee('data-wizard-review', false);
        $data = $this->personal();
        $data['bank'] = ['payment_method' => 'transfer', 'salary_iban' => 'DE00000000000000000000'];
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $data])->assertStatus(422)
            ->assertSee('gültiger Prüfziffer')->assertSee('data-wizard-error', false)->assertSessionMissing('_old_input');
        $this->assertSame([], $invitation->fresh()->data);
        // Rein synthetische Null-Kontonummer mit rechnerisch passender Prüfziffer;
        // die formale Prüfung behauptet ausdrücklich keine Kontoexistenz.
        $data['bank']['salary_iban'] = 'de36 0000 0000 0000 0000 00';
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $data, 'wizard_step' => 2])->assertRedirect()->assertSessionHas('employee_wizard_step', 2);
        $this->assertSame('DE36000000000000000000', data_get($invitation->fresh()->data, 'bank.salary_iban'));
        $this->get($url)->assertSee('Keine Konto- oder Inhaberbestätigung.');
    }

    private function managementPayload(): array
    {
        return ['version' => $this->record()->version, 'profile' => 'general', 'email' => 'owner-new@example.test',
            'station_id' => $this->station, 'personnel_number' => '00088', 'personal_data' => $this->personal(),
            'employer_data' => $this->employer(), 'reason' => 'Fiktive Inhaberkorrektur', 'confirmed' => '1'];
    }

    public function test_owner_edits_personal_and_employer_data_and_invalidates_previous_access_without_sending_mail(): void
    {
        $this->createEmployee();
        [$invitation,$url] = $this->accept();
        $draft = $this->personal();
        $draft['person']['city'] = 'Privater Entwurfsort';
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $draft])->assertRedirect();
        $edit = $this->base.'/'.$this->employee.'/edit';
        $this->get($edit)->assertOk()->assertDontSee('Privater Entwurfsort')->assertSee('personal_data[person][first_name]', false);
        $payload = $this->managementPayload();
        $this->post($edit, $payload)->assertRedirect();
        $this->assertSame('managed', $this->record()->status);
        $this->assertSame('00088', $this->record()->personnel_number);
        $this->assertSame('Musterort', data_get(json_decode(Crypt::decryptString($this->record()->personal_data), true), 'person.city'));
        $this->assertNull($invitation->fresh()->grant_hash);
        $this->assertSame('revoked', $invitation->fresh()->status);
        $this->get($url)->assertGone();
        $this->get($this->base.'/'.$this->employee)->assertOk()->assertSee('Vom Inhaber gepflegte Stammdaten');
        $this->assertDatabaseHas('employee_reviews', ['action' => 'edit']);
        $this->invite();
        $latest = EmployeeInvitation::latest('id')->first();
        $this->assertSame('Musterort', data_get($latest->data, 'person.city'));
        $this->assertNull($latest->submitted_data);
        $this->assertStringNotContainsString('Privater Entwurfsort', json_encode($latest->data));
        Mail::assertSent(EmployeeInvitationMail::class, fn ($mail) => $mail->hasTo('owner-new@example.test'));
    }

    public function test_owner_can_edit_a_previously_approved_record_without_rewriting_its_approval_snapshot(): void
    {
        $this->createEmployee();
        [$invitation,$url] = $this->accept();
        $this->submit([$invitation, $url]);
        $this->post($this->base.'/'.$this->employee, ['version' => $this->record()->version, 'submission_version' => $invitation->fresh()->version,
            'action' => 'approve', 'confirmed' => '1', 'station_id' => $this->station, 'personnel_number' => '0005', 'data' => $this->employer()])->assertRedirect();
        $snapshot = DB::table('employee_reviews')->where('action', 'approve')->value('snapshot');
        $this->get($this->base.'/'.$this->employee.'/edit')->assertOk();
        $payload = $this->managementPayload();
        $payload['personal_data']['person']['city'] = 'Korrigierter Musterort';
        $this->post($this->base.'/'.$this->employee.'/edit', $payload)->assertRedirect();
        $this->assertSame('managed', $this->record()->status);
        $this->assertSame($snapshot, DB::table('employee_reviews')->where('action', 'approve')->value('snapshot'));
        $this->assertDatabaseHas('employee_reviews', ['action' => 'edit']);
    }

    public function test_trash_is_confirmed_tenant_scoped_and_reversible_without_restoring_old_links(): void
    {
        $this->createEmployee();
        [$invitation,$url] = $this->accept();
        $base = $this->base.'/'.$this->employee;
        $this->post($base.'/trash', ['version' => $this->record()->version])->assertRedirect()->assertSessionHas('status_type', 'error');
        $this->assertNull($this->record()->deleted_at);
        $this->post($base.'/trash', ['version' => $this->record()->version, 'confirmed' => '1'])->assertRedirect();
        $this->assertNotNull($this->record()->deleted_at);
        $this->post($this->base.'/create', ['creation_key' => $this->record()->creation_key, 'first_name' => 'Fiktive', 'last_name' => 'Testperson',
            'email' => 'personal-test@example.test', 'station_id' => $this->station, 'profile' => 'general'])->assertConflict();
        $this->assertDatabaseCount('employees', 1);
        $this->get($base)->assertNotFound();
        $this->get($base.'/edit')->assertNotFound();
        $this->get($url)->assertGone();
        $this->get($this->base)->assertOk()->assertDontSee('Fiktive Testperson');
        $this->get($this->base.'/trash')->assertOk()->assertSee('Testperson');
        $this->post($base.'/restore', ['version' => $this->record()->version])->assertRedirect();
        $this->assertNull($this->record()->deleted_at);
        $this->get($base)->assertOk();
        $this->get($url)->assertGone();
        $this->assertNull($invitation->fresh()->grant_hash);
        $this->assertDatabaseHas('employee_reviews', ['action' => 'trash']);
        $this->assertDatabaseHas('employee_reviews', ['action' => 'restore']);
        Mail::assertSentCount(1);
    }

    public function test_management_rejects_wrong_roles_foreign_tenants_expired_trials_stale_versions_and_untrusted_fields(): void
    {
        $this->createEmployee();
        $base = $this->base.'/'.$this->employee;
        $payload = $this->managementPayload();
        foreach ([PartnerRole::Manager, PartnerRole::Staff, PartnerRole::Viewer] as $role) {
            PartnerMembership::where('user_id', $this->owner->id)->update(['role' => $role->value]);
            $this->get($base.'/edit')->assertForbidden();
            $this->post($base.'/edit', $payload)->assertForbidden();
            $this->post($base.'/trash', ['version' => 1, 'confirmed' => '1'])->assertForbidden();
            $this->get($this->base.'/trash')->assertForbidden();
            $this->post($base.'/restore', ['version' => 1])->assertForbidden();
        }
        PartnerMembership::where('user_id', $this->owner->id)->update(['role' => 'owner']);
        $other = Partner::create(['name' => 'Anderer fiktiver Partner']);
        $this->get('/app/partners/'.$other->id.'/employees/'.$this->employee.'/edit')->assertNotFound();
        $foreignStation = (array) DB::table('stations')->where('id', $this->station)->first();
        unset($foreignStation['id']);
        $foreignStation['partner_id'] = $other->id;
        $foreignId = DB::table('stations')->insertGetId($foreignStation);
        $this->post($base.'/edit', array_replace($payload, ['station_id' => $foreignId]))->assertNotFound();
        $this->get('/app/partners/'.$other->id.'/employees/trash')->assertNotFound();
        $super = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($super)->get($base.'/edit')->assertNotFound();
        $this->actingAs($this->owner);

        $this->post($base.'/edit', array_replace($payload, ['role' => 'owner']))->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post($base.'/edit', array_replace($payload, ['version' => 999]))->assertConflict();
        $this->post($base.'/trash', ['version' => 999, 'confirmed' => '1'])->assertConflict();
        $payload['personal_data']['bank'] = ['salary_iban' => 'DE00000000000000000000'];
        $this->post($base.'/edit', $payload)->assertStatus(422)->assertSee('gültiger Prüfziffer')->assertSessionMissing('_old_input');
        $this->assertSame('draft', $this->record()->status);
        $this->partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->get($base.'/edit')->assertForbidden();
        $this->post($base.'/edit', $this->managementPayload())->assertForbidden();
        $this->post($base.'/trash', ['version' => 1, 'confirmed' => '1'])->assertForbidden();
        Mail::assertNothingSent();
    }

    public function test_owner_profile_change_uses_the_selected_whitelist_and_trash_reserves_personnel_number(): void
    {
        $this->createEmployee();
        $edit = $this->base.'/'.$this->employee.'/edit';
        $this->get($edit.'?profile=minijob')->assertOk()->assertSee('RV-Befreiung gewünscht?');
        $payload = $this->managementPayload();
        $payload['profile'] = 'minijob';
        $this->post($edit, $payload)->assertRedirect();
        $this->assertSame('minijob', $this->record()->profile);
        $this->post($edit, array_replace($payload, ['version' => $this->record()->version, 'personal_data' => ['person' => ['first_name' => 'Fiktiv', 'last_name' => 'Test'], 'children' => [['first_name' => 'Nicht erlaubt']]]]))->assertStatus(422);
        $other = (array) $this->record();
        unset($other['id']);
        $other['uuid'] = (string) Str::uuid();
        $other['creation_key'] = (string) Str::uuid();
        $other['personnel_number'] = '00999';
        $other['deleted_at'] = now();
        DB::table('employees')->insert($other);
        $this->post($edit, array_replace($payload, ['version' => $this->record()->version, 'personnel_number' => '00999']))->assertStatus(422)->assertSee('Papierkorb');
        $this->assertSame('00088', $this->record()->personnel_number);
    }

    public function test_mail_is_neutral_and_secret_is_not_in_request_path_or_query(): void
    {
        $this->createEmployee();
        [$invitation, $token, $url] = $this->invite();
        $mail = Mail::sent(EmployeeInvitationMail::class)->last();
        $html = $mail->render();
        $this->assertStringNotContainsString('Testperson', $html);
        $this->assertStringNotContainsString('personal-test@example.test', $html);
        $this->assertNull(parse_url($mail->invitationUrl, PHP_URL_QUERY));
        $this->assertStringNotContainsString($token, parse_url($mail->invitationUrl, PHP_URL_PATH));
        $this->get($url)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Frame-Options', 'DENY')->assertDontSee($token);
    }

    public function test_another_session_cannot_reuse_invitation_uuid_without_its_grant(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->withSession(['employee_access.'.$invitation->uuid => str_repeat('wrong', 12)])
            ->post($url, ['action' => 'save', 'version' => 1, 'data' => $this->personal()])->assertGone();
        $this->assertSame([], $invitation->fresh()->data);
    }

    public function test_personnel_number_collision_stale_review_and_missing_employer_data_are_rejected(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $this->submit([$invitation, $url]);
        $version = $this->record()->version;
        $payload = ['action' => 'approve', 'version' => $version, 'submission_version' => $invitation->fresh()->version,
            'confirmed' => '1', 'station_id' => $this->station, 'personnel_number' => '0009', 'data' => []];
        $this->post($this->base.'/'.$this->employee, $payload)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post($this->base.'/'.$this->employee, array_replace($payload, ['data' => $this->employer(), 'version' => $version - 1]))->assertConflict();
        $this->post($this->base.'/'.$this->employee, array_replace($payload, ['data' => $this->employer(), 'submission_version' => 0]))->assertConflict();
        $other = (array) $this->record();
        unset($other['id']);
        $other['uuid'] = (string) Str::uuid();
        $other['creation_key'] = (string) Str::uuid();
        $other['personnel_number'] = '0009';
        DB::table('employees')->insert($other);
        $this->post($this->base.'/'.$this->employee, array_replace($payload, ['data' => $this->employer()]))->assertStatus(422);
        $this->assertSame('submitted', $this->record()->status);
        $this->assertDatabaseCount('employee_reviews', 0);
    }

    public function test_mail_rate_limit_has_its_own_bucket_and_does_not_grant_extra_access(): void
    {
        $this->createEmployee();
        for ($i = 0; $i < 3; $i++) {
            $this->invite();
        }
        $this->post($this->base.'/'.$this->employee.'/invite', ['version' => $this->record()->version, 'confirmed' => '1'])->assertStatus(429);
        Mail::assertSentCount(3);
        $this->assertSame(1, EmployeeInvitation::where('status', 'ready')->count());
        $this->assertSame(2, EmployeeInvitation::where('status', 'revoked')->count());
    }

    public function test_conditional_vwl_and_employment_data_are_kept_as_drafts_and_checked_before_submission(): void
    {
        $data = $this->personal();
        $data['vwl'] = ['recipient' => 'Fiktiver Vertragsanbieter'];
        $this->assertSame('Fiktiver Vertragsanbieter', EmployeeFields::validate($data, 'personal', 'general', false)['vwl']['recipient']);
        try {
            EmployeeFields::validate($data, 'personal', 'general', true);
            $this->fail('VWL-Details ohne Entscheidung akzeptiert.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('data.vwl', $error->errors());
        }
        $data = $this->employer();
        $data['employment']['fixed_term_type'] = 'fixed_date';
        $data['employment']['fixed_term_end_on'] = '2025-01-01';
        try {
            EmployeeFields::validate($data, 'employer', 'general', true);
            $this->fail('Vertragsende vor Beginn akzeptiert.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('data.employment.fixed_term_end_on', $error->errors());
        }
        $data['employment']['fixed_term_end_on'] = '2027-01-01';
        $data['pay_components'][0]['hourly_rate'] = '25,50';
        $this->assertSame('25.50', EmployeeFields::validate($data, 'employer', 'general', true)['pay_components'][0]['hourly_rate']);
    }

    public function test_invitation_uses_configured_smtp_sender_without_contacting_the_transport(): void
    {
        PlatformMailSetting::forceCreate(['id' => 1, 'enabled' => true, 'from_address' => 'sender@example.test', 'from_name' => 'Fiktiver Nova-Absender']);
        $mail = (new EmployeeInvitationMail('http://127.0.0.1:8097/employee-invitations/'.Str::uuid().'#'.Str::random(64)))->build();
        $this->assertSame('sender@example.test', $mail->from[0]['address']);
        $this->assertSame('Fiktiver Nova-Absender', $mail->from[0]['name']);
        $this->assertSame('employees.public.invitation-text', $mail->textView);
        Mail::assertNothingSent();
    }

    public function test_document_drafts_are_private_encrypted_and_only_shared_on_submission(): void
    {
        config(['employee_documents.storage_root' => storage_path('framework/testing/employee-documents')]);
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $self = $url.'/documents';
        $owner = $this->base.'/'.$this->employee.'/documents';
        $this->get($self)->assertOk()->assertSee('keine Gesundheitsunterlagen')->assertSee('QR-Code nach HTTPS-Freigabe');
        $this->post($self, ['confirmed' => '1', 'document' => UploadedFile::fake()->image('Fiktiver-Nachweis.png', 40, 40)])->assertRedirect();
        $document = DB::table('employee_documents')->first();
        $this->assertNotSame('Fiktiver-Nachweis.png', $document->original_name);
        $encrypted = app(EmployeeDocuments::class)->disk()->get($document->storage_path);
        $this->assertStringNotContainsString('PNG', $encrypted);
        $this->assertStringStartsWith("\x89PNG", Crypt::decryptString($encrypted));
        $this->get($self.'/'.$document->uuid)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($owner)->assertOk()->assertDontSee('Fiktiver-Nachweis.png');
        $this->get($owner.'/'.$document->uuid)->assertNotFound();
        $this->submit([$invitation, $url]);
        $this->get($owner)->assertOk()->assertSee('Fiktiver-Nachweis.png');
        $this->get($owner.'/'.$document->uuid)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post($self.'/'.$document->uuid, ['confirmed' => '1'])->assertForbidden();
        $this->post($self, ['confirmed' => '1', 'document' => UploadedFile::fake()->image('late.png')])->assertForbidden();
        $this->partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->get($owner.'/'.$document->uuid)->assertOk();
        $this->post($owner, ['confirmed' => '1', 'document' => UploadedFile::fake()->image('late.png')])->assertForbidden();
        app(EmployeeDocuments::class)->disk()->delete($document->storage_path);
    }

    public function test_document_upload_rejects_bad_content_missing_confirmation_and_revoked_access(): void
    {
        $this->createEmployee();
        [$invitation, $url] = $this->accept();
        $path = $url.'/documents';
        foreach ([['evil.jpg', '<html>bad</html>'], ['active.pdf', "%PDF-1.4\n/Java#53cript (bad)\n%%EOF"], ['broken.pdf', '%PDF-1.4 no end']] as [$name,$body]) {
            $this->post($path, ['confirmed' => '1', 'document' => UploadedFile::fake()->createWithContent($name, $body)])->assertStatus(422)->assertSessionMissing('_old_input');
        }
        $this->post($path, ['document' => UploadedFile::fake()->image('missing.png')])->assertStatus(422);
        $this->post($path, ['confirmed' => '1', 'document' => UploadedFile::fake()->image('large.png', 2100, 2100)])->assertStatus(422);
        $this->assertDatabaseCount('employee_documents', 0);
        $this->post($this->base.'/'.$this->employee.'/revoke')->assertRedirect();
        $this->get($path)->assertGone();
        $this->post($path, ['confirmed' => '1', 'document' => UploadedFile::fake()->image('denied.png')])->assertGone();
    }

    public function test_owner_document_permissions_and_soft_removal(): void
    {
        config(['employee_documents.storage_root' => storage_path('framework/testing/employee-documents')]);
        $this->createEmployee();
        $path = $this->base.'/'.$this->employee.'/documents';
        $this->post($path, ['confirmed' => '1', 'document' => UploadedFile::fake()->image('Owner.png')])->assertRedirect();
        $document = DB::table('employee_documents')->first();
        $this->get($path)->assertSee('Owner.png');
        [$invitation,$url] = $this->accept();
        $this->get($url.'/documents')->assertDontSee('Owner.png');
        $this->get($url.'/documents/'.$document->uuid)->assertNotFound();
        PartnerMembership::where('user_id', $this->owner->id)->update(['role' => 'staff']);
        $this->get($path)->assertForbidden();
        $this->get($path.'/'.$document->uuid)->assertForbidden();
        PartnerMembership::where('user_id', $this->owner->id)->update(['role' => 'owner']);
        $other = Partner::create(['name' => 'Anderer fiktiver Nachweispartner']);
        $this->get('/app/partners/'.$other->id.'/employees/'.$this->employee.'/documents/'.$document->uuid)->assertNotFound();
        $this->post($path.'/'.$document->uuid, ['confirmed' => '1'])->assertRedirect();
        $this->get($path.'/'.$document->uuid)->assertNotFound();
        $this->assertNotNull(DB::table('employee_documents')->value('deleted_at'));
        app(EmployeeDocuments::class)->disk()->delete($document->storage_path);
    }

    public function test_choices_are_partner_scoped_and_free_values_do_not_extend_shared_catalog(): void
    {
        $this->post($this->base.'/choices', ['category' => 'school', 'label' => 'Fiktiver Sonderabschluss'])->assertRedirect();
        $this->post($this->base.'/choices', ['category' => 'school', 'label' => 'fiktiver Sonderabschluss'])->assertRedirect();
        $this->assertDatabaseCount('employee_choices', 1);
        $this->createEmployee();
        [$invitation,$url] = $this->accept();
        $this->get($url)->assertOk()->assertSee('Fiktiver Sonderabschluss')->assertSee('Deutschland')->assertSee('AOK');
        $data = $this->personal();
        $data['person']['nationality_code'] = 'Freier fiktiver Eintrag';
        $this->post($url, ['action' => 'save', 'version' => 1, 'data' => $data])->assertRedirect();
        $this->assertDatabaseCount('employee_choices', 1);
        $this->assertSame('Freier fiktiver Eintrag', data_get($invitation->fresh()->data, 'person.nationality_code'));
        PartnerMembership::where('user_id', $this->owner->id)->update(['role' => 'staff']);
        $this->post($this->base.'/choices', ['category' => 'school', 'label' => 'Verboten'])->assertForbidden();
        PartnerMembership::where('user_id', $this->owner->id)->update(['role' => 'owner']);
        $this->partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->post($this->base.'/choices', ['category' => 'school', 'label' => 'Abgelaufen'])->assertForbidden();
    }
}
