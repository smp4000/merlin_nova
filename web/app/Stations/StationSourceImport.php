<?php

namespace App\Stations;

use App\Models\Partner;
use App\Models\Station;
use App\Models\StationProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Bestätigte Ergänzung leerer Stammdaten aus einer geprüften Quellenmomentaufnahme.
 *
 * Niemals ein allgemeines Überschreiben: pro Wochentag bleiben belegter Status und
 * selbst teilweise Uhrzeiten erhalten. Koordinaten werden nur als ganzes Paar und
 * nur bei zwei leeren Zielfeldern ergänzt. Profil-/Quellenfassung unter Partnersperre
 * vergleichen, damit eine manuelle Paralleländerung auch zwischen Vorschau und
 * Bestätigung geschützt bleibt. Finanzdaten werden weder ausgegeben noch verändert.
 */
final class StationSourceImport
{
    /** Nicht geheimes Vergleichstoken der autorisierten gespeicherten Quelle. */
    public static function snapshot(Station $station): string
    {
        return hash('sha256', json_encode(self::canonical($station->external_source_data), JSON_THROW_ON_ERROR));
    }

    /** MySQL ordnet JSON-Objektschlüssel um; die Reihenfolge ist keine fachliche Änderung. */
    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }

    /** Liefert ausschließlich ergänzbare, erneut nach dem Stationsschema geprüfte Felder. */
    public static function additions(array $previous, array $source, bool $hours, bool $coordinates): array
    {
        $changes = [];
        if ($hours) {
            foreach ($source['data']['opening_hours'] ?? [] as $day => $value) {
                $old = $previous['opening_hours'][$day] ?? [];
                if (isset(StationFields::DAYS[$day]) && in_array($old['status'] ?? 'unknown', ['unknown', null, ''], true) && blank($old['open'] ?? null) && blank($old['close'] ?? null)) {
                    $changes['opening_hours'][$day] = $value;
                }
            }
        }
        $pair = $source['data']['coordinates'] ?? null;
        if ($coordinates && blank($previous['latitude'] ?? null) && blank($previous['longitude'] ?? null) && is_array($pair) && isset($pair['latitude'], $pair['longitude'])) {
            $changes['latitude'] = $pair['latitude'];
            $changes['longitude'] = $pair['longitude'];
        }

        return StationFields::validate($changes);
    }

    /** Gibt die Anzahl ergänzter Wochentage/Feldpaare zurück; keine Änderung bei leerer Auswahl. */
    public function apply(User $actor, Station $station, bool $hours, bool $coordinates, int $version, string $snapshot): int
    {
        return DB::transaction(function () use ($actor, $station, $hours, $coordinates, $version, $snapshot): int {
            Partner::lockForUpdate()->findOrFail($station->partner_id);
            $current = Station::findOrFail($station->id);
            Gate::forUser($actor->fresh())->authorize('update', $current);
            $profile = StationProfile::where('station_id', $current->id)->first();
            if (($profile?->version ?? 0) !== $version || ! hash_equals(self::snapshot($current), $snapshot)) {
                throw ValidationException::withMessages(['source' => 'Station oder Quelle wurde inzwischen geändert. Bitte Vorschau neu laden.']);
            }
            $previous = $profile?->data ?? [];
            $changes = self::additions($previous, $current->external_source_data ?? [], $hours, $coordinates);
            if ($changes === []) {
                return 0;
            }
            $data = array_replace($previous, $changes);
            if (isset($changes['opening_hours'])) {
                $data['opening_hours'] = array_replace($previous['opening_hours'] ?? [], $changes['opening_hours']);
            }
            $data['source_imports'] = ['imported_at' => now()->toIso8601String(), 'source_id' => $current->external_source_id, 'source_fetched_at' => $current->external_source_data['fetched_at'], 'fields' => array_keys($changes)];
            $profile ??= new StationProfile;
            $profile->forceFill(['station_id' => $current->id, 'data' => $data, 'version' => $version + 1, 'updated_by' => $actor->id])->save();

            return count($changes['opening_hours'] ?? []) + (isset($changes['latitude']) ? 1 : 0);
        });
    }
}
