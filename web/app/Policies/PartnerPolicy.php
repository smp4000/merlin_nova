<?php

namespace App\Policies;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\User;

/**
 * Rechte an der Firma und ihrer Zugangsliste, getrennt von Plattformrechten.
 *
 * Alle aktiven Mitglieder dürfen den Firmenkopf lesen. Nur Inhaber dürfen
 * ihn ändern oder die Zugangsliste mit E-Mail-Adressen einsehen. Verwaltung,
 * Mitarbeiter und Leser bekommen daraus keine Berechtigung zur Rechtevergabe.
 * Steuer-/Bank-/Personaldaten sind hier ausdrücklich nicht freigeschaltet.
 */
class PartnerPolicy
{
    public function view(User $user, Partner $partner): bool
    {
        return $user->roleFor($partner) !== null;
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->roleFor($partner) === PartnerRole::Owner && $partner->allowsOperationalWrites();
    }

    public function manageMembers(User $user, Partner $partner): bool
    {
        return $user->roleFor($partner) === PartnerRole::Owner;
    }
}
