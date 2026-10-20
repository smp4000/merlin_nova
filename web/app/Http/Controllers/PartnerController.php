<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Tenancy\PartnerContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Autorisierte Partnerübersicht und schmale technische Partner-Endpunkte.
 *
 * Die HTML-Übersicht ergänzt die bestehenden JSON-Endpunkte. Der Kontext wird
 * ausschließlich durch ResolvePartner aufgebaut; Firmenänderungen und die
 * personenbezogene Zugangsliste brauchen zusätzliche eigene Policy-Rechte.
 * Explizite Feldlisten verhindern, dass spätere sensible Modellfelder
 * versehentlich in die bisherigen Antworten oder Schreibzugriffe gelangen.
 */
class PartnerController extends Controller
{
    /** Liefert nur Stationen des durch Middleware autorisierten Partnerkontexts. */
    public function overview(PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());

        return view('partner.overview', [
            'partner' => $partner,
            'stations' => Station::orderBy('name')->paginate(20),
            'stationCount' => Station::count(),
        ]);
    }

    public function show(PartnerContext $context): JsonResponse
    {
        return response()->json(Partner::findOrFail($context->id())->only(['id', 'name']));
    }

    public function update(Request $request, PartnerContext $context): JsonResponse
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('update', $partner);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['prohibited'],
            'is_super_admin' => ['prohibited'],
            'role' => ['prohibited'],
            'partner_id' => ['prohibited'],
        ]);
        $partner->update(['name' => $data['name']]);

        return response()->json($partner->only(['id', 'name']));
    }

    public function members(PartnerContext $context): JsonResponse
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('manageMembers', $partner);

        return response()->json($partner->memberships()->with('user:id,name,email')->paginate(25));
    }
}
