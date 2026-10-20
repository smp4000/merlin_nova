<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationProfile;
use App\Stations\StationCodes;
use App\Stations\StationFields;
use App\Stations\StationMap;
use App\Stations\StationMedia;
use App\Stations\StationProfiles;
use App\Tenancy\PartnerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Eigene Stationsmaske im festgelegten Nova-Design, ohne Alt-Resource auszuführen.
 *
 * Kontakt-/Organisationsdetails werden nur Inhaber und Verwaltung offengelegt.
 * Bestehende Stationslisten und Leser erhalten diese Daten nicht automatisch.
 * ResolvePartner und scoped Route Binding laufen vor jeder Aktion; ein fremder
 * Datensatz wird deshalb schon vor der Formular-/Profilabfrage mit 404 abgewiesen.
 */
class StationDetailsController extends Controller
{
    /** Nach Testende bleibt diese autorisierte Detailansicht vollständig lesbar. */
    public function show(Request $request, PartnerContext $context, string $partner, Station $station): View
    {
        Gate::authorize('viewDetails', $station);
        if ($request->session()->getOldInput('_station_context') !== $context->id().':'.$station->id) {
            // Fehlerdaten eines anderen Standorts dürfen in parallelen Tabs nicht
            // als Vorschlag für diese Station erscheinen oder versehentlich wandern.
            $request->session()->forget('_old_input');
        }
        $profile = StationProfile::where('station_id', $station->id)->first();
        $canManageFinance = Gate::allows('manageFinance', $station);
        $profileData = $profile?->data ?? [];
        if (! $canManageFinance) {
            $profileData = array_diff_key($profileData, array_flip(StationFields::financeKeys()));
        }

        $sourceCompetitors = StationCompetitor::where('station_id', $station->id)->get();

        return view('stations.details', [
            'sourceCompetitors' => $sourceCompetitors,
            'mapPoints' => StationMap::points($station, $profileData, $sourceCompetitors),
            'partner' => Partner::findOrFail($context->id()), 'station' => $station,
            'profileData' => ['house_number' => $station->house_number, ...$profileData], 'profileVersion' => $profile?->version ?? 0,
            'snapshot' => StationProfiles::snapshot($station),
            'shortCodeSuggestion' => $station->short_code ?? StationCodes::suggest($station->city ?? '', $station->street ?? ''),
            'canWrite' => Gate::allows('update', $station),
            'canManageFinance' => $canManageFinance,
        ]);
    }

    /** Explizite Eingabegrenzen gelten auch ohne JavaScript oder sichtbaren Tab. */
    public function update(Request $request, string $partner, Station $station, StationProfiles $profiles, StationMedia $media): RedirectResponse
    {
        try {
            return $this->persist($request, $partner, $station, $profiles, $media);
        } catch (ValidationException $error) {
            if ($request->expectsJson()) {
                throw $error;
            }
            // Fehlerformulare dürfen keine neuen Bank-/Steuerwerte in die Sitzung
            // übernehmen. Falsch verschachtelte Angriffe werden ebenfalls entfernt,
            // damit die erneute HTML-Ausgabe keine Arrays als Texte rendern muss.
            $safe = ['_station_context' => $station->partner_id.':'.$station->id];
            foreach (['name', 'brand', 'street', 'postal_code', 'city', 'country_code', 'short_code', 'version', 'snapshot'] as $key) {
                if (is_scalar($request->input($key))) {
                    $safe[$key] = mb_substr((string) $request->input($key), 0, 255);
                }
            }
            $profile = $request->input('profile', []);
            if (is_array($profile)) {
                foreach (StationFields::fields() as $group => $fields) {
                    if ($group === 'finance') {
                        continue;
                    }
                    foreach (array_keys($fields) as $key) {
                        if (is_scalar($profile[$key] ?? null)) {
                            $safe['profile'][$key] = mb_substr((string) $profile[$key], 0, $fields[$key][1] === 'textarea' ? 2000 : 255);
                        }
                    }
                }
                foreach (['fuel_types', 'services', 'additional_businesses'] as $key) {
                    if (is_array($profile[$key] ?? null)) {
                        $safe['profile'][$key] = array_map(fn ($value) => mb_substr($value, 0, 100), array_slice(array_values(array_filter($profile[$key], 'is_string')), 0, 30));
                    } elseif ($key === 'fuel_types' && is_string($profile[$key] ?? null)) {
                        $safe['profile'][$key] = mb_substr($profile[$key], 0, 3030);
                    }
                }
                foreach (['opening_hours', 'competitors'] as $key) {
                    if (is_array($profile[$key] ?? null)) {
                        foreach (array_slice($profile[$key], 0, $key === 'competitors' ? 8 : 7, true) as $index => $row) {
                            if (is_array($row)) {
                                $rowKeys = $key === 'opening_hours' ? ['status', 'open', 'close'] : ['name', 'brand', 'street', 'postal_code', 'city', 'distance', 'notes', 'latitude', 'longitude', 'price_super', 'price_e10', 'price_diesel'];
                                $safe['profile'][$key][$index] = array_map(fn ($value) => $value === null ? null : mb_substr((string) $value, 0, 500), array_filter(array_intersect_key($row, array_flip($rowKeys)), fn ($value) => is_scalar($value) || $value === null));
                            }
                        }
                    }
                }
            }

            return back()->withErrors($error->errors())->withInput($safe);
        }
    }

    /** Validierung und Mutation bleiben getrennt von der datensparsamen Fehlerausgabe. */
    private function persist(Request $request, string $partner, Station $station, StationProfiles $profiles, StationMedia $media): RedirectResponse
    {
        if (is_string($request->input('short_code'))) {
            $request->merge(['short_code' => StationCodes::normalize($request->input('short_code'))]);
        }
        Gate::authorize('update', $station);
        $base = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/\S/u'],
            'brand' => ['nullable', 'string', 'max:100'],
            'short_code' => ['sometimes', 'required', 'string', 'max:8', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)?$/'],
            'street' => ['required', 'string', 'max:255', 'regex:/\S/u'],
            'postal_code' => ['required', 'string', 'max:20', 'regex:/\S/u'],
            'city' => ['required', 'string', 'max:100', 'regex:/\S/u'],
            'country_code' => ['required', 'in:DE,AT,CH'],
            'version' => ['required', 'integer', 'min:0'],
            'snapshot' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'profile' => ['present', 'array'],
            'partner_id' => ['prohibited'], 'tenant_id' => ['prohibited'],
            'device_setup_token' => ['prohibited'], 'enrollment_token' => ['prohibited'],
            'role' => ['prohibited'], 'is_super_admin' => ['prohibited'], 'bankAccounts' => ['prohibited'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ], [
            'required' => 'Das Feld :attribute muss ausgefüllt werden.',
            'present' => 'Die Stationsdaten fehlen. Bitte laden Sie das Formular neu.',
            'string' => 'Das Feld :attribute muss Text enthalten.',
            'max' => 'Das Feld :attribute überschreitet die zulässige Größe oder Länge (:max).',
            'min' => 'Der Wert für :attribute ist zu klein.',
            'integer' => 'Das Feld :attribute muss eine ganze Zahl enthalten.',
            'regex' => 'Das Format für :attribute ist ungültig.',
            'in' => 'Die Auswahl für :attribute ist ungültig.',
            'array' => 'Die Struktur für :attribute ist ungültig.',
            'prohibited' => 'Das Feld :attribute darf nicht über dieses Formular geändert werden.',
            'image' => 'Bitte wählen Sie ein gültiges Rasterbild.',
            'mimes' => 'Erlaubt sind ausschließlich JPG- und PNG-Bilder.',
            'dimensions' => 'Die Bildabmessungen sind zu groß (höchstens 6000 Pixel je Seite).',
        ], ['name' => 'Stationsname', 'street' => 'Straße', 'postal_code' => 'Postleitzahl', 'city' => 'Ort', 'country_code' => 'Land', 'profile' => 'Stationsdaten', 'logo' => 'Stationslogo', 'photos' => 'Stationsfotos']);
        $input = $base['profile'];
        // Textlisten werden bewusst nur auf Zeilen aufgeteilt. Kein freies JSON
        // aus einem versteckten Feld und keine unvalidierten Repeater-Unterkeys.
        if (isset($input['fuel_types']) && is_string($input['fuel_types'])) {
            $input['fuel_types'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $input['fuel_types']))));
        }
        foreach (['services', 'additional_businesses'] as $list) {
            $input[$list] ??= [];
        }
        foreach (['competitors', 'bank_accounts'] as $list) {
            if (isset($input[$list]) && is_array($input[$list])) {
                $input[$list] = array_values(array_filter($input[$list], fn ($row) => ! is_array($row) || count(array_filter($row, fn ($value) => $value !== null && $value !== '')) > 0));
            }
        }
        $canManageFinance = Gate::allows('manageFinance', $station);
        abort_if(! $canManageFinance && array_intersect(array_keys($input), StationFields::financeKeys()) !== [], 403);
        $previous = StationProfile::where('station_id', $station->id)->first()?->data ?? [];
        $data = StationFields::validate($input, $canManageFinance, $previous);
        $baseFields = array_intersect_key($base, array_flip(['name', 'brand', 'street', 'postal_code', 'city', 'country_code', 'short_code']));
        $prepared = $media->prepare($station, $request->file('logo'), $request->file('photos', []));
        try {
            $profiles->save($request->user(), $station, $baseFields, $data, (int) $base['version'], $base['snapshot'], $prepared);
        } catch (\Throwable $error) {
            StationMedia::discard($prepared);
            throw $error;
        }

        return redirect()->route('stations.details', [$partner, $station->id])->with('status_type', 'success')->with('status', 'Stationsdaten gespeichert.');
    }

    /** Bildauslieferung ist genauso mandanten-/rollenbegrenzt wie die Detailansicht. */
    public function image(PartnerContext $context, string $partner, Station $station, string $image)
    {
        Gate::authorize('viewDetails', $station);
        $profile = StationProfile::where('station_id', $station->id)->firstOrFail();
        $item = collect($profile->data['media'] ?? [])->firstWhere('id', $image);
        abort_unless($item && str_starts_with($item['path'], 'station-media/'.$context->id().'/'.$station->id.'/'), 404);
        abort_unless(Storage::disk('local')->exists($item['path']), 404);

        return response()->file(Storage::disk('local')->path($item['path']), ['Content-Type' => $item['mime'], 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
