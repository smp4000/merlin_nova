<?php

namespace App\Policies;

use App\Enums\PartnerRole;
use App\Models\Employee;
use App\Models\Partner;
use App\Models\User;

/**
 * Erste Personalakte ausschließlich für aktive Inhaber. Bestehende Firmenverwaltung,
 * Leser, Mitarbeiter und Superadmin erben keine sensiblen Personalrechte. Stations-
 * zuordnung beschreibt den Arbeitsort, nicht die Erlaubnis, Akten zu öffnen.
 */
class EmployeePolicy
{
    public function viewAny(User $user, Partner $partner): bool
    {
        return $user->roleFor($partner) === PartnerRole::Owner;
    }

    public function view(User $user, Employee $employee): bool
    {
        $partner = Partner::find($employee->partner_id);

        return $partner !== null && $this->viewAny($user, $partner);
    }

    public function create(User $user, Partner $partner): bool
    {
        return $partner->allowsOperationalWrites() && $this->viewAny($user, $partner);
    }

    public function update(User $user, Employee $employee): bool
    {
        $partner = Partner::find($employee->partner_id);

        return $partner !== null && $this->create($user, $partner);
    }

    /** Papierkorb und Wiederherstellung bleiben Fachänderungen mit Inhaber-/Trialprüfung. */
    public function delete(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }
}
