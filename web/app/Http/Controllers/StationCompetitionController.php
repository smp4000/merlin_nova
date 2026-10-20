<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationProfile;
use App\Stations\Providers\BenzinpreisProvider;
use App\Stations\StationCompetitionChoices;
use App\Stations\StationMap;
use App\Stations\StationPriceSnapshot;
use App\Stations\StationProfiles;
use App\Stations\StationSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Bewusste Wettbewerbsübernahme in die eigene Station, kein ungescopter Proxy.
 * Jeder externe Abruf ist eine einzelne POST-Aktion. Treffer und Vorschau gelten
 * nur für diese Station/Sitzung und fünfzehn Minuten. Speichern prüft unter
 * Partnersperre Rechte, Stationsfassung, Dubletten und gemeinsames Achterlimit.
 */
class StationCompetitionController extends Controller
{
    public function handle(Request $request, string $partner, Station $station, BenzinpreisProvider $provider)
    {
        Gate::authorize($request->isMethod('get') ? 'viewDetails' : 'update', $station);
        $key = 'competition_drafts.'.$station->partner_id.'_'.$station->id;
        $draft = $request->session()->get($key, []);
        if (($draft['expires_at'] ?? 0) <= now()->timestamp) {
            $draft = [];
        }
        // Gültige Suchentwürfe aus der vorherigen Oberfläche erhalten ebenfalls
        // eine gebundene Prüffolge. Hier wird nur Sitzungskontext ergänzt; externe
        // Abrufe erfolgen weiterhin ausschließlich über autorisierte POSTs.
        if (($draft['result']['status'] ?? null) === 'ok' && ! isset($draft['distance_scan'])) {
            $draft['distance_scan'] = ['token' => (string) Str::uuid(), 'checked' => []];
            $request->session()->put($key, $draft);
        }
        if ($request->isMethod('get')) {
            $hitChecks = $this->hitChecks($station, $draft, $provider);
            $scanPending = $this->scanPending($station, $draft, $hitChecks);

            return view('stations.competition-search', ['partner' => Partner::findOrFail($station->partner_id), 'station' => $station,
                'sortedHits' => StationCompetitionChoices::sort($draft['result']['data'] ?? []),
                'hitChecks' => $hitChecks, 'scanPending' => $scanPending,
                'stationCoordinates' => StationProfile::where('station_id', $station->id)->first()?->data ?? [],
                'draft' => $draft, 'competitors' => StationCompetitor::where('station_id', $station->id)->get(), 'canWrite' => Gate::allows('update', $station)]);
        }
        $action = $request->validate(['action' => ['required', 'in:search,preview,add,refresh,remove,select_batch,check_batch,add_batch,check_hit,check_distances,pause_distances']])['action'];
        if (in_array($action, ['refresh', 'remove'], true)) {
            $id = $request->validate(['competitor_id' => ['required', 'integer'], 'confirmed' => ['accepted']])['competitor_id'];
            $record = StationCompetitor::where('station_id', $station->id)->findOrFail($id);
            $result = $action === 'refresh' ? $provider->details($record->source_id) : null;
            if ($result && $result['status'] !== 'ok') {
                return back()->with('source_message', StationSource::message($result['status']));
            }
            DB::transaction(function () use ($station, $record, $result, $action): void {
                Partner::lockForUpdate()->findOrFail($station->partner_id);
                $current = Station::findOrFail($station->id);
                Gate::forUser(auth()->user()->fresh())->authorize('update', $current);
                $record = StationCompetitor::where('station_id', $station->id)->lockForUpdate()->findOrFail($record->id);
                if ($action === 'remove') {
                    $record->delete();
                } else {
                    abort_unless($record->source_id === ($result['data']['source_id'] ?? null), 409);
                    $record->forceFill(['snapshot' => StationPriceSnapshot::merge($result, $record->snapshot), 'updated_by' => auth()->id()])->save();
                }
            });

            return back()->with('status', $action === 'remove' ? 'Wettbewerbsverknüpfung entfernt. Manuelle Angaben bleiben erhalten.' : 'Wettbewerbspreise aktualisiert.');
        }
        if ($action === 'search') {
            $radius = (int) $request->validate(['radius' => ['required', 'integer', 'between:2,25']])['radius'];
            $result = $provider->searchNearby($station->postal_code ?? '', $station->city ?? '', $radius);
            $draft = ['radius' => $radius, 'result' => $result, 'expires_at' => now()->addMinutes(15)->timestamp,
                'distance_scan' => ['token' => (string) Str::uuid(), 'checked' => []],
                'station_snapshot' => StationProfiles::snapshot($station), 'profile_version' => StationProfile::where('station_id', $station->id)->value('version') ?? 0];
            if ($result['status'] !== 'ok') {
                $request->session()->flash('source_message', StationSource::message($result['status']));
            }
        } elseif (in_array($action, ['check_distances', 'pause_distances'], true)) {
            $token = $request->validate(['scan_token' => ['required', 'string', 'max:64']])['scan_token'];
            abort_unless(isset($draft['distance_scan']) && hash_equals($draft['distance_scan']['token'], $token), 422, 'Die Suche ist abgelaufen. Bitte neu suchen.');
            $pending = $this->scanPending($station, $draft, $this->hitChecks($station, $draft, $provider));
            unset($draft['distance_scan']['error']);
            $draft['distance_scan']['paused'] = $action === 'pause_distances';
            if ($pending && $action === 'check_distances') {
                // Ein Abruf pro POST, keine parallele Schleife oder Umgehung der
                // Anbieterquote. Fehler stoppen die browserseitige Fortsetzung.
                $id = $pending[0];
                $result = $provider->details($id);
                if ($result['status'] === 'ok' && ($result['data']['source_id'] ?? null) === $id) {
                    $draft['hit_previews'][$id] = $result;
                    $draft['distance_scan']['checked'][] = $id;
                } else {
                    $draft['distance_scan']['error'] = StationSource::message($result['status']);
                }
            }
        } elseif ($action === 'check_hit') {
            $id = $request->validate(['source_id' => ['required', 'string', 'max:160']])['source_id'];
            abort_unless(collect($draft['result']['data'] ?? [])->contains('source_id', $id), 422, 'Bitte einen Treffer aus der aktuellen Suche wählen.');
            abort_if($id === $station->external_source_id || StationCompetitor::where('station_id', $station->id)->where('source_id', $id)->exists(), 422);
            // Ein ausdrücklicher Klick prüft genau einen Treffer, ohne ihn bereits
            // auszuwählen oder zu speichern. GET bleibt vollständig ohne Quellabruf.
            $result = $provider->details($id);
            if ($result['status'] === 'ok' && ($result['data']['source_id'] ?? null) === $id) {
                $draft['hit_previews'][$id] = $result;
            } else {
                $request->session()->flash('source_message', StationSource::message($result['status']));
            }
        } elseif ($action === 'select_batch') {
            $ids = $request->validate([
                'source_ids' => ['required', 'array', 'min:1', 'max:8'],
                'source_ids.*' => ['required', 'string', 'max:160', 'distinct'],
            ], ['source_ids.required' => 'Bitte mindestens einen Wettbewerber auswählen.', 'source_ids.max' => 'Bitte höchstens acht Wettbewerber auswählen.'])['source_ids'];
            // Nur ausdrücklich markierte Treffer dieser Sitzung dürfen die begrenzte
            // Prüffolge bilden. Fremde Kennungen und belegte Plätze stoppen vor HTTP.
            $hits = collect($draft['result']['data'] ?? [])->pluck('source_id')->all();
            abort_unless(count(array_diff($ids, $hits)) === 0, 422, 'Bitte Treffer aus der aktuellen Suche wählen.');
            $checks = $this->hitChecks($station, $draft, $provider);
            foreach ($ids as $id) {
                if (($checks[$id]['distance'] ?? null) === null || ($checks[$id]['outside'] ?? false)) {
                    throw ValidationException::withMessages(['source_ids' => 'Ein ausgewählter Wettbewerber ist ungeprüft oder liegt außerhalb des gewählten Stationsradius. Bitte erst die Entfernungen prüfen und die Auswahl korrigieren.']);
                }
            }
            $existing = StationCompetitor::where('station_id', $station->id)->pluck('source_id')->all();
            abort_if(in_array($station->external_source_id, $ids, true) || array_intersect($ids, $existing), 422, 'Eigene oder bereits übernommene Stationen sind nicht auswählbar.');
            $manual = StationProfile::where('station_id', $station->id)->first()?->data['competitors'] ?? [];
            abort_if(count($ids) + count($existing) + count($manual) > 8, 422, 'Zusammen sind höchstens acht Wettbewerber möglich.');
            unset($draft['preview']);
            $draft['batch'] = ['token' => (string) Str::uuid(), 'ids' => array_values($ids), 'previews' => []];
        } elseif ($action === 'check_batch') {
            $token = $request->validate(['batch_token' => ['required', 'string']])['batch_token'];
            abort_unless(isset($draft['batch']) && hash_equals($draft['batch']['token'], $token), 422, 'Die Auswahl ist abgelaufen oder wurde ersetzt.');
            // Genau ein Abruf je POST, keine parallelen Quellabrufe. Die Oberfläche
            // setzt die ausdrücklich angeforderte Folge mit Abstand fort. Bei Fehler
            // bleibt sie stehen; bereits geprüfte Ergebnisse gehen nicht verloren.
            $pending = array_values(array_diff($draft['batch']['ids'], array_keys($draft['batch']['previews'])));
            abort_unless(count($pending), 422, 'Alle ausgewählten Standorte sind bereits geprüft.');
            $result = $provider->details($pending[0]);
            unset($draft['batch']['error']);
            if ($result['status'] === 'ok' && ($result['data']['source_id'] ?? null) === $pending[0]) {
                $draft['batch']['previews'][$pending[0]] = $result;
            } else {
                $draft['batch']['error'] = StationSource::message($result['status']);
            }
        } elseif ($action === 'preview') {
            $id = $request->validate(['source_id' => ['required', 'string', 'max:160']])['source_id'];
            abort_unless(collect($draft['result']['data'] ?? [])->contains('source_id', $id), 422, 'Bitte einen Treffer aus der aktuellen Suche wählen.');
            $result = $provider->details($id);
            unset($draft['preview']);
            if ($result['status'] === 'ok') {
                $draft['preview'] = $result;
            } else {
                $request->session()->flash('source_message', StationSource::message($result['status']));
            }
        } else {
            $request->validate(['confirmed' => ['accepted']]);
            if ($action === 'add_batch') {
                $token = $request->validate(['batch_token' => ['required', 'string']])['batch_token'];
                abort_unless(isset($draft['batch']) && hash_equals($draft['batch']['token'], $token), 422, 'Bitte die aktuelle Auswahl prüfen.');
                abort_unless(count($draft['batch']['ids']) === count($draft['batch']['previews']), 422, 'Bitte zuerst alle ausgewählten Standorte prüfen.');
                $sources = array_values($draft['batch']['previews']);
            } else {
                abort_unless(isset($draft['preview']), 422, 'Bitte zuerst einen Suchtreffer prüfen.');
                $sources = [$draft['preview']];
            }
            DB::transaction(function () use ($station, $draft, $sources): void {
                Partner::lockForUpdate()->findOrFail($station->partner_id);
                $current = Station::findOrFail($station->id);
                Gate::forUser(auth()->user()->fresh())->authorize('update', $current);
                $profile = StationProfile::where('station_id', $station->id)->first();
                abort_unless($draft['station_snapshot'] === StationProfiles::snapshot($current) && (int) $draft['profile_version'] === (int) ($profile?->version ?? 0), 409, 'Der Standort wurde geändert. Bitte neu suchen.');
                // Gemeinsame Transaktion: Scheitert auch nur der letzte Eintrag an
                // Radius, Dublette oder Quote, wird kein Teil der Auswahl gespeichert.
                foreach ($sources as $source) {
                    $distance = StationMap::distance($profile?->data ?? [], $source['data']['coordinates'] ?? []);
                    if ($distance === null) {
                        throw ValidationException::withMessages(['source_ids' => 'Ohne geprüfte Stationskoordinaten ist keine Wettbewerbsübernahme möglich. Bitte die Standortdaten prüfen.']);
                    }
                    // Eine erwartbare fachliche Ablehnung gehört zurück ins Formular,
                    // nicht auf eine technische Exceptionseite. Der Entwurf bleibt
                    // erhalten; die Exception rollt den gesamten Sammelimport zurück.
                    if ($distance !== null && $distance > $draft['radius']) {
                        throw ValidationException::withMessages(['source_ids' => $source['data']['name'].' · '.($source['data']['street'] ?? '').': Entfernung '.number_format($distance, 2, ',', '.').' km, gewählter Stationsradius '.$draft['radius'].' km. Bitte diesen Standort abwählen und „Auswahl prüfen“ wählen oder mit größerem Radius neu suchen. Es wurde nichts aus dieser Auswahl übernommen.']);
                    }
                    $id = $source['data']['source_id'];
                    abort_if($id === $current->external_source_id, 422, 'Die eigene Station ist kein Wettbewerber.');
                    if (StationCompetitor::where('station_id', $station->id)->where('source_id', $id)->exists()) {
                        throw ValidationException::withMessages(['source_id' => 'Dieser Wettbewerber ist bereits vorhanden.']);
                    }
                    $manual = $profile?->data['competitors'] ?? [];
                    foreach ($manual as $row) {
                        if (mb_strtolower(trim($row['name'] ?? '')) === mb_strtolower(trim($source['data']['name'])) && mb_strtolower(trim($row['street'] ?? '')) === mb_strtolower(trim($source['data']['street']))) {
                            throw ValidationException::withMessages(['source_id' => 'Dieser Wettbewerber ist bereits manuell erfasst.']);
                        }
                    }
                    abort_if(count($manual) + StationCompetitor::where('station_id', $station->id)->count() >= 8, 422, 'Höchstens acht Wettbewerber je Station sind möglich.');
                    StationCompetitor::forceCreate(['station_id' => $station->id, 'source_id' => $id, 'snapshot' => $source, 'updated_by' => auth()->id()]);
                }
            });
            unset($draft['preview'], $draft['batch']);
            $request->session()->flash('status', 'Wettbewerber angelegt. Manuelle Angaben bleiben erhalten.');
        }
        $request->session()->put($key, $draft);

        return redirect()->to(route('stations.competition', [$partner, $station->id]).(in_array($action, ['check_hit', 'check_distances', 'pause_distances'], true) ? '#competition-results' : (in_array($action, ['preview', 'select_batch', 'check_batch'], true) ? '#competition-preview' : '')));
    }

    /**
     * Nur ungeprüfte, nicht eigene und noch nicht übernommene Treffer fortsetzen.
     * Erfolgreich abgerufene Treffer ohne Koordinaten werden nicht endlos wiederholt;
     * sie bleiben gesperrt. Die Suchliste ist bereits auf fünfzig Treffer begrenzt.
     */
    private function scanPending(Station $station, array $draft, array $checks): array
    {
        if (! isset($draft['distance_scan'])) {
            return [];
        }
        $origin = StationProfile::where('station_id', $station->id)->first()?->data ?? [];
        if (StationMap::distance($origin, $origin) === null) {
            return [];
        }
        $existing = StationCompetitor::where('station_id', $station->id)->pluck('source_id')->all();
        $pending = [];
        foreach ($draft['result']['data'] ?? [] as $hit) {
            $id = $hit['source_id'];
            if ($id !== $station->external_source_id && ! in_array($id, $existing, true) && ! in_array($id, $draft['distance_scan']['checked'], true) && ($checks[$id]['distance'] ?? null) === null) {
                $pending[] = $id;
            }
        }

        return $pending;
    }

    /**
     * Entfernungen immer vom aktuellen Stationspunkt berechnen, nicht vom
     * PLZ-Mittelpunkt oder einem vom Browser übermittelten Kilometerwert.
     * Sitzungsgebundene Vorschauen und geprüfter öffentlicher Cache vermeiden
     * zusätzliche Abrufe. Fehlende Koordinaten ergeben ausdrücklich unbekannt.
     */
    private function hitChecks(Station $station, array $draft, BenzinpreisProvider $provider): array
    {
        $origin = StationProfile::where('station_id', $station->id)->first()?->data ?? [];
        $checks = [];
        foreach ($draft['result']['data'] ?? [] as $hit) {
            $id = $hit['source_id'];
            $preview = $draft['hit_previews'][$id] ?? $draft['batch']['previews'][$id] ?? null;
            if (($draft['preview']['data']['source_id'] ?? null) === $id) {
                $preview = $draft['preview'];
            }
            $coordinates = $id === $station->external_source_id ? $origin : ($preview['data']['coordinates'] ?? $provider->cachedCoordinates($id));
            $distance = StationMap::distance($origin, $coordinates);
            $checks[$id] = ['distance' => $distance, 'outside' => $distance !== null && $distance > ($draft['radius'] ?? 0)];
        }

        return $checks;
    }
}
