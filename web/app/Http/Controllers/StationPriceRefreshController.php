<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationProfile;
use App\Stations\Providers\BenzinpreisProvider;
use App\Stations\StationMap;
use App\Stations\StationPriceSnapshot;
use App\Stations\StationSource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Bewusst gestartete Preisaktualisierung für eine Station und ihre Quellkontakte.
 * Die serverseitige Sitzung bindet eine begrenzte Folge an Partner, Station und
 * Quellenkennung. Der Browser liefert keine Ziel-IDs oder URLs. Ein POST ruft
 * höchstens einen Standort ab; die vorhandenen Anbieterlimits gelten unverändert.
 * Jeder erfolgreiche Schritt wird einzeln gespeichert. Fehler pausieren den Lauf,
 * ohne vorherige Erfolge oder zuletzt bekannte Preise zu löschen. Die Route sperrt
 * konkurrierende Aufrufe derselben Sitzung; eine neue Folge ersetzt ihren Token.
 */
class StationPriceRefreshController extends Controller
{
    /** POST-Aktionen prüfen Schreibrecht vor HTTP und erneut unter Partnersperre. */
    public function handle(Request $request, string $partner, Station $station, BenzinpreisProvider $provider)
    {
        Gate::authorize('update', $station);
        $action = $request->validate(['action' => ['required', 'in:start,next,pause']])['action'];
        $key = 'price_refresh_runs.'.$station->partner_id.'_'.$station->id;
        $run = $request->session()->get($key, []);
        if ($action === 'start') {
            $jobs = [];
            if ($station->external_source_id) {
                $jobs[] = ['competitor_id' => null, 'source_id' => $station->external_source_id, 'name' => $station->name];
            }
            foreach (StationCompetitor::where('station_id', $station->id)->orderBy('id')->limit(8)->get() as $record) {
                $jobs[] = ['competitor_id' => $record->id, 'source_id' => $record->source_id, 'name' => $record->snapshot['data']['name'] ?? 'Wettbewerber'];
            }
            if (! $jobs) {
                throw ValidationException::withMessages(['prices' => 'Es sind noch keine Benzinpreisquellen verknüpft.']);
            }
            $run = ['token' => (string) Str::uuid(), 'jobs' => $jobs, 'done' => 0, 'expires_at' => now()->addMinutes(15)->timestamp, 'paused' => false];
        } else {
            $token = $request->validate(['run_token' => ['required', 'string', 'max:64']])['run_token'];
            if (($run['expires_at'] ?? 0) <= now()->timestamp || ! hash_equals($run['token'] ?? '', $token)) {
                throw ValidationException::withMessages(['prices' => 'Der Aktualisierungslauf ist abgelaufen oder wurde ersetzt. Bitte neu starten.']);
            }
            $run['paused'] = $action === 'pause';
            unset($run['error']);
            if ($action === 'next' && $run['done'] < count($run['jobs'])) {
                $job = $run['jobs'][$run['done']];
                // Vor externem Abruf ausschließen, dass eine entfernte oder neu
                // verknüpfte Station durch die alte Folge weiter abgefragt wird.
                if (! $this->matches($station, $job)) {
                    $run['error'] = $job['name'].': Die Quellenverknüpfung wurde geändert. Bitte neu starten.';
                } else {
                    $result = $provider->details($job['source_id']);
                    if ($result['status'] !== 'ok') {
                        $run['error'] = $job['name'].': '.StationSource::message($result['status']);
                    } else {
                        $saved = DB::transaction(function () use ($station, $job, $result): bool {
                            Partner::lockForUpdate()->findOrFail($station->partner_id);
                            $current = Station::findOrFail($station->id);
                            Gate::forUser(auth()->user()->fresh())->authorize('update', $current);
                            if (! $this->matches($current, $job) || ($result['data']['source_id'] ?? null) !== $job['source_id']) {
                                return false;
                            }
                            if ($job['competitor_id'] === null) {
                                $current->forceFill(['external_source_data' => StationPriceSnapshot::merge($result, $current->external_source_data)])->save();
                            } else {
                                $record = StationCompetitor::where('station_id', $current->id)->lockForUpdate()->findOrFail($job['competitor_id']);
                                $record->forceFill(['snapshot' => StationPriceSnapshot::merge($result, $record->snapshot), 'updated_by' => auth()->id()])->save();
                            }

                            return true;
                        });
                        if ($saved) {
                            $run['done']++;
                        } else {
                            $run['error'] = $job['name'].': Die Quellenverknüpfung wurde geändert. Bitte neu starten.';
                        }
                    }
                }
                if (isset($run['error'])) {
                    $run['paused'] = true;
                }
            }
        }
        $request->session()->put($key, $run);

        if ($request->expectsJson()) {
            // Nur die freigegebenen Preis-/Kartenfelder übertragen. Profil- und
            // Finanzdaten bleiben serverseitig. Kein erneuter Seitenaufruf: Karte,
            // Zoom, Scrollposition und noch ungespeicherte Eingaben bleiben bestehen.
            $current = Station::findOrFail($station->id);
            $competitors = StationCompetitor::where('station_id', $station->id)->get();
            $source = $current->external_source_data;

            return response()->json([
                'widget' => view('stations.price-refresh', ['station' => $current, 'partner' => Partner::findOrFail($current->partner_id), 'sourceCompetitors' => $competitors, 'canWrite' => true])->render(),
                'points' => StationMap::points($current, StationProfile::where('station_id', $station->id)->first()?->data ?? [], $competitors),
                'sourcePreview' => $source ? view('stations.source-preview', ['source' => $source])->render() : null,
                'sourcePrices' => $source['data']['prices'] ?? [],
                'lastKnown' => $source['data']['prices_last_known'] ?? [],
                'sourceStamp' => $source ? 'Benzinpreis-Aktuell.de · '.Carbon::parse($source['fetched_at'])->timezone('Europe/Berlin')->format('d.m. H:i').(Carbon::parse($source['fetched_at'])->lt(now()->subMinutes(5)) ? ' · veraltet' : '') : null,
            ]);
        }

        return redirect()->to(route('stations.details', [$partner, $station->id]).'#price-refresh');
    }

    /** Nur der gespeicherte Datensatz derselben Station darf Ziel des Schritts sein. */
    private function matches(Station $station, array $job): bool
    {
        return $job['competitor_id'] === null
            ? $station->external_source_id === $job['source_id']
            : StationCompetitor::where('station_id', $station->id)->whereKey($job['competitor_id'])->where('source_id', $job['source_id'])->exists();
    }
}
