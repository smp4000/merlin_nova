<?php

namespace App\Stations;

/** Gemeinsame deutsche Zustände; keine technischen Rohfehler/Quellantworten anzeigen. */
final class StationSource
{
    public static function message(string $status): string
    {
        return match ($status) {
            'rate_limited' => 'Abruflimit erreicht. Bitte versuchen Sie es später erneut.',
            'not_found' => 'Die Quelle hat keine passende Seite gefunden. Bitte prüfen Sie PLZ und Ort.',
            'parse_failed', 'invalid_identity' => 'Die Quelldaten konnten nicht sicher zugeordnet werden. Bitte manuell erfassen.',
            'invalid_query' => 'Bitte eine deutsche Postleitzahl und den zugehörigen Ort eingeben.',
            'radius_mismatch' => 'Die Quelle liefert einen abweichenden Umkreis. Die Treffer werden deshalb nicht als passende Suche übernommen. Bitte später erneut versuchen.',
            default => 'Die Datenquelle ist gerade nicht erreichbar. Die manuelle Anlage bleibt möglich.',
        };
    }
}
