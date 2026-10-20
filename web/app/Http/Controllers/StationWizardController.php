<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Stations\Providers\PostalPlaceProvider;
use App\Stations\Providers\StationDataProvider;
use App\Stations\StationCodes;
use App\Stations\StationSource;
use App\Stations\StationSourceImport;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stationsassistent mit optionaler, bewusst ausgelöster externer Suche.
 *
 * Entwürfe liegen pro Partner und Formular in der angemeldeten Sitzung. Jeder
 * Schritt prüft aktuelle Rechte; allein die bestätigte Zusammenfassung schreibt.
 * Der Partnerkontext kommt ausschließlich aus ResolvePartner. Formular-IDs sind
 * keine Berechtigung und enthalten weder Mandantenkennung noch API-Schlüssel.
 */
class StationWizardController extends Controller
{
    /** Öffnet einen neuen Entwurf oder einen vorhandenen Schritt ohne Datenbankänderung. */
    public function show(Request $request, PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('create', [Station::class, $partner]);
        $token = $request->query('draft');
        if ($token === null) {
            $token = (string) Str::uuid();
            // Eine begrenzte Entwurfsliste verhindert unbegrenztes Sitzungswachstum.
            $drafts = $request->session()->get('station_drafts', []);
            $drafts[$token] = ['partner_id' => $partner->id, 'step' => 1, 'data' => []];
            $request->session()->put('station_drafts', array_slice($drafts, -10, null, true));

            return redirect()->route('stations.create', [$partner->id, 'draft' => $token]);
        }
        $draft = $this->draft($request, $partner, $token);

        return view('stations.wizard', compact('partner', 'token', 'draft'));
    }

    /** Validiert schrittweise; Wiederholung des Abschlusses liefert dieselbe Station. */
    public function advance(Request $request, PartnerContext $context, StationDataProvider $provider)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('create', [Station::class, $partner]);
        $request->validate(['draft' => ['required', 'uuid'], 'partner_id' => ['prohibited']]);
        $token = $request->string('draft')->toString();
        $draft = $this->draft($request, $partner, $token);
        if (in_array($request->input('action'), ['source_search', 'source_select', 'source_manual'], true)) {
            abort_unless($draft['step'] === 1, 422);
            if ($request->input('action') === 'source_manual') {
                unset($draft['source'], $draft['search']);
            } elseif ($request->input('action') === 'source_search') {
                $query = $request->validate(['search_postal_code' => ['required', 'regex:/^\d{5}$/D'], 'search_city' => ['nullable', 'string', 'max:100']]);
                // Nur der bewusste Suchauftrag löst die begrenzte Ortsermittlung
                // aus, niemals einzelne Tastendrücke. Vorhandene Orte erhalten;
                // bei Mehrdeutigkeit nicht willkürlich eine Gemeinde auswählen.
                if (trim($query['search_city'] ?? '') === '') {
                    $places = app(PostalPlaceProvider::class)->lookup($query['search_postal_code']);
                    if ($places['status'] !== 'ok' || count($places['cities']) !== 1) {
                        throw ValidationException::withMessages(['search_city' => count($places['cities'] ?? []) > 1
                            ? 'Diese PLZ gehört zu mehreren Orten. Bitte über „Ort ermitteln“ den passenden Ort auswählen oder manuell ergänzen.'
                            : 'Der Ort konnte derzeit nicht ermittelt werden. Bitte Ort manuell ergänzen oder später erneut suchen.']);
                    }
                    $query['search_city'] = $places['cities'][0];
                }
                $result = $provider->search($query['search_postal_code'], $query['search_city']);
                $draft['search'] = ['result' => $result, 'query' => $query, 'expires_at' => now()->addMinutes(15)->timestamp];
                if ($result['status'] !== 'ok') {
                    $request->session()->flash('source_message', StationSource::message($result['status']));
                }
            } else {
                $selection = $request->validate(['source_id' => ['required', 'string', 'max:160']]);
                // Nur Treffer dieses Partner-/Sitzungsentwurfs sind auswählbar.
                // Keine direkte URL/Quellkennung aus einem manipulierten Formular abrufen.
                if (($draft['search']['expires_at'] ?? 0) <= now()->timestamp) {
                    throw ValidationException::withMessages(['source_id' => 'Die Suche ist abgelaufen. Bitte erneut suchen.']);
                }
                $hit = collect($draft['search']['result']['data'] ?? [])->firstWhere('source_id', $selection['source_id']);
                if (! $hit) {
                    throw ValidationException::withMessages(['source_id' => 'Bitte einen Treffer aus dieser Suche auswählen.']);
                }
                $result = $provider->details($selection['source_id']);
                if ($result['status'] === 'ok') {
                    $draft['source'] = $result;
                    $draft['imports'] = [];
                    $draft['data'] = array_merge($draft['data'], array_intersect_key($result['data'], array_flip(['name', 'street', 'postal_code', 'city'])), ['country_code' => 'DE']);
                    $draft['step'] = 2;
                } else {
                    $request->session()->flash('source_message', StationSource::message($result['status']));
                }
            }
        } elseif ($request->input('action') === 'back') {
            // Auch noch unvollständige Adressangaben beim Zurückgehen behalten.
            // Pflicht-/Länderprüfung erfolgt erneut beim Vorwärtsgehen; unbeschränkte
            // Rohdaten dürfen dennoch nicht in die Sitzung gelangen.
            if ($draft['step'] === 2) {
                $draft['data'] = array_merge($draft['data'], $request->validate([
                    'street' => ['nullable', 'string', 'max:255'],
                    'postal_code' => ['nullable', 'string', 'max:20'],
                    'city' => ['nullable', 'string', 'max:100'],
                    'country_code' => ['nullable', 'string', 'max:2'],
                ]));
            }
            $draft['step'] = max(1, $draft['step'] - 1);
        } elseif ($draft['step'] === 1) {
            $draft['data'] = array_merge($draft['data'], $request->validate([
                'name' => ['required', 'string', 'max:255', 'regex:/\S/u'],
                'brand' => ['nullable', 'string', 'max:100'],
            ]));
            $draft['step'] = 2;
        } elseif ($draft['step'] === 2) {
            $draft['data'] = array_merge($draft['data'], $request->validate([
                'street' => ['required', 'string', 'max:255', 'regex:/\S/u'],
                'postal_code' => ['required', 'string', 'max:20', 'regex:/\S/u'],
                'city' => ['required', 'string', 'max:100', 'regex:/\S/u'],
                'country_code' => ['required', 'in:DE,AT,CH'],
            ]));
            $draft['step'] = 3;
        } elseif ($draft['step'] === 3) {
            $request->validate(['import_hours' => ['sometimes', 'boolean'], 'import_coordinates' => ['sometimes', 'boolean']]);
            $draft['imports'] = ['hours' => $request->boolean('import_hours'), 'coordinates' => $request->boolean('import_coordinates')];
            $draft['step'] = 4;
        } else {
            $request->validate(['confirmed' => ['accepted']]);
            $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $draft['data']);
            // Partnersperre koordiniert auch den bisherigen technischen Anlageweg.
            // Rechte, Testende und Quote werden erst unter dieser Sperre erneut geprüft.
            $source = $draft['source'] ?? null;
            $imports = $draft['imports'] ?? [];
            DB::transaction(function () use ($partner, $token, $data, $source, $imports, $request) {
                $locked = Partner::lockForUpdate()->findOrFail($partner->id);
                Gate::authorize('create', [Station::class, $locked]);
                $existing = Station::where('creation_token', $token)->first();
                if ($existing !== null) {
                    foreach ($data as $field => $value) {
                        if (($existing->{$field} ?? '') !== ($value ?? '')) {
                            throw ValidationException::withMessages(['station' => 'Dieser Entwurf wurde bereits mit anderen Angaben gespeichert. Bitte starten Sie eine neue Anlage.']);
                        }
                    }

                    return;
                }
                if ($locked->registration_owner_id !== null && Station::count() >= $locked->trial_station_limit) {
                    throw ValidationException::withMessages(['station_limit' => 'Das zugesagte Stationslimit ist erreicht.']);
                }
                // Gleicher Name und gleiche normalisierte Anschrift sind innerhalb
                // dieses Partners eine Dublette. Keine Auskunft über andere Partner.
                $identity = Station::identityFor($data);
                if (Station::where('identity_hash', $identity)->exists()) {
                    throw ValidationException::withMessages(['station' => 'Diese Station mit gleicher Anschrift ist bereits angelegt.']);
                }
                if ($source && Station::where('external_source_id', $source['data']['source_id'])->exists()) {
                    throw ValidationException::withMessages(['station' => 'Diese externe Station ist bei Ihrem Partner bereits verknüpft.']);
                }
                $station = new Station($data);
                $station->forceFill(['creation_token' => $token, 'identity_hash' => $identity,
                    'short_code' => StationCodes::suggest($data['city'], $data['street']),
                    'external_source_id' => $source['data']['source_id'] ?? null,
                    'external_source_data' => $source])->save();
                if ($source && (($imports['hours'] ?? false) || ($imports['coordinates'] ?? false))) {
                    app(StationSourceImport::class)->apply($request->user(), $station, $imports['hours'] ?? false, $imports['coordinates'] ?? false, 0, StationSourceImport::snapshot($station));
                }
            }, attempts: 3);

            return redirect()->route('partner.overview', $partner->id)->with('status_type', 'success')->with('status', 'Station erfolgreich angelegt. Mitarbeiter können jetzt im Mitarbeiterbereich erfasst werden.');
        }
        $request->session()->put('station_drafts.'.$token, $draft);

        return redirect()->route('stations.create', [$partner->id, 'draft' => $token]);
    }

    /** Fremde, veraltete oder manipulierte Entwürfe verraten keine gespeicherten Inhalte. */
    private function draft(Request $request, Partner $partner, mixed $token): array
    {
        abort_unless(is_string($token) && Str::isUuid($token), 404);
        $draft = $request->session()->get('station_drafts.'.$token);
        abort_unless(is_array($draft) && $draft['partner_id'] === $partner->id, 404);

        return $draft;
    }
}
