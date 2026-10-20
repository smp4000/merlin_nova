<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Privater Beschäftigungsnachweis einer Personalakte. Der Partnerscope begrenzt
 * jede Auflösung; der Dateipfad wird ausschließlich serverseitig erzeugt.
 * Mitarbeiterdateien sind bis submitted_at nur in ihrer Einladung sichtbar.
 * Dateiname und Dateiinhalt werden getrennt verschlüsselt gespeichert. Löschen
 * verbirgt eine Fassung, ersetzt aber keine rechtlich geklärte Aufbewahrung.
 */
class EmployeeDocument extends PartnerOwnedModel
{
    use SoftDeletes;

    protected $guarded = ['*'];

    protected $hidden = ['storage_path', 'original_name'];

    protected function casts(): array
    {
        return ['original_name' => 'encrypted', 'submitted_at' => 'datetime'];
    }
}
