<?php

namespace App\Models;

/**
 * Geschützte, nachträglich nicht über die Oberfläche editierbare Prüffassung.
 * Enthält bei Freigabe genau die bestätigten persönlichen und Arbeitgeberangaben,
 * bei Rückfrage nur den notwendigen Rückfragetext. Kein allgemeines Activity-Log:
 * sensible Snapshots bleiben verschlüsselt und benötigen dieselben Inhaberrechte.
 */
class EmployeeReview extends PartnerOwnedModel
{
    public $timestamps = false;

    protected $guarded = ['*'];

    protected $hidden = ['snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array', 'created_at' => 'datetime'];
    }
}
