<?php

namespace App\Stations\Providers;

/**
 * Grenze zwischen Stationsfachlichkeit und der externen Datenquelle.
 *
 * Ergebnisse sind Vorschauen, keine Models oder Schreibbefehle. Ein Treffer weist
 * weder Betreiberrechte nach noch darf er Partnerkontext oder manuelle Daten ersetzen.
 * Technische Aktivierung auf Nutzerauftrag bestätigt keine Anbieter-Nutzungsrechte.
 */
interface StationDataProvider
{
    /** Liefert einen expliziten Status sowie eine Trefferliste, niemals implizit „keine Treffer“ bei Fehlern. */
    public function search(string $postalCode, string $city = ''): array;

    /** Liefert Details zu einer Quellenkennung; Kennungen sind keine frei abrufbaren URLs. */
    public function details(string $sourceId): array;
}
