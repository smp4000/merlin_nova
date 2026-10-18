<?php

namespace App\Http\Controllers;

use App\Billing\BillingCatalog;
use Illuminate\Contracts\View\View;

/**
 * Öffentlicher Produkteinstieg, getrennt von Partner- und Plattformrechten.
 * Ausschließlich bewusst veröffentlichte Preisfassungen werden übernommen.
 * Testdauer/-limit stammen aus dem aktiven Standard, niemals aus Mockupwerten.
 * Interne Bearbeiterdaten und Entwürfe gehören nicht in die öffentliche View.
 * Diese Seite startet keinen Test und sammelt noch keine Registrierungsdaten.
 */
class LandingController extends Controller
{
    public function __invoke(BillingCatalog $catalog): View
    {
        $policy = $catalog->activeTrialPolicy();

        return view('landing', [
            'offers' => $catalog->publishedOffers(),
            'trialDays' => $policy?->duration_days,
            'trialStationLimit' => $policy?->station_limit,
        ]);
    }
}
