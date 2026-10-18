<?php

namespace App\Models;

/**
 * Minimale Station als erster operativer Datensatz innerhalb eines Partners.
 *
 * Der Name dient aktuell dem Nachweis der Mandantentrennung über echte
 * Schreib-/Lese-Endpunkte. Adressdaten, Benzinpreisabfrage, Stationsassistent
 * und stationsbezogene Benutzerrechte sind noch nicht implementiert.
 * partner_id wird nie aus Eingaben übernommen, sondern vom Kontext gesetzt.
 */
class Station extends PartnerOwnedModel
{
    protected $fillable = ['name'];
}
