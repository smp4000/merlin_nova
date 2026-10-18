<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Die Firma des Tankstellenpartners bildet die oberste Mandantengrenze.
 *
 * Dieser Datensatz enthält derzeit nur Plattform-Metadaten. Deshalb besitzt
 * er keinen operativen Mandantenscope: Der Superadmin darf Firmen auflisten,
 * ohne deren operative Daten freizuschalten. Jeder Partner-Endpunkt muss ihn
 * stattdessen über ResolvePartner und PartnerPolicy ausdrücklich autorisieren.
 * Stations- und spätere Personaldaten verwenden PartnerOwnedModel.
 * is_active ist eine technische Sperre, kein Test-/Abonnementstatus.
 */
class Partner extends Model
{
    protected $attributes = ['is_active' => true];

    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'trial_started_at' => 'immutable_datetime', 'trial_ends_at' => 'immutable_datetime'];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PartnerMembership::class);
    }

    /**
     * Registrierte Firmen dürfen operativ nur während ihres bestätigten Tests
     * schreiben. Am zugesagten Endzeitpunkt greift Nur-Lesen ohne Cronjob.
     * Ältere technische Fixtures ohne Registrierung bleiben unverändert;
     * ein bezahlter Vertragsstatus wird erst im späteren Abo-Modul ergänzt.
     */
    public function allowsOperationalWrites(): bool
    {
        return $this->registration_owner_id === null
            || ($this->trial_started_at !== null && $this->trial_ends_at !== null && now()->lt($this->trial_ends_at));
    }
}
