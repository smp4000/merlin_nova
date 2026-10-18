<?php

namespace App\Enums;

/**
 * Erlaubte Firmenrollen mit stabilen englischen Speicherwerten.
 *
 * Deutsche Anzeigenamen sind davon getrennt und dürfen später geändert
 * werden, ohne gespeicherte Berechtigungen umzudeuten. Die Rolle bezeichnet
 * weder Personalaktenrechte noch eine konkrete Stationszuordnung.
 * Die eigentlichen Entscheidungen treffen Policies, nicht UI-Menüs.
 */
enum PartnerRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Inhaber',
            self::Manager => 'Verwaltung',
            self::Staff => 'Mitarbeiter',
            self::Viewer => 'Lesender Zugriff',
        };
    }
}
