<?php

namespace App\Models;

/**
 * Ausdrücklich ausgewählter Wettbewerber einer eigenen Station. Gehört immer
 * demselben Partner wie die Stammstation (auch zusammengesetzter Fremdschlüssel).
 * Kein eigener Nova-Standort, daher kein Zugriff und kein Stationskontingent.
 * Verschlüsselter Quellstand bleibt von manuell gepflegten Wettbewerbern getrennt.
 * Kennung allein ist kein Importauftrag; Sitzungsprüfung übernimmt der Controller.
 */
class StationCompetitor extends PartnerOwnedModel
{
    protected $guarded = ['*'];

    protected $hidden = ['snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array'];
    }
}
