<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Vollständige Preisfassung: EUR-Nettobetrag in ganzen Cent, Abrechnungsperiode,
 * Bezugsgröße Firma/Station und optionales Stationslimit. Keine Float-Beträge.
 * Auch Entwürfe sind Snapshots: Überarbeitung erzeugt eine neue Versionsnummer.
 * Eine Veröffentlichung erhält einen separaten unveränderlichen Nachweis.
 * Dies aktiviert noch keinen Vertrag und erzeugt keine Rechnung/Lastschrift.
 */
class TariffVersion extends ImmutableBillingRecord
{
    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'number' => 'integer', 'station_limit' => 'integer'];
    }

    public function publication(): HasOne
    {
        return $this->hasOne(TariffPublication::class);
    }
}
