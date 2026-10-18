<?php

namespace App\Models;

/**
 * Versionierte Standardbedingungen für künftig neu beginnende Tests.
 * Dauer und Stationslimit werden nicht an bestehenden Fassungen verändert.
 * Die spätere Registrierung muss die konkrete Policy-ID und den zugesagten
 * Beginn/das Ende beim Partner festhalten. Ein neuer Standard verlängert
 * deshalb keinen bestehenden Test. Noch keine Implementierung des Teststarts.
 * E-Mail-Nachweis, ausdrücklicher Aboabschluss und Ablauf-Nur-Lesen sind feste
 * Produktregeln; es existiert bewusst kein Schalter für automatische Zahlung.
 */
class TrialPolicy extends ImmutableBillingRecord
{
    protected function casts(): array
    {
        return ['duration_days' => 'integer', 'station_limit' => 'integer'];
    }
}
