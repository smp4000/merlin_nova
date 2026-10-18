<?php

namespace App\Filament\Pages;

use App\Billing\BillingCatalog;
use App\Models\Tariff;
use App\Models\TariffVersion;
use App\Models\TrialPolicy;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * Eigene ruhige Plattformseite mit getrennten Formularen für Testregeln,
 * Tarifentwürfe und ausdrückliche Veröffentlichung. Keine Standard-CRUD-
 * Ressource, die historische Fassungen versehentlich editierbar machen würde.
 * Gesperrte Livewire-Zeiger tragen die beim Laden gesehenen Versionsstände;
 * jeder Fachaufruf prüft zusätzlich im Service die aktuelle Berechtigung.
 */
class BillingSettings extends Page
{
    protected static ?string $title = 'Tarife & Testphase';

    protected static ?string $slug = 'tarife';

    protected string $view = 'filament.pages.billing-settings';

    #[Locked]
    public ?int $selectedTariffId = null;

    #[Locked]
    public ?int $expectedVersionId = null;

    #[Locked]
    public ?int $expectedPolicyId = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public string $price = '';

    public string $billingInterval = 'monthly';

    public string $billingBasis = 'partner';

    public string $stationLimit = '';

    public string $trialDays = '';

    public string $trialStationLimit = '';

    public bool $confirmPublication = false;

    public string $notice = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage-billing') ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $policy = app(BillingCatalog::class)->activeTrialPolicy();
        $this->expectedPolicyId = $policy?->id;
        $this->trialDays = $policy ? (string) $policy->duration_days : '';
        $this->trialStationLimit = $policy ? (string) $policy->station_limit : '';
    }

    /** Auswahl lädt eine Kopie zur Überarbeitung, niemals ein editierbares historisches Objekt. */
    public function chooseTariff(?string $identifier = null): void
    {
        abort_unless(static::canAccess(), 403);
        $this->resetErrorBag();
        $this->confirmPublication = false;
        $this->notice = '';
        if (! $identifier) {
            $this->reset('selectedTariffId', 'expectedVersionId', 'code', 'name', 'description', 'price', 'stationLimit', 'billingInterval', 'billingBasis');

            return;
        }
        abort_unless(ctype_digit($identifier), 404);
        $tariff = Tariff::findOrFail($identifier);
        $version = $tariff->versions()->orderByDesc('number')->firstOrFail();
        $this->selectedTariffId = $tariff->id;
        $this->expectedVersionId = $tariff->published_version_id;
        $this->code = $tariff->code;
        $this->name = $version->name;
        $this->description = $version->description ?? '';
        $this->price = sprintf('%d,%02d', intdiv($version->price_cents, 100), $version->price_cents % 100);
        $this->billingInterval = $version->billing_interval;
        $this->billingBasis = $version->billing_basis;
        $this->stationLimit = $version->station_limit ? (string) $version->station_limit : '';
    }

    public function saveDraft(): void
    {
        abort_unless(static::canAccess(), 403);
        $version = app(BillingCatalog::class)->draft(auth()->user(), $this->selectedTariffId, [
            'code' => $this->code, 'name' => $this->name, 'description' => $this->description,
            'price' => $this->price, 'billing_interval' => $this->billingInterval,
            'billing_basis' => $this->billingBasis, 'station_limit' => $this->stationLimit === '' ? null : $this->stationLimit,
        ]);
        $this->chooseTariff((string) $version->tariff_id);
        $this->notice = 'Neue Entwurfsfassung gespeichert. Noch nicht veröffentlicht.';
    }

    public function publishVersion(int $versionId): void
    {
        abort_unless(static::canAccess(), 403);
        $this->validate(['confirmPublication' => ['accepted']], ['confirmPublication.accepted' => 'Bitte bestätigen Sie die bewusste Veröffentlichung.']);
        abort_if($this->selectedTariffId === null, 404);
        app(BillingCatalog::class)->publish(auth()->user(), $this->selectedTariffId, $versionId, $this->expectedVersionId);
        $this->expectedVersionId = Tariff::findOrFail($this->selectedTariffId)->published_version_id;
        $this->confirmPublication = false;
        $this->notice = 'Tariffassung veröffentlicht. Keine Verträge oder Zahlungen ausgelöst.';
    }

    public function saveTrialPolicy(): void
    {
        abort_unless(static::canAccess(), 403);
        $policy = app(BillingCatalog::class)->trialPolicy(auth()->user(), [
            'duration_days' => $this->trialDays, 'station_limit' => $this->trialStationLimit,
        ], $this->expectedPolicyId);
        $this->expectedPolicyId = $policy->id;
        $this->notice = 'Neue Testregeln gespeichert. Frühere Fassungen bleiben unverändert.';
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        return [
            'tariffs' => Tariff::orderBy('code')->limit(200)->get(),
            'versions' => $this->selectedTariffId ? TariffVersion::where('tariff_id', $this->selectedTariffId)->with('publication')->orderByDesc('number')->limit(10)->get() : collect(),
            'trialHistory' => TrialPolicy::orderByDesc('id')->limit(5)->get(),
        ];
    }
}
