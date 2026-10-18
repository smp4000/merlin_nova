<?php

namespace App\Policies;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\Station;
use App\Models\User;

/**
 * Kleine Rechte-Matrix für die technische Stationsgrundlage.
 *
 * Inhaber und Verwaltung dürfen Stationsnamen anlegen/ändern, Mitarbeiter
 * und Leser nur lesen. Zugriff setzt immer eine aktive Mitgliedschaft voraus.
 * Stationszuordnungen und fachliche Rechte für Rundgänge/Kassenschichten
 * werden später separat eingeführt, nicht aus dieser Rolle abgeleitet.
 */
class StationPolicy
{
    public function create(User $user, Partner $partner): bool
    {
        return in_array($user->roleFor($partner), [PartnerRole::Owner, PartnerRole::Manager], true);
    }

    public function update(User $user, Station $station): bool
    {
        $partner = Partner::find($station->partner_id);

        return $partner !== null && $this->create($user, $partner);
    }
}
