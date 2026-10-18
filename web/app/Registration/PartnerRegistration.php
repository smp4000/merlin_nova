<?php

namespace App\Registration;

use App\Billing\BillingCatalog;
use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\SepaMandate;
use App\Models\User;
use App\Rules\Iban;
use App\Tenancy\PartnerContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Atomare Registrierung: Zugang, Firma, Inhaberrolle und verschlüsselter
 * Mandatsentwurf entstehen gemeinsam oder gar nicht. Der Formularschlüssel
 * wird gehasht, der normalisierte Inhalt zusätzlich per HMAC gebunden.
 * Gleicher Schlüssel mit anderem Inhalt ist ein Konflikt statt Überschreiben.
 * Testbedingungen werden bei Registrierung zugesagt; Start und Ende erst
 * durch den authentifizierten, signierten E-Mail-Nachweis gesetzt.
 */
final class PartnerRegistration
{
    public const DECLARATION = 'Lokaler SEPA-Mandatsentwurf: vorbereitende Erfassung für einen später ausdrücklich abzuschließenden Vertrag. Keine Abbuchung im kostenlosen Test, keine automatische Kostenpflicht. Noch kein zum Einzug freigegebenes Mandat; Gläubigerangaben und endgültiger Mandatstext stehen aus.';

    public function register(array $input, string $token, int $policyId): array
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            throw ValidationException::withMessages(['registration' => 'Ungültiger Formularschlüssel.']);
        }
        $data = Validator::make($input, [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(14)->mixedCase()->numbers()->symbols()],
            'iban' => ['required', 'string', 'max:64', new Iban],
            'account_holder' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'regex:/\A[A-Z]{2}\z/'],
            'sepa_accepted' => ['required', 'accepted'],
            'test_accepted' => ['required', 'accepted'],
            'is_super_admin' => ['prohibited'], 'role' => ['prohibited'], 'partner_id' => ['prohibited'],
        ], [
            'required' => ':attribute ist erforderlich.', 'accepted' => ':attribute muss ausdrücklich bestätigt werden.',
            'email' => 'Bitte eine gültige E-Mail-Adresse eingeben.', 'confirmed' => 'Die Passwortwiederholung stimmt nicht überein.',
            'prohibited' => 'Unzulässige Berechtigungsangabe.', 'regex' => ':attribute hat ein ungültiges Format.',
        ], ['company_name' => 'Firmenname', 'contact_name' => 'Name', 'account_holder' => 'Kontoinhaber', 'street' => 'Straße', 'postal_code' => 'Postleitzahl', 'city' => 'Ort', 'country' => 'Land', 'sepa_accepted' => 'Mandatsentwurf', 'test_accepted' => 'Testbedingungen'])->validate();

        $data['email'] = Str::lower(trim($data['email']));
        $data['iban'] = Iban::normalize($data['iban']);
        unset($data['password_confirmation']);
        ksort($data);
        $tokenHash = hash('sha256', $token);
        $payloadHash = hash_hmac('sha256', json_encode([$data, $policyId], JSON_THROW_ON_ERROR), config('app.key'));

        try {
            return DB::transaction(function () use ($data, $tokenHash, $payloadHash, $policyId): array {
                $existing = User::where('registration_token_hash', $tokenHash)->lockForUpdate()->first();
                if ($existing) {
                    return $this->replay($existing, $payloadHash);
                }
                $policy = app(BillingCatalog::class)->activeTrialPolicy();
                if (! $policy || $policy->id !== $policyId) {
                    throw ValidationException::withMessages(['registration' => 'Die Testbedingungen wurden geändert. Bitte das Formular neu laden.']);
                }
                if (User::where('email', $data['email'])->exists()) {
                    throw ValidationException::withMessages(['registration' => 'Diese Registrierung ist nicht möglich. Bestehenden Zugang verwenden oder Angaben prüfen.']);
                }
                $user = User::create(['name' => $data['contact_name'], 'email' => $data['email'], 'password' => $data['password']]);
                $user->forceFill(['registration_token_hash' => $tokenHash, 'registration_payload_hash' => $payloadHash])->save();
                $partner = Partner::create(['name' => $data['company_name']]);
                $partner->forceFill(['registration_owner_id' => $user->id, 'trial_policy_id' => $policy->id, 'trial_duration_days' => $policy->duration_days, 'trial_station_limit' => $policy->station_limit])->save();
                PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $user->id, 'role' => PartnerRole::Owner]);
                $context = app(PartnerContext::class);
                $context->set($partner);
                try {
                    SepaMandate::forceCreate([
                        'reference' => 'NOVA-'.Str::ulid(), 'iban' => $data['iban'], 'account_holder' => $data['account_holder'],
                        'debtor_address' => array_intersect_key($data, array_flip(['street', 'postal_code', 'city', 'country'])),
                        'status' => 'prepared', 'declaration_version' => 'local-draft-v1', 'declaration_text' => self::DECLARATION, 'declared_at' => now(),
                    ]);
                } finally {
                    $context->clear();
                }

                return ['user' => $user, 'created' => true];
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            $existing = User::where('registration_token_hash', $tokenHash)->first();
            if ($existing) {
                return $this->replay($existing, $payloadHash);
            }
            throw ValidationException::withMessages(['registration' => 'Diese Registrierung ist nicht möglich. Bitte Angaben prüfen.']);
        }
    }

    private function replay(User $user, string $payloadHash): array
    {
        if (! hash_equals($user->registration_payload_hash, $payloadHash)) {
            throw ValidationException::withMessages(['registration' => 'Das Formular wurde bereits mit anderen Angaben verwendet. Bitte neu laden.']);
        }

        return ['user' => $user, 'created' => false];
    }

    /** Nur vom authentifizierten und signaturgeprüften Bestätigungs-Endpunkt aufrufen. */
    public function confirm(User $actor): void
    {
        DB::transaction(function () use ($actor): void {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            abort_unless($user->is_active, 403);
            $partner = Partner::where('registration_owner_id', $user->id)->lockForUpdate()->first();
            $newlyVerified = ! $user->hasVerifiedEmail();
            if ($newlyVerified) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            if ($partner && ! $partner->trial_started_at) {
                abort_unless($partner->is_active, 403);
                $start = now()->toImmutable();
                $partner->forceFill(['trial_started_at' => $start, 'trial_ends_at' => $start->addDays($partner->trial_duration_days)])->save();
            }
            if ($newlyVerified) {
                DB::afterCommit(fn () => event(new Verified($user)));
            }
        }, attempts: 3);
    }
}
