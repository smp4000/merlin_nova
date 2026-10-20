<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Stations\StationCodes;
use App\Tenancy\PartnerContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Technische Lese-/Schreibstrecke für mandantengebundene Stationsnamen.
 *
 * PartnerOwnedModel begrenzt Abfragen und Route Binding, StationPolicy
 * begrenzt Schreibrechte. Beide sind notwendig: Ein korrekter Partner allein
 * erlaubt einem Leser noch keine Änderung. Die Eingabe enthält nur den Namen;
 * Partner-ID und Privilegien dürfen nicht vom Browser übernommen werden.
 * Der manuelle Stationsassistent ist ein eigener HTML-Einstieg. Diese schmale
 * technische Schnittstelle bleibt für bestehende lokale Clients erhalten.
 */
class StationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Station::query()->select(['id', 'name'])->orderBy('id')->paginate(25));
    }

    public function store(Request $request, PartnerContext $context): JsonResponse
    {
        Gate::authorize('create', [Station::class, Partner::findOrFail($context->id())]);
        $data = $this->validated($request);
        // Derselbe Partner wird vor jeder Anlage gesperrt: parallele Anfragen
        // dürfen das zugesagte Test-Stationslimit nicht gemeinsam überschreiten.
        $station = DB::transaction(function () use ($context, $data): Station {
            $partner = Partner::lockForUpdate()->findOrFail($context->id());
            Gate::authorize('create', [Station::class, $partner]);
            // Der alte technische Einstieg kennt nur einen Namen. Eine identische
            // Wiederholung liefert deshalb die vorhandene technische Station,
            // bevor die Quote geprüft wird; Adressstationen werden nicht verwechselt.
            $existing = Station::whereNull('identity_hash')->where('name', $data['name'])->first();
            if ($existing !== null) {
                return $existing;
            }
            if ($partner->registration_owner_id !== null && Station::count() >= $partner->trial_station_limit) {
                throw ValidationException::withMessages(['station_limit' => 'Das Stationslimit Ihres kostenlosen Tests ist erreicht.']);
            }

            $station = new Station($data);
            $station->forceFill(['short_code' => StationCodes::suggest('')])->save();

            return $station;
        }, attempts: 3);

        return response()->json($station->only(['id', 'name']), 201);
    }

    public function update(Request $request, string $partner, Station $station): JsonResponse
    {
        Gate::authorize('update', $station);
        $data = $this->validated($request);
        // Dieselbe Partnersperre wie bei Anlagen hält Namen und Dublettenschlüssel
        // konsistent. Auch ein während der Eingabe abgelaufener Test wird erkannt.
        $station = DB::transaction(function () use ($station, $data): Station {
            Partner::lockForUpdate()->findOrFail($station->partner_id);
            $current = Station::findOrFail($station->id);
            Gate::authorize('update', $current);
            $current->fill($data);
            if ($current->identity_hash !== null) {
                $identity = Station::identityFor($current->getAttributes());
                if (Station::where('identity_hash', $identity)->whereKeyNot($current->id)->exists()) {
                    throw ValidationException::withMessages(['name' => 'Diese Station mit gleicher Anschrift ist bereits angelegt.']);
                }
                $current->forceFill(['identity_hash' => $identity]);
            }
            $current->save();

            return $current;
        }, attempts: 3);

        return response()->json($station->only(['id', 'name']));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/\S/u'],
            'partner_id' => ['prohibited'],
            'role' => ['prohibited'],
            'is_super_admin' => ['prohibited'],
        ]);

        return ['name' => trim($data['name'])];
    }
}
