<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Personalakte eines Partners, ausdrücklich kein Benutzerkonto. Alle fachlichen
 * Inhalte sind nur über autorisierte Personalaktionen zugänglich und verschlüsselt.
 * Die erste Fassung verwendet den Partner als Arbeitgeberbereich; mehrere rechtliche
 * Arbeitgeber innerhalb eines Partners benötigen vor Nutzung eine eigene Erweiterung.
 * Personalnummern sind deshalb zunächst je Partner eindeutig, niemals je Station.
 */
class Employee extends PartnerOwnedModel
{
    // Papierkorb blendet die Akte mandantenweit aus; Inhalte werden erst durch
    // einen gesonderten, künftig geregelten Aufbewahrungs-/Löschlauf vernichtet.
    use SoftDeletes;

    protected $guarded = ['*'];

    protected $hidden = ['identity', 'personal_data', 'employer_data', 'creation_key', 'personnel_number'];

    protected function casts(): array
    {
        return ['identity' => 'encrypted:array', 'personal_data' => 'encrypted:array', 'employer_data' => 'encrypted:array'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
