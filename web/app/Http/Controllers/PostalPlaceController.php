<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Station;
use App\Stations\Providers\PostalPlaceProvider;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Ortsvorschläge nur für berechtigte Stationsverwalter; kein öffentlicher Geocodingproxy. */
class PostalPlaceController extends Controller
{
    public function lookup(Request $request, PartnerContext $context, PostalPlaceProvider $provider)
    {
        Gate::authorize('create', [Station::class, Partner::findOrFail($context->id())]);
        $input = $request->validate(['postal_code' => ['required', 'string', 'regex:/^\d{5}$/D'], 'country_code' => ['required', 'in:DE']]);

        return response()->json($provider->lookup($input['postal_code']))->header('Cache-Control', 'private, no-store');
    }
}
