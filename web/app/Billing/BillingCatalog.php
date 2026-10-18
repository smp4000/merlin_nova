<?php

namespace App\Billing;

use App\Models\Tariff;
use App\Models\TariffPublication;
use App\Models\TariffVersion;
use App\Models\TrialPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Einziger schreibender Fachzugang für den Plattform-Katalog.
 * Jede Aktion prüft die Plattformberechtigung erneut, auch bei Livewire-Aufrufen.
 * Transaktionen und Zeilensperren serialisieren Versionsnummern und Zeiger.
 * Erwartete vorherige Zeiger verhindern, dass zwei geöffnete Adminfenster
 * unbemerkt gegenseitig ihre Freigabe oder Testregeländerung überschreiben.
 * Keine Aboaktivierung, Rechnungsstellung, Bankausführung oder Partneränderung.
 */
final class BillingCatalog
{
    private const MESSAGES = [
        'required' => ':attribute ist erforderlich.',
        'string' => ':attribute muss als Text angegeben werden.',
        'integer' => ':attribute muss eine ganze Zahl sein.',
        'min.numeric' => ':attribute muss mindestens :min betragen.',
        'max.numeric' => ':attribute darf höchstens :max betragen.',
        'max.string' => ':attribute darf höchstens :max Zeichen enthalten.',
        'regex' => ':attribute hat ein ungültiges Format.',
        'unique' => ':attribute ist bereits vergeben.',
        'in' => ':attribute enthält eine nicht erlaubte Auswahl.',
    ];

    private const ATTRIBUTES = [
        'code' => 'Der Tarifcode', 'name' => 'Der Anzeigename', 'description' => 'Die Beschreibung',
        'price' => 'Der Preis', 'billing_interval' => 'Die Abrechnungsperiode',
        'billing_basis' => 'Die Preisbasis', 'station_limit' => 'Das Stationslimit', 'duration_days' => 'Die Testdauer',
    ];

    public function draft(User $actor, ?int $tariffId, array $input): TariffVersion
    {
        Gate::forUser($actor)->authorize('manage-billing');
        $data = Validator::make($input, [
            'code' => [$tariffId ? 'sometimes' : 'required', 'string', 'max:64', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'string', 'regex:/\A\d{1,7}(?:[,.]\d{1,2})?\z/'],
            'billing_interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'billing_basis' => ['required', Rule::in(['partner', 'station'])],
            'station_limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ], self::MESSAGES, self::ATTRIBUTES)->validate();
        $parts = explode('.', str_replace(',', '.', $data['price']));
        $cents = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents < 1) {
            throw ValidationException::withMessages(['price' => 'Ein kostenpflichtiger Tarif muss mindestens einen Cent kosten.']);
        }

        return DB::transaction(function () use ($actor, $tariffId, $data, $cents): TariffVersion {
            if ($tariffId) {
                $tariff = Tariff::lockForUpdate()->findOrFail($tariffId);
            } else {
                Validator::make($data, ['code' => ['unique:tariffs,code']], self::MESSAGES, self::ATTRIBUTES)->validate();
                $tariff = Tariff::forceCreate(['code' => $data['code']]);
            }

            return TariffVersion::forceCreate([
                'tariff_id' => $tariff->id,
                'number' => (int) $tariff->versions()->max('number') + 1,
                'name' => $data['name'], 'description' => $data['description'] ?? null,
                'price_cents' => $cents, 'billing_interval' => $data['billing_interval'],
                'billing_basis' => $data['billing_basis'], 'station_limit' => $data['station_limit'] ?? null,
                'created_by' => $actor->id,
            ]);
        }, attempts: 3);
    }

    /** Eine bestätigte Freigabe ist idempotent; historische Fassungen niemals zurückaktivieren. */
    public function publish(User $actor, int $tariffId, int $versionId, ?int $expectedVersionId): void
    {
        Gate::forUser($actor)->authorize('manage-billing');
        DB::transaction(function () use ($actor, $tariffId, $versionId, $expectedVersionId): void {
            $tariff = Tariff::lockForUpdate()->findOrFail($tariffId);
            $version = $tariff->versions()->findOrFail($versionId);
            if ($tariff->published_version_id === $version->id) {
                return;
            }
            $currentNumber = $tariff->publishedVersion?->number ?? 0;
            if ($tariff->published_version_id !== $expectedVersionId || $version->publication()->exists() || $version->number <= $currentNumber) {
                throw ValidationException::withMessages(['publication' => 'Der Katalog wurde inzwischen geändert. Bitte neu laden.']);
            }
            TariffPublication::forceCreate(['tariff_version_id' => $version->id, 'created_by' => $actor->id]);
            $tariff->forceFill(['published_version_id' => $version->id])->save();
        }, attempts: 3);
    }

    public function trialPolicy(User $actor, array $input, ?int $expectedPolicyId): TrialPolicy
    {
        Gate::forUser($actor)->authorize('manage-billing');
        $data = Validator::make($input, [
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'station_limit' => ['required', 'integer', 'min:1', 'max:10000'],
        ], self::MESSAGES, self::ATTRIBUTES)->validate();

        return DB::transaction(function () use ($actor, $data, $expectedPolicyId): TrialPolicy {
            $control = DB::table('billing_controls')->where('id', 1)->lockForUpdate()->first();
            if ($control->trial_policy_id !== $expectedPolicyId) {
                throw ValidationException::withMessages(['trial' => 'Die Testregeln wurden inzwischen geändert. Bitte neu laden.']);
            }
            $policy = TrialPolicy::forceCreate([...$data, 'created_by' => $actor->id]);
            DB::table('billing_controls')->where('id', 1)->update(['trial_policy_id' => $policy->id]);

            return $policy;
        }, attempts: 3);
    }

    /** Unkonfiguriert bleibt null: kein stiller kostenloser Zeitraum aus Beispielwerten. */
    public function activeTrialPolicy(): ?TrialPolicy
    {
        $identifier = DB::table('billing_controls')->where('id', 1)->value('trial_policy_id');

        return $identifier ? TrialPolicy::findOrFail($identifier) : null;
    }

    /** Öffentliche Angebotsgrundlage ohne Entwürfe oder interne Bearbeiterdaten. Noch keine HTTP-API. */
    public function publishedOffers(): Collection
    {
        return TariffVersion::whereIn('id', Tariff::select('published_version_id')->whereNotNull('published_version_id'))
            ->get(['id', 'tariff_id', 'number', 'name', 'description', 'price_cents', 'billing_interval', 'billing_basis', 'station_limit']);
    }
}
