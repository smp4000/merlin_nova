<?php

namespace App\Stations;

/**
 * Fehlende Preise (etwa außerhalb der Öffnungszeit) löschen keine bekannten
 * Beobachtungen. Je Sorte bleibt der ursprüngliche Abrufzeitpunkt erhalten;
 * ein neuer leerer Abruf darf einen historischen Wert niemals verjüngen.
 * Nur dieselbe Quellenkennung darf fortgeschrieben werden. Keine Schätzung
 * und keine Aussage, dass die Station offen ist oder der Preis noch gilt.
 */
final class StationPriceSnapshot
{
    public static function merge(array $fresh, ?array $previous): array
    {
        if (($fresh['status'] ?? null) !== 'ok') {
            return $fresh;
        }
        $same = ($fresh['data']['source_id'] ?? null) !== null
            && ($fresh['data']['source_id'] ?? null) === ($previous['data']['source_id'] ?? null);
        foreach (['e5', 'e10', 'diesel'] as $fuel) {
            $value = $fresh['data']['prices'][$fuel] ?? null;
            $old = $same ? ($previous['data']['prices'][$fuel] ?? null) : null;
            $historical = $fresh['data']['prices_last_known'][$fuel] ?? false;
            $fresh['data']['prices_last_known'][$fuel] = $historical;
            $fresh['data']['prices_observed_at'][$fuel] = $value !== null && ! $historical ? $fresh['fetched_at'] : null;
            if ($value === null && $old !== null) {
                $fresh['data']['prices'][$fuel] = $old;
                $fresh['data']['prices_last_known'][$fuel] = true;
                $fresh['data']['prices_observed_at'][$fuel] = array_key_exists($fuel, $previous['data']['prices_observed_at'] ?? []) ? $previous['data']['prices_observed_at'][$fuel] : $previous['fetched_at'];
            }
        }

        return $fresh;
    }
}
