<?php

namespace App\Stations;

use App\Models\Partner;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Atomare Pflege des Stationskopfs und seines vertraulichen Stammdatenprofils.
 *
 * Beide Schreibwege teilen die Partnersperre mit dem Assistenten. Ein Formular
 * darf weder eine parallel bearbeitete Fassung überschreiben noch über eine
 * Deaktivierung im Profil die Stationsquote umgehen. Änderungsakteur/-zeit und
 * Version werden gespeichert, jedoch keine sensiblen Vollwerte in Logs kopiert.
 * Dies ist ein Änderungsnachweis, noch kein vollständiges revisionsfestes Audit.
 */
final class StationProfiles
{
    /** Unvertraulicher Vergleichswert gegen parallele Änderungen am Stationskopf. */
    public static function snapshot(Station $station): string
    {
        return hash('sha256', json_encode($station->only(['id', 'name', 'brand', 'street', 'house_number', 'postal_code', 'city', 'country_code', 'short_code']), JSON_THROW_ON_ERROR));
    }

    /**
     * Speichert nur bereits validierte Daten, autorisiert unter Sperre erneut.
     * Veraltete Versionen liefern einen fachlichen Konflikt ohne Teiländerung.
     */
    public function save(User $actor, Station $station, array $base, array $data, int $version, string $snapshot, array $media = []): void
    {
        DB::transaction(function () use ($actor, $station, $base, $data, $version, $snapshot, $media): void {
            Partner::lockForUpdate()->findOrFail($station->partner_id);
            $current = Station::findOrFail($station->id);
            Gate::forUser($actor->fresh())->authorize('update', $current);
            $profile = StationProfile::where('station_id', $current->id)->first();
            if (($profile?->version ?? 0) !== $version || ! hash_equals(self::snapshot($current), $snapshot)) {
                throw ValidationException::withMessages(['version' => 'Die Station wurde inzwischen geändert. Bitte laden Sie die aktuelle Fassung neu und prüfen Sie Ihre Eingaben.']);
            }
            $previous = $profile?->data ?? [];
            // Quell-Wettbewerber zählen mit; der manuelle Formularweg darf die
            // gemeinsame Grenze nicht umgehen oder eine importierte Adresse duplizieren.
            $external = StationCompetitor::where('station_id', $current->id)->get();
            if (count($data['competitors'] ?? []) + $external->count() > 8) {
                throw ValidationException::withMessages(['competitors' => 'Mit den Quell-Wettbewerbern sind höchstens acht Einträge erlaubt.']);
            }
            foreach ($data['competitors'] ?? [] as $row) {
                foreach ($external as $entry) {
                    $source = $entry->snapshot['data'];
                    if (mb_strtolower(trim($row['name'])) === mb_strtolower(trim($source['name'])) && mb_strtolower(trim($row['street'] ?? '')) === mb_strtolower(trim($source['street']))) {
                        throw ValidationException::withMessages(['competitors' => 'Dieser Wettbewerber ist bereits aus der Quelle vorhanden.']);
                    }
                }
            }

            if (! Gate::forUser($actor->fresh())->allows('manageFinance', $current)) {
                // Ein Rollenwechsel zwischen Formular und Speicherung darf weder
                // Bankdaten schreiben noch vorhandene unsichtbare Werte löschen.
                abort_if(array_intersect(array_keys($data), StationFields::financeKeys()) !== [], 403);
                $data = [...$data, ...array_intersect_key($previous, array_flip(StationFields::financeKeys()))];
            }
            // Alte Freitexte bleiben erhalten, wenn die neue Maske sie nicht
            // mitsendet. Keine automatische Umwandlung in Steuer-ID/m²/Nummer.
            $data = [...array_intersect_key($previous, array_flip(StationFields::legacyKeys())), ...$data];
            // Letzten bestätigten Quellenimport als Herkunftsnachweis erhalten.
            // Dieser reservierte Schlüssel ist niemals über Formularfelder schreibbar.
            if (isset($previous['source_imports'])) {
                $data['source_imports'] = $previous['source_imports'];
            }
            if (array_key_exists('short_code', $base)) {
                $code = StationCodes::normalize($base['short_code'] ?? '');
                if (! preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)?$/', $code) || strlen($code) > 8) {
                    throw ValidationException::withMessages(['short_code' => 'Bitte einen gültigen internen Kurzcode mit höchstens 8 Zeichen angeben.']);
                }
                if (Station::where('short_code', $code)->whereKeyNot($current->id)->exists()) {
                    throw ValidationException::withMessages(['short_code' => 'Dieser Kurzcode ist bei Ihrem Partner bereits vergeben.']);
                }
                $current->forceFill(['short_code' => $code]);
                unset($base['short_code']);
            }
            $data['media'] = $previous['media'] ?? [];
            foreach ($media as $item) {
                if ($item['kind'] === 'logo') {
                    // Bisherige Dateien werden erhalten; Löschen/Aufbewahrung ist
                    // ein eigener späterer Ablauf, keine Nebenwirkung des Uploads.
                    $data['media'] = array_values(array_filter($data['media'], fn ($old) => $old['kind'] !== 'logo'));
                }
                $data['media'][] = $item;
            }
            if (count(array_filter($data['media'], fn ($item) => $item['kind'] === 'photo')) > 10) {
                throw ValidationException::withMessages(['photos' => 'Pro Station sind höchstens zehn Fotos erlaubt.']);
            }
            $base['house_number'] = $data['house_number'] ?? null;
            unset($data['house_number']);
            $current->fill($base);
            $identity = Station::identityFor($current->getAttributes());
            if (Station::where('identity_hash', $identity)->whereKeyNot($current->id)->exists()) {
                throw ValidationException::withMessages(['name' => 'Diese Station mit gleicher Anschrift ist bereits angelegt.']);
            }
            $current->forceFill(['identity_hash' => $identity])->save();
            $profile ??= new StationProfile;
            $profile->forceFill([
                'station_id' => $current->id, 'data' => $data,
                'version' => $version + 1, 'updated_by' => $actor->id,
            ])->save();
        }, attempts: 3);
    }
}
