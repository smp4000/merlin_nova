<?php

namespace App\Stations;

use App\Rules\Iban;
use DateTimeZone;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Gemeinsamer Feldkatalog für die neue Stationsmaske und ihre Servervalidierung.
 *
 * Grundlage ist die vom Nutzer gelieferte GasStationResource. Fehlende externe
 * Übersetzungs-/Lookupkataloge wurden ausdrücklich zur Übernahme freigegeben
 * und liegen eigenständig in StationCatalogs. Weitere Felder bleiben Texte.
 * IDs, Rollen, Geräteschlüssel, beliebige Settings und Providerdaten sind keine
 * schreibbaren Stammdatenfelder. Finanzfelder werden nur für ausdrücklich
 * autorisierte Inhaber in die Validierungswhitelist aufgenommen.
 */
final class StationFields
{
    public const GROUPS = [
        'address' => 'Adresse & Kontakt', 'general' => 'Allgemein',
        'hours' => 'Öffnungszeiten', 'shop' => 'Shop & Betrieb',
        'competition' => 'Wettbewerb', 'map' => 'Karte', 'finance' => 'Finanzen',
    ];

    public const DAYS = [
        'monday' => 'Montag', 'tuesday' => 'Dienstag', 'wednesday' => 'Mittwoch',
        'thursday' => 'Donnerstag', 'friday' => 'Freitag', 'saturday' => 'Samstag', 'sunday' => 'Sonntag',
    ];

    /** Felddefinitionen [deutsches Label, Eingabetyp, technische Maximallänge]. */
    public static function fields(): array
    {
        return [
            'address' => [
                'academic_title' => ['Titel / Anrede', 'text', 100],
                'contact_first_name' => ['Vorname des Ansprechpartners', 'text', 255],
                'contact_last_name' => ['Nachname des Ansprechpartners', 'text', 255],
                'house_number' => ['Hausnummer (falls separat erfasst)', 'text', 20],
                'district_part' => ['Ortsteil', 'text', 100], 'state' => ['Bundesland / Kanton', 'text', 100],
                'phone' => ['Telefon', 'tel', 50], 'fax' => ['Fax', 'tel', 50],
                'email' => ['E-Mail-Adresse der Station', 'email', 255], 'website' => ['Website', 'url', 255],
            ],
            'general' => [
                'latitude' => ['Breitengrad', 'latitude', 30], 'longitude' => ['Längengrad', 'longitude', 30],
                'external_number' => ['Stationsnummer der Gesellschaft', 'text', 20],
                'station_number' => ['Bisherige interne Stationsnummer (Freitext)', 'legacy', 50],
                'ownership_type' => ['Eigentums- / Betriebsart', 'text', 100],
                'sales_channel' => ['Vertriebsweg', 'text', 100], 'district' => ['Bezirk', 'text', 100],
                'district_description' => ['Bezirksbeschreibung', 'text', 255], 'region' => ['Region', 'text', 100],
                'region_manager' => ['Regionsleitung', 'text', 255],
                'station_number_fuel' => ['Stationsnummer Kraftstoff', 'text', 20],
                'station_number_shop' => ['Stationsnummer Shop', 'text', 20],
                'has_toll_terminal' => ['Mautterminal vorhanden', 'boolean', 1],
                'is_active' => ['Standort aktiv', 'boolean', 1],
            ],
            'hours' => [
                'timezone' => ['Zeitzone', 'timezone', 100],
                'first_opening_ok' => ['Erstöffnung OK', 'date', 10],
                'first_opening_dk' => ['Erstöffnung DK', 'date', 10],
            ],
            'shop' => [
                'num_pumps' => ['Anzahl Zapfsäulen', 'integer', 10000],
                'has_camera' => ['Videoüberwachung vorhanden', 'boolean', 1],
                'has_car_wash' => ['Waschanlage vorhanden', 'boolean', 1],
                'has_shop' => ['Shop vorhanden', 'boolean', 1],
                'shop_area_sqm' => ['Verkaufsfläche (m²)', 'area', 20],
                'shop_size' => ['Bisherige Shopgröße (Freitext)', 'legacy', 50],
                'shop_type' => ['Shoptyp', 'text', 100], 'shop_class' => ['Shopklasse', 'text', 100],
                'shop_setup_date' => ['Shop-Einrichtungsdatum', 'date', 10],
                'nielsen_area' => ['Nielsen-Gebiet', 'text', 100], 'price_region' => ['Preisregion', 'text', 50],
                'assortment_level' => ['Sortimentsstufe', 'text', 100],
                'shop_partner' => ['Shoppartner', 'text', 255], 'shop_operation_number' => ['Shop-Betriebsnummer', 'text', 20],
                'car_wash_brand' => ['Marke der Waschanlage', 'text', 255],
                'car_wash_type' => ['Typ der Waschanlage', 'text', 255],
                'wash_drive_through' => ['Durchfahranlage', 'boolean', 1],
                'wash_underbody' => ['Unterbodenwäsche', 'boolean', 1],
                'wash_height' => ['Maximale Waschhöhe (m)', 'dimension', 20],
                'wash_width' => ['Maximale Waschbreite (m)', 'dimension', 20],
                'wash_ticket_system' => ['Ticketsystem vorhanden', 'boolean', 1],
                'wash_easy_carwash_pro' => ['Easy Carwash Pro vorhanden', 'boolean', 1],
                'wash_notes' => ['Hinweise zur Waschanlage', 'textarea', 2000],
                'notes' => ['Allgemeine Notizen', 'textarea', 2000],
            ],
            'competition' => [
                'price_super' => ['Super E5 (EUR/l, manuell)', 'price', 20],
                'price_e10' => ['Super E10 (EUR/l, manuell)', 'price', 20],
                'price_diesel' => ['Diesel (EUR/l, manuell)', 'price', 20],
            ],
            'map' => [],
            'finance' => [
                'vat_id' => ['USt-IdNr.', 'text', 20],
                'tax_id' => ['Bisherige Steuerkennung (Freitext)', 'legacy', 100],
                'trade_register' => ['Handelsregister', 'text', 255],
            ],
        ];
    }

    /** Nur diese Daten werden bei Verwaltungszugängen vor Anzeige vollständig entfernt. */
    public static function financeKeys(): array
    {
        return ['vat_id', 'tax_id', 'trade_register', 'bank_accounts'];
    }

    /** Nicht automatisch als neue Fachfelder interpretierte Bestandsangaben. */
    public static function legacyKeys(): array
    {
        return ['station_number', 'shop_size', 'tax_id'];
    }

    /** Bekannte Vorschläge sind keine Behauptung einer Markenpartnerschaft. */
    public static function choices(): array
    {
        return [
            'services' => ['bakery' => 'Backshop', 'atm' => 'Geldautomat', 'cafe' => 'Café / Restaurant'],
            'additional_businesses' => [
                'hermes' => 'Hermes Paketshop', 'ups' => 'UPS Access Point', 'dhl' => 'DHL Paketshop',
                'dpd' => 'DPD Pickup', 'gls' => 'GLS Paketshop', 'amazon_locker' => 'Amazon Locker',
                'lotto' => 'Lotto', 'toto' => 'Toto', 'sportwetten' => 'Sportwetten',
                'tire_service' => 'Reifenservice', 'master_shop' => 'Meisterbetrieb', 'car_rental' => 'Autovermietung',
                'oil_service' => 'Ölservice', 'used_cars' => 'Gebrauchtwagenhandel', 'tuev_dekra' => 'TÜV / Dekra-Prüfstelle',
            ],
        ];
    }

    /**
     * Prüft vollständige Stammdaten und weist unbekannte Schlüssel ausdrücklich ab.
     * $previous muss aus dem autorisierten Profil derselben Station stammen,
     * niemals aus Requestdaten: nur damit bleiben frühere freie Auswahlwerte
     * ohne stillschweigende Umdeutung speicherbar. Neue Werte unterliegen dem
     * freigegebenen Katalog; freie Vorschlagsfelder behalten ihre Längengrenzen.
     */
    public static function validate(array $input, bool $finance = false, array $previous = []): array
    {
        if (is_string($input['shop_area_sqm'] ?? null)) {
            $input['shop_area_sqm'] = str_replace(',', '.', trim($input['shop_area_sqm']));
        }
        $rules = [];
        $attributes = [];
        foreach (self::fields() as $fields) {
            foreach ($fields as $key => [$label, $type, $limit]) {
                $rules[$key] = match ($type) {
                    'boolean' => ['nullable', 'boolean'],
                    'integer' => ['nullable', 'integer', 'min:1', 'max:10000'],
                    'date' => ['nullable', 'date_format:Y-m-d'],
                    'area' => ['nullable', 'numeric', 'between:0,999999.9', 'decimal:0,1', 'regex:/^\d+(?:\.\d)?$/'],
                    'timezone' => ['nullable', Rule::in(DateTimeZone::listIdentifiers())],
                    'email' => ['nullable', 'string', 'email:rfc', 'max:255'],
                    'url' => ['nullable', 'string', 'url:http,https', 'max:255'],
                    'price' => ['nullable', 'string', 'regex:/^(?:0|[1-9]\d{0,2})(?:\.\d{1,3})?$/'],
                    'dimension' => ['nullable', 'numeric', 'between:0.01,100', 'decimal:0,2'],
                    'latitude' => ['nullable', 'numeric', 'between:-90,90', 'decimal:0,8'],
                    'longitude' => ['nullable', 'numeric', 'between:-180,180', 'decimal:0,8'],
                    default => ['nullable', 'string', 'max:'.$limit],
                };
                $attributes[$key] = $label;
                if (in_array($key, ['station_number_fuel', 'station_number_shop', 'shop_operation_number'], true)
                    && is_string($previous[$key] ?? null) && mb_strlen($previous[$key]) > $limit) {
                    // Längere bereits gespeicherte Kennungen werden nicht gekürzt.
                    // Neu eingegebene Werte müssen trotzdem in 20 Zeichen passen.
                    $rules[$key] = ['nullable', 'string', function ($attribute, $value, $fail) use ($previous, $key, $limit) {
                        if (mb_strlen($value) > $limit && $value !== $previous[$key]) {
                            $fail('Neue Stationsnummern dürfen höchstens 20 Zeichen enthalten.');
                        }
                    }];
                }
            }
        }
        if ($finance) {
            $rules['bank_accounts'] = ['nullable', 'array', 'max:10'];
            $rules['bank_accounts.*'] = ['array:iban,bank_name,bic,description,account_type'];
            $rules['bank_accounts.*.iban'] = ['required', 'string', 'max:50', new Iban];
            foreach (['bank_name' => 255, 'description' => 255, 'account_type' => 100] as $key => $max) {
                $rules['bank_accounts.*.'.$key] = ['nullable', 'string', 'max:'.$max];
            }
            $rules['bank_accounts.*.bic'] = ['nullable', 'string', 'regex:/^[A-Z]{6}[A-Z0-9]{2}(?:[A-Z0-9]{3})?$/'];
        } else {
            foreach (self::financeKeys() as $key) {
                unset($rules[$key]);
            }
        }
        foreach (StationCatalogs::fixed() as $key => $choices) {
            // Nur der Bestandswert dieser bereits autorisierten Station ist
            // zusätzlich erlaubt. Ein fremder oder bloß im Request behaupteter
            // Altwert kann damit keinen neuen Katalogwert einschleusen.
            $allowed = array_map('strval', array_keys($choices));
            if (is_string($previous[$key] ?? null)) {
                $allowed[] = $previous[$key];
            }
            $rules[$key][] = Rule::in($allowed);
        }
        foreach (self::choices() as $key => $choices) {
            $rules[$key] = ['nullable', 'array', 'max:'.count($choices)];
            $rules[$key.'.*'] = ['string', 'distinct', Rule::in(array_keys($choices))];
        }
        $rules['fuel_types'] = ['nullable', 'array', 'max:30'];
        $rules['fuel_types.*'] = ['string', 'max:100', 'distinct'];
        $rules['opening_hours'] = ['nullable', 'array:'.implode(',', array_keys(self::DAYS))];
        foreach (self::DAYS as $day => $label) {
            $rules['opening_hours.'.$day] = ['nullable', 'array:status,open,close'];
            $rules['opening_hours.'.$day.'.status'] = ['required_with:opening_hours.'.$day, Rule::in(['unknown', 'closed', 'all_day', 'interval'])];
            $rules['opening_hours.'.$day.'.open'] = ['nullable', 'date_format:H:i'];
            $rules['opening_hours.'.$day.'.close'] = ['nullable', 'date_format:H:i'];
        }
        $competitorKeys = ['name', 'brand', 'street', 'postal_code', 'city', 'distance', 'notes', 'latitude', 'longitude', 'price_super', 'price_e10', 'price_diesel'];
        $rules['competitors'] = ['nullable', 'array', 'max:8'];
        $rules['competitors.*'] = ['array:'.implode(',', $competitorKeys)];
        foreach ($competitorKeys as $key) {
            $rules['competitors.*.'.$key] = match ($key) {
                'name' => ['required', 'string', 'max:255'],
                'distance' => ['nullable', 'numeric', 'between:0,20000', 'decimal:0,1'],
                'latitude', 'longitude', 'price_super', 'price_e10', 'price_diesel' => $rules[$key],
                default => ['nullable', 'string', 'max:'.($key === 'notes' ? 500 : 255)],
            };
        }
        $allowed = array_filter(array_keys($rules), fn ($key) => ! str_contains($key, '.'));
        if (array_diff(array_keys($input), $allowed)) {
            throw ValidationException::withMessages(['profile' => 'Die Stationsdaten enthalten nicht erlaubte Felder.']);
        }
        $data = Validator::make($input, $rules, [
            'required' => 'Das Feld :attribute muss ausgefüllt werden.',
            'required_with' => 'Das Feld :attribute muss vollständig angegeben werden.',
            'string' => 'Das Feld :attribute muss Text enthalten.',
            'max' => 'Das Feld :attribute überschreitet die zulässige Länge oder Anzahl (:max).',
            'in' => 'Die Auswahl für :attribute ist ungültig.',
            'regex' => 'Das Format für :attribute ist ungültig.',
            'numeric' => 'Das Feld :attribute muss eine Zahl enthalten.',
            'integer' => 'Das Feld :attribute muss eine ganze Zahl enthalten.',
            'min' => 'Der Wert für :attribute ist zu klein.',
            'between' => 'Das Feld :attribute muss zwischen :min und :max liegen.',
            'date_format' => 'Das Format für :attribute muss :format entsprechen.',
            'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'url' => 'Bitte geben Sie eine gültige HTTP(S)-Webadresse ein.',
            'decimal' => 'Die Nachkommastellen für :attribute sind ungültig.',
            'array' => 'Die Struktur für :attribute ist ungültig.',
            'distinct' => 'Das Feld :attribute enthält einen doppelten Wert.',
            'boolean' => 'Das Feld :attribute muss Ja oder Nein enthalten.',
        ], $attributes)->validate();
        foreach ($data['bank_accounts'] ?? [] as $index => $account) {
            $data['bank_accounts'][$index]['iban'] = Iban::normalize($account['iban']);
        }
        if (isset($data['shop_area_sqm'])) {
            // Dezimalstring statt Gleitkomma-Rechnung; genau eine Nachkommastelle
            // wie im Referenzfeld, ohne Umrechnung alter Shopgrößen-Freitexte.
            $parts = explode('.', (string) $data['shop_area_sqm']);
            $data['shop_area_sqm'] = (ltrim($parts[0], '0') ?: '0').'.'.($parts[1] ?? '0');
        }
        // Unbekannt bedeutet nicht geschlossen/nein. Bei einem Zeitintervall
        // müssen beide Uhrzeiten vorliegen; identische Zeiten sind kein 24h-Ersatz.
        foreach ($data['opening_hours'] ?? [] as $day => $hours) {
            if (($hours['status'] ?? null) === 'interval' && (empty($hours['open']) || empty($hours['close']) || $hours['open'] === $hours['close'])) {
                throw ValidationException::withMessages(['opening_hours.'.$day => 'Bitte geben Sie für '.self::DAYS[$day].' ein vollständiges Zeitintervall an. Für 24 Stunden wählen Sie den eigenen Status.']);
            }
            if (($hours['status'] ?? null) !== 'interval') {
                $data['opening_hours'][$day] = ['status' => $hours['status'] ?? 'unknown'];
            }
        }
        foreach ([$data, ...($data['competitors'] ?? [])] as $index => $coordinates) {
            if (filled($coordinates['latitude'] ?? null) !== filled($coordinates['longitude'] ?? null)) {
                throw ValidationException::withMessages(['profile' => 'Breiten- und Längengrad müssen gemeinsam angegeben werden.']);
            }
        }
        $seen = [];
        foreach ($data['competitors'] ?? [] as $competitor) {
            $identity = mb_strtolower(preg_replace('/\s+/u', '', implode('|', Arr::only($competitor, ['name', 'street', 'postal_code', 'city']))));
            if (in_array($identity, $seen, true)) {
                throw ValidationException::withMessages(['competitors' => 'Ein Wettbewerbsstandort ist doppelt eingetragen.']);
            }
            $seen[] = $identity;
        }

        return $data;
    }
}
