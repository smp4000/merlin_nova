<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Versionierter Stammdatensatz einer Station, getrennt von öffentlichen Listenfeldern.
 *
 * Die Daten enthalten freiwillige Kontakt-/Organisationsinformationen und werden
 * daher verschlüsselt gespeichert sowie aus jeder automatischen Serialisierung
 * entfernt. Keine beliebigen Settings: StationFields validiert sämtliche Schlüssel.
 * Der Service setzt station_id, Akteur und Version unter einer Partnersperre;
 * Requestdaten dürfen diese Sicherheitsbezüge nicht massenzuweisen.
 * Verschlüsselung ersetzt keine Policy und kein Aufbewahrungs-/Schlüsselkonzept.
 */
class StationProfile extends PartnerOwnedModel
{
    protected $fillable = ['data'];

    protected $hidden = ['data'];

    protected function casts(): array
    {
        return ['data' => 'encrypted:array', 'version' => 'integer'];
    }

    /** Beziehung nur innerhalb des fail-closed Partnerkontexts verwenden. */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
