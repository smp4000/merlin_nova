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
        return ['is_active' => 'boolean'];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PartnerMembership::class);
    }
}
