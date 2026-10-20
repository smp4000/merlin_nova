<?php

namespace App\Stations;

use App\Models\Station;
use App\Models\StationCompetitor;
use Carbon\Carbon;

/**
 * Nur freigegebene Kartenfelder verlassen den Server: keine Profil-/Bankdaten.
 * Fehlende Koordinaten bleiben fehlend. Ranking nur zwischen Wettbewerbern und
 * je Sorte; unbekannte, historische oder über fünf Minuten alte Preise neutral.
 * Die eigene Station bleibt blau, selbst wenn ihr Preis Minimum/Maximum wäre.
 */
final class StationMap
{
    /** Luftlinie nur zwischen vorhandenen geprüften Koordinatenpaaren, keine Geocodierung. */
    public static function distance(array $a, array $b): ?float
    {
        foreach ([$a, $b] as $point) {
            if (! is_numeric($point['latitude'] ?? null) || ! is_numeric($point['longitude'] ?? null)
                || abs((float) $point['latitude']) > 90 || abs((float) $point['longitude']) > 180) {
                return null;
            }
        }
        $lat = deg2rad((float) $b['latitude'] - (float) $a['latitude']);
        $lon = deg2rad((float) $b['longitude'] - (float) $a['longitude']);
        $h = sin($lat / 2) ** 2 + cos(deg2rad((float) $a['latitude'])) * cos(deg2rad((float) $b['latitude'])) * sin($lon / 2) ** 2;

        return 6371 * 2 * asin(sqrt(min(1, max(0, $h))));
    }

    public static function points(Station $station, array $profile, iterable $competitors): array
    {
        $own = $station->external_source_data;
        $points = [self::point($station->name, $profile, $own, true, ['e5' => $profile['price_super'] ?? null, 'e10' => $profile['price_e10'] ?? null, 'diesel' => $profile['price_diesel'] ?? null])];
        $points[0]['address'] = trim(implode(' ', array_filter([$station->street, $station->postal_code, $station->city])));
        foreach ($profile['competitors'] ?? [] as $row) {
            $points[] = self::point($row['name'], $row, null, false, ['e5' => $row['price_super'] ?? null, 'e10' => $row['price_e10'] ?? null, 'diesel' => $row['price_diesel'] ?? null]);
        }
        foreach ($competitors as $competitor) {
            $snapshot = $competitor instanceof StationCompetitor ? $competitor->snapshot : $competitor;
            $points[] = self::point($snapshot['data']['name'], $snapshot['data']['coordinates'] ?? [], $snapshot, false);
        }

        return self::colors($points);
    }

    public static function colors(array $points): array
    {
        foreach (['e5', 'e10', 'diesel'] as $fuel) {
            $values = [];
            foreach ($points as $point) {
                if (! $point['own'] && $point['eligible'][$fuel] && $point['latitude'] !== null && $point['longitude'] !== null) {
                    $values[] = $point['prices'][$fuel];
                }
            }
            foreach ($points as &$point) {
                $point['colors'][$fuel] = $point['own'] ? 'blue' : 'black';
                if (! $point['own'] && $point['eligible'][$fuel] && $point['latitude'] !== null && $point['longitude'] !== null && count($values) > 1 && min($values) < max($values)) {
                    $point['colors'][$fuel] = $point['prices'][$fuel] === min($values) ? 'green' : ($point['prices'][$fuel] === max($values) ? 'red' : 'black');
                }
            }
            unset($point);
        }

        return $points;
    }

    private static function point(string $name, array $coordinates, ?array $snapshot, bool $own, array $manual = []): array
    {
        $lat = $coordinates['latitude'] ?? null;
        $lon = $coordinates['longitude'] ?? null;
        $valid = is_numeric($lat) && is_numeric($lon) && abs((float) $lat) <= 85 && abs((float) $lon) <= 180;
        $point = ['name' => $name, 'own' => $own, 'latitude' => $valid ? (float) $lat : null, 'longitude' => $valid ? (float) $lon : null,
            'address' => trim(implode(' ', array_filter([$snapshot['data']['street'] ?? $coordinates['street'] ?? null, $snapshot['data']['postal_code'] ?? $coordinates['postal_code'] ?? null, $snapshot['data']['city'] ?? $coordinates['city'] ?? null]))),
            'prices' => [], 'eligible' => [], 'stamps' => [], 'last_known' => []];
        foreach (['e5', 'e10', 'diesel'] as $fuel) {
            $raw = $snapshot['data']['prices'][$fuel] ?? $manual[$fuel] ?? null;
            $price = is_numeric($raw) && (float) $raw > 0 && (float) $raw < 100 ? (float) $raw : null;
            $fromSource = isset($snapshot['data']['prices'][$fuel]);
            $stamp = $fromSource ? (array_key_exists($fuel, $snapshot['data']['prices_observed_at'] ?? []) ? $snapshot['data']['prices_observed_at'][$fuel] : ($snapshot['data']['prices_last_known'][$fuel] ?? false ? null : ($snapshot['fetched_at'] ?? null))) : null;
            $last = $fromSource && ($snapshot['data']['prices_last_known'][$fuel] ?? false);
            $point['prices'][$fuel] = $price;
            $point['last_known'][$fuel] = $last;
            $point['stamps'][$fuel] = $stamp;
            $point['eligible'][$fuel] = $price !== null && ! $last && $stamp && Carbon::parse($stamp)->betweenIncluded(now()->subMinutes(5), now());
        }

        return $point;
    }
}
