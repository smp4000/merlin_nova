<?php

namespace App\Models;

/**
 * Betriebsstation eines Partners mit manuell erfasster Anschrift und optionaler Marke.
 * Der Basisscope schützt Zuordnung und Abfragen; eine Marke beweist keine Betreiberrechte.
 * Interne Wiederholungsschlüssel sind nicht massenzuweisbar und nicht serialisierbar.
 * Quellenverknüpfung nur über den geprüften Assistenten; öffentliche Quellpreise
 * bleiben getrennt von manuellen Daten und generischen JSON-Antworten.
 */
class Station extends PartnerOwnedModel
{
    protected $fillable = ['name', 'brand', 'street', 'house_number', 'postal_code', 'city', 'country_code'];

    // Der Kurzcode wird nur durch den autorisierten Fachservice gesetzt.
    // Keine Freigabe als generisches Mass-Assignment-Feld.
    protected $hidden = ['creation_token', 'identity_hash', 'external_source_id', 'external_source_data'];

    protected function casts(): array
    {
        return ['external_source_data' => 'array'];
    }

    /**
     * Einheitlicher mandanteninterner Dublettenschlüssel für manuelle Stammdaten.
     * Schreibweise und Leerraum unterscheiden keine zweite Station. Der Schlüssel
     * ist kein weltweit eindeutiger Betreiber-/Standortnachweis.
     */
    public static function identityFor(array $data): string
    {
        $values = [];
        foreach (['name', 'street', 'postal_code', 'city', 'country_code'] as $field) {
            // Alte kombinierte Straßenzeilen bleiben hashkompatibel. Neue separat
            // erfasste Hausnummern zählen zur Anschrift, nicht zu Privatdetails.
            $value = $field === 'street' ? $data[$field].' '.($data['house_number'] ?? '') : $data[$field];
            $values[] = mb_strtolower(preg_replace('/\s+/u', '', $value));
        }

        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }
}
