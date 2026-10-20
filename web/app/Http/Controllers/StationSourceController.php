<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Models\StationProfile;
use App\Stations\Providers\StationDataProvider;
use App\Stations\StationPriceSnapshot;
use App\Stations\StationSource;
use App\Stations\StationSourceImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Bewusste Preisaktualisierung nur für eine bereits eigene, verknüpfte Station.
 * Scoped Binding verbirgt fremde Stationen vor dem Abruf. Nach Netzwerkzugriff
 * Rechte unter Partnersperre erneut prüfen; Stammdaten niemals überschreiben.
 */
class StationSourceController extends Controller
{
    /** Vorschau liest nur den gespeicherten Snapshot; Bestätigung ergänzt ausschließlich Leerstellen. */
    public function import(Request $request, string $partner, Station $station, StationSourceImport $imports)
    {
        Gate::authorize('update', $station);
        abort_unless($station->external_source_id && $station->external_source_data, 422);
        if ($request->isMethod('post')) {
            $input = $request->validate(['confirmed' => ['accepted'], 'import_hours' => ['sometimes', 'boolean'], 'import_coordinates' => ['sometimes', 'boolean'], 'version' => ['required', 'integer', 'min:0'], 'source_snapshot' => ['required', 'regex:/^[a-f0-9]{64}$/D']]);
            if (! $request->boolean('import_hours') && ! $request->boolean('import_coordinates')) {
                throw ValidationException::withMessages(['source' => 'Bitte Öffnungszeiten oder Koordinaten ausdrücklich auswählen.']);
            }
            $count = $imports->apply($request->user(), $station, $request->boolean('import_hours'), $request->boolean('import_coordinates'), (int) $input['version'], $input['source_snapshot']);

            return redirect()->route('stations.details', [$partner, $station->id])->with('status_type', 'success')->with('status', $count ? 'Geprüfte Quelldaten ergänzt. Vorhandene manuelle Angaben bleiben erhalten.' : 'Keine freien Felder ergänzt. Vorhandene Angaben bleiben erhalten.');
        }
        $profile = StationProfile::where('station_id', $station->id)->first();
        $data = $profile?->data ?? [];

        return view('stations.source-import', ['partner' => Partner::findOrFail($station->partner_id), 'station' => $station, 'source' => $station->external_source_data, 'version' => $profile?->version ?? 0, 'snapshot' => StationSourceImport::snapshot($station), 'additions' => StationSourceImport::additions($data, $station->external_source_data, true, true)]);
    }

    /**
     * Eigene Verknüpfungsmaske für Bestandsstationen. Daten liegen pro Station,
     * Partner und Sitzung; kein Suchergebnis eines anderen Formulars ist gültig.
     * GET liest ausschließlich. POST prüft Schreiben/Teststatus vor jedem Abruf.
     */
    public function configure(Request $request, string $partner, Station $station, StationDataProvider $provider)
    {
        Gate::authorize('update', $station);
        $key = 'station_source_drafts.'.$station->partner_id.'_'.$station->id;
        $draft = $request->session()->get($key, []);
        if (($draft['expires_at'] ?? 0) <= now()->timestamp) {
            $draft = [];
        }
        if ($request->isMethod('post')) {
            $action = $request->validate(['action' => ['required', 'in:search,preview,link']])['action'];
            if ($action === 'search') {
                $query = $request->validate(['postal_code' => ['required', 'regex:/^\d{5}$/D'], 'city' => ['required', 'string', 'max:100']]);
                $result = $provider->search($query['postal_code'], $query['city']);
                $draft = ['result' => $result, 'query' => $query, 'expires_at' => now()->addMinutes(15)->timestamp];
                if ($result['status'] !== 'ok') {
                    $request->session()->flash('source_message', StationSource::message($result['status']));
                }
            } elseif ($action === 'preview') {
                $id = $request->validate(['source_id' => ['required', 'string', 'max:160']])['source_id'];
                if (! collect($draft['result']['data'] ?? [])->firstWhere('source_id', $id)) {
                    throw ValidationException::withMessages(['source_id' => 'Bitte erneut suchen und einen Treffer auswählen.']);
                }
                $result = $provider->details($id);
                if ($result['status'] === 'ok') {
                    $draft['preview'] = $result;
                } else {
                    unset($draft['preview']);
                    $request->session()->flash('source_message', StationSource::message($result['status']));
                }
            } else {
                $request->validate(['confirmed' => ['accepted']]);
                if (! isset($draft['preview'])) {
                    throw ValidationException::withMessages(['source_id' => 'Die Vorschau ist abgelaufen. Bitte erneut suchen.']);
                }
                if ($request->filled('source_id') && $request->input('source_id') !== $draft['preview']['data']['source_id']) {
                    throw ValidationException::withMessages(['source_id' => 'Bitte die geänderte Auswahl zuerst prüfen.']);
                }
                DB::transaction(function () use ($station, $draft): void {
                    Partner::lockForUpdate()->findOrFail($station->partner_id);
                    $current = Station::findOrFail($station->id);
                    Gate::forUser(auth()->user()->fresh())->authorize('update', $current);
                    if ($current->external_source_id !== null) {
                        throw ValidationException::withMessages(['source_id' => 'Diese Station ist inzwischen bereits verknüpft. Bitte neu laden.']);
                    }
                    $id = $draft['preview']['data']['source_id'];
                    if (Station::where('external_source_id', $id)->whereKeyNot($current->id)->exists()) {
                        throw ValidationException::withMessages(['source_id' => 'Diese Quelle ist bei Ihrem Partner bereits einer Station zugeordnet.']);
                    }
                    $current->forceFill(['external_source_id' => $id, 'external_source_data' => $draft['preview']])->save();
                });
                $request->session()->forget($key);

                return redirect()->route('stations.details', [$partner, $station->id])->with('status_type', 'success')->with('status', 'Benzinpreisanbindung gespeichert. Ihre Stammdaten bleiben unverändert.');
            }
            $request->session()->put($key, $draft);

            return redirect()->route('stations.source.configure', [$partner, $station->id]);
        }

        return view('stations.source-configure', ['partner' => Partner::findOrFail($station->partner_id), 'station' => $station, 'draft' => $draft]);
    }

    /** Ersetzt ausschließlich die getrennte Quellmomentaufnahme, niemals den Stationskopf. */
    public function refresh(string $partner, Station $station, StationDataProvider $provider)
    {
        Gate::authorize('update', $station);
        abort_unless($station->external_source_id, 422);
        $result = $provider->details($station->external_source_id);
        if ($result['status'] !== 'ok') {
            return back()->with('source_message', StationSource::message($result['status']));
        }
        DB::transaction(function () use ($station, $result): void {
            Partner::lockForUpdate()->findOrFail($station->partner_id);
            $current = Station::findOrFail($station->id);
            Gate::forUser(auth()->user()->fresh())->authorize('update', $current);
            abort_unless($current->external_source_id === $result['data']['source_id'], 409);
            $current->forceFill(['external_source_data' => StationPriceSnapshot::merge($result, $current->external_source_data)])->save();
        });

        return back()->with('status_type', 'success')->with('status', 'Quelldaten aktualisiert. Manuelle Stammdaten bleiben erhalten.');
    }
}
