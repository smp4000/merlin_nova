<?php

namespace App\Models;

/**
 * Vorbereitender lokaler Mandatsdatensatz, nicht ein einziehbares Bankmandat.
 * Bankverbindung, Kontoinhaber und Schuldneranschrift werden mit APP_KEY
 * verschlüsselt und niemals über Modell-JSON ausgegeben. Registrierung setzt
 * hierfür intern kurzzeitig den neuen Partnerkontext; normale Zugriffe bleiben
 * mandantengebunden. Gläubiger-/Textfreigabe und bezahlter Vertrag fehlen noch.
 * Schlüsselbackup/-rotation und Aufbewahrungsregeln brauchen eigene Abnahme.
 */
class SepaMandate extends PartnerOwnedModel
{
    protected $guarded = ['*'];

    protected $hidden = ['iban', 'account_holder', 'debtor_address', 'declaration_text'];

    protected function casts(): array
    {
        return ['iban' => 'encrypted', 'account_holder' => 'encrypted', 'debtor_address' => 'encrypted:array', 'declared_at' => 'immutable_datetime'];
    }

    public function canCollect(): bool
    {
        return false;
    }
}
