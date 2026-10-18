<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
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
 * Der Stationsassistent einschließlich Preisabfrage folgt später separat.
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
            if ($partner->registration_owner_id !== null && Station::count() >= $partner->trial_station_limit) {
                throw ValidationException::withMessages(['station_limit' => 'Das Stationslimit Ihres kostenlosen Tests ist erreicht.']);
            }

            return Station::create($data);
        }, attempts: 3);

        return response()->json($station->only(['id', 'name']), 201);
    }

    public function update(Request $request, string $partner, Station $station): JsonResponse
    {
        Gate::authorize('update', $station);
        $station->update($this->validated($request));

        return response()->json($station->only(['id', 'name']));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'partner_id' => ['prohibited'],
            'role' => ['prohibited'],
            'is_super_admin' => ['prohibited'],
        ]);

        return ['name' => $data['name']];
    }
}
