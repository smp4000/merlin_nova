<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kurzlebige Zugangsfähigkeit für genau eine Mitarbeiter-Selbstauskunft.
 * Kein normaler PartnerOwnedModel-Scope: Der öffentliche Einstieg kennt noch keinen
 * Partner. Ausschließlich EmployeeOnboarding darf UUID/Geheimnis prüfen und daraus
 * einen eng begrenzten Partnerkontext öffnen. Keine generischen CRUD-/JSON-Endpunkte.
 * E-Mail-Link und Sitzung speichern unterschiedliche Geheimnisse, jeweils nur gehasht
 * in dieser Tabelle. Personenwerte bleiben verschlüsselt und aus JSON ausgeblendet.
 */
class EmployeeInvitation extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['token_hash', 'grant_hash', 'data', 'submitted_data', 'question'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'submitted_at' => 'datetime', 'data' => 'encrypted:array', 'submitted_data' => 'encrypted:array', 'question' => 'encrypted'];
    }
}
