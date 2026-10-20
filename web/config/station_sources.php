<?php

/**
 * Betreiberseitig austauschbarer Geocodingdienst, niemals aus Requestdaten.
 * Leerer Endpunkt deaktiviert die Abfrage. Bestehende lokale .env bleibt unverändert.
 * Öffentliche Nominatim-Regeln: explizite Aktion, App-Kennung, Cache, Attribution,
 * maximal ein Request/Sekunde anwendungsweit; kein Autocomplete/Batchbetrieb.
 */
return ['postal_endpoint' => env('NOVA_POSTAL_ENDPOINT', 'https://nominatim.openstreetmap.org/search')];
