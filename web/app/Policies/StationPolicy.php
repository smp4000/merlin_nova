<?php

namespace App\Policies;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\Station;
use App\Models\User;

/**
 * Rechte-Matrix für Stationskopf, vertrauliche Stammdaten und Finanzsicht.
 *
 * Inhaber und Verwaltung dürfen Stationen anlegen/ändern und deren Detaildaten
 * lesen. Bank-/Steuerdaten bleiben ausschließlich beim Inhaber. Mitarbeiter und
 * Leser behalten die minimale Liste ohne Kontakte, Profile oder Bilder.
 * Zugriff setzt immer eine aktive Mitgliedschaft voraus; Schreiben zusätzlich
 * den aktuellen Teststatus. Die Finanzsicht ist keine Zahlungsvollmacht.
 * Stationszuordnungen und fachliche Rechte für Rundgänge/Kassenschichten
 * werden später separat eingeführt, nicht aus dieser Rolle abgeleitet.
 */
class StationPolicy
{
    /** Stationsbankdaten bleiben Inhabern vorbehalten; kein pauschales Verwaltungsrecht. */
    public function manageFinance(User $user, Station $station): bool
    {
        $partner = Partner::find($station->partner_id);

        return $partner !== null && $user->roleFor($partner) === PartnerRole::Owner;
    }

    /**
     * Erweiterte Stammdaten enthalten Kontakte und interne Organisationsangaben.
     * Diese Sicht ist Inhaber/Verwaltung vorbehalten, unabhängig vom schreibenden
     * Teststatus. Lesende Rollen behalten die bisherige minimale Stationssicht.
     */
    public function viewDetails(User $user, Station $station): bool
    {
        $partner = Partner::find($station->partner_id);

        return $partner !== null && in_array($user->roleFor($partner), [PartnerRole::Owner, PartnerRole::Manager], true);
    }

    public function create(User $user, Partner $partner): bool
    {
        return $partner->allowsOperationalWrites() && in_array($user->roleFor($partner), [PartnerRole::Owner, PartnerRole::Manager], true);
    }

    public function update(User $user, Station $station): bool
    {
        $partner = Partner::find($station->partner_id);

        return $partner !== null && $this->create($user, $partner);
    }
}
