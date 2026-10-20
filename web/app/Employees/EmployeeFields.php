<?php

namespace App\Employees;

use App\Rules\Iban;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Gemeinsamer Anzeige-/Validierungskatalog aus P-040/P-041. Die neun roten
 * DATEV-Rahmen sind Exporthinweise, keine pauschalen Einladungs-Pflichtfelder.
 * Kanzleifragen bleiben optional/ungeklärt. Keine erfundenen Beitragsschlüssel,
 * Mindestlöhne oder Entgeltgrenzen; Abrechnungseinordnung liegt beim Arbeitgeber.
 * Strikte Schlüssel-Whitelists verhindern Arbeitgeber-/Rollendaten im Selfservice.
 */
final class EmployeeFields
{
    public const PROFILES = ['general' => 'Allgemeiner Personalbogen', 'minijob' => 'Minijob', 'short_term' => 'Kurzfristige Beschäftigung', 'training' => 'Ausbildung'];

    public const STATES = ['managed' => 'Vom Inhaber gepflegt', 'draft' => 'Vorbereitet', 'invited' => 'Eingeladen', 'in_progress' => 'In Bearbeitung', 'submitted' => 'Zur Prüfung', 'changes_requested' => 'Rückfrage offen', 'approved' => 'Geprüft'];

    /** Feld: deutsches Label, Typ, Pflicht vor Einreichung/Freigabe, Auswahlwerte. */
    private static function field(string $label, string $type = 'text', bool $required = false, array $options = []): array
    {
        return compact('label', 'type', 'required', 'options');
    }

    /** Vorschlag mit freier Ergänzung; kein hartes Enum für historische/fremde Abschlüsse. */
    private static function suggestion(string $label, string $category): array
    {
        return self::field($label, 'suggestion') + ['category' => $category];
    }

    /** Persönliche Tatsachen; Beiträge und Vertragsvereinbarungen fehlen absichtlich. */
    public static function personal(string $profile): array
    {
        $f = self::field(...);
        $choice = ['yes' => 'Ja', 'no' => 'Nein', 'unknown' => 'Noch zu klären'];
        $sections = [
            'person' => ['label' => 'Person & Anschrift', 'fields' => [
                'first_name' => $f('Vorname', required: true), 'last_name' => $f('Familienname', required: true),
                'birth_name' => $f('Abweichender Geburtsname'), 'birth_date' => $f('Geburtsdatum', 'birth', true),
                'street' => $f('Straße', required: true), 'house_number' => $f('Hausnummer'),
                'postal_code' => $f('Postleitzahl'), 'city' => $f('Ort', required: true),
                'country_code' => $f('Anschriftenland (zweistelliger Ländercode)', 'country', true), 'address_addition' => $f('Anschriftenzusatz'),
                'birth_place' => $f('Geburtsort – falls benötigt'), 'birth_country' => $f('Geburtsland – falls benötigt'),
                'gender_code' => $f('Geschlecht – falls benötigt', 'select', false, ['male' => 'Männlich', 'female' => 'Weiblich', 'diverse' => 'Divers', 'undetermined' => 'Unbestimmt']),
                'nationality_code' => self::suggestion('Staatsangehörigkeit – Angabe zur Prüfung', 'nationality'),
                'school_qualification' => self::suggestion('Höchster Schulabschluss', 'school'), 'vocational_qualification' => self::suggestion('Höchste Berufsausbildung', 'vocational'),
                'phone' => $f('Telefon (freiwillig)', 'tel'),
            ]],
            'insurance' => ['label' => 'Steuer & Versicherung', 'fields' => [
                'social_security_number' => $f('Sozialversicherungsnummer (wenn vorhanden)', 'ssn'),
                'tax_id' => $f('Steuer-ID (wenn vorhanden)', 'tax'),
                'health_insurance_type' => $f('Krankenversicherung', 'select', false, ['statutory' => 'Gesetzlich', 'private' => 'Privat', 'unknown' => 'Noch zu klären']),
                'health_insurer_name' => self::suggestion('Krankenkasse / private Versicherung', 'insurer'),
                'last_statutory_health_fund' => self::suggestion('Letzte gesetzliche Krankenkasse (bei privater Versicherung)', 'insurer'),
            ]],
            'bank' => ['label' => 'Gehaltskonto', 'fields' => [
                'payment_method' => $f('Vereinbarter Zahlungsweg', 'select', false, ['transfer' => 'Überweisung', 'other' => 'Anderer Zahlungsweg', 'unknown' => 'Noch zu klären']),
                'salary_bank_name' => $f('Bankname'),
                'salary_iban' => $f('IBAN', 'iban'), 'salary_bic' => $f('BIC (falls benötigt)', 'bic'),
                'salary_account_holder' => $f('Abweichender Kontoinhaber'),
            ]],
            'jobs' => ['label' => 'Weitere Beschäftigungen', 'fields' => [
                'has_other_jobs' => $f('Weitere Beschäftigungen vorhanden?', 'select', true, $choice),
                'employment_priority' => $f('Diese Beschäftigung ist', 'select', false, ['primary' => 'Hauptbeschäftigung', 'secondary' => 'Nebenbeschäftigung', 'unknown' => 'Noch zu klären']),
                'initial_employment_status' => $f('Status bei Beschäftigungsbeginn – bei Minijob/kurzfristig'),
            ]],
            'vwl' => ['label' => 'Vermögenswirksame Leistungen', 'fields' => [
                'has_vwl' => $f('VWL-Vertrag vorhanden?', 'select', false, $choice), 'recipient' => $f('Empfänger'),
                'amount' => $f('Monatlicher Vertragsbetrag in €', 'money'), 'started_on' => $f('Vertragsbeginn', 'date'),
                'contract_number' => $f('VWL-Vertragsnummer'), 'iban' => $f('VWL-IBAN (eigenes Vertragskonto)', 'iban'), 'bic' => $f('VWL-BIC (falls benötigt)', 'bic'),
            ]],
        ];
        if ($profile === 'minijob') {
            $sections['insurance']['fields']['pension_exemption_request'] = $f('RV-Befreiung gewünscht? Kein Antrag durch diese Auswahl.', 'select', false, $choice);
        }
        if (in_array($profile, ['general', 'training'], true)) {
            $sections['children_info'] = ['label' => 'Elterneigenschaft – nur bei angefordertem Nachweis', 'fields' => [
                'parenthood_status' => $f('Elterneigenschaft – soweit für die Prüfung benötigt', 'select', false, $choice),
            ]];
        }

        if (! in_array($profile, ['minijob', 'short_term'], true)) {
            unset($sections['jobs']['fields']['initial_employment_status']);
        }
        if ($profile !== 'general') {
            unset($sections['jobs']['fields']['employment_priority']);
        }

        return $sections;
    }

    /** Wiederholbare Sachverhalte, maximal zwanzig Zeilen als technische Missbrauchsgrenze. */
    public static function repeats(string $side, string $profile): array
    {
        $f = self::field(...);
        if ($side === 'personal') {
            $groups = [
                'other_jobs' => ['label' => 'Weitere Jobs (auch laufende)', 'fields' => [
                    'employer_name' => $f('Arbeitgeber'), 'activity' => $f('Tätigkeit'), 'started_on' => $f('Beginn', 'date'),
                    'ended_on' => $f('Ende (leer bei laufender Beschäftigung)', 'date'), 'weekly_hours' => $f('Wochenstunden (falls bekannt)', 'hours'),
                    'type' => $f('Art der Beschäftigung'),
                ]],
                'previous_jobs' => ['label' => 'Relevante Vorbeschäftigungen im Kalenderjahr', 'fields' => [
                    'started_on' => $f('Von', 'date'), 'ended_on' => $f('Bis', 'date'), 'employer_name' => $f('Arbeitgeber'),
                    'type' => $f('Beschäftigungsart'), 'days' => $f('Beschäftigungstage (falls bekannt)', 'days'),
                ]],
            ];
            if (in_array($profile, ['general', 'training'], true)) {
                $groups['children'] = ['label' => 'Kindernachweise – ausschließlich nach Aufforderung der Personalstelle', 'fields' => [
                    'last_name' => $f('Familienname'), 'first_name' => $f('Vorname'), 'birth_date' => $f('Geburtsdatum', 'birth'),
                ]];
            }

            return $groups;
        }
        $fields = ['label' => $f('Bezeichnung'), 'amount' => $f('Betrag in €', 'money'), 'valid_from' => $f('Betrag gültig ab', 'month'),
            'hourly_rate' => $f('Stundenlohn in €', 'money'), 'hourly_valid_from' => $f('Stundenlohn gültig ab', 'month')];
        if ($profile === 'training') {
            $fields['training_year'] = $f('Ausbildungsjahr', 'year');
        }

        return ['pay_components' => ['label' => $profile === 'training' ? 'Ausbildungsvergütung je Abschnitt' : 'Entgeltbestandteile', 'fields' => $fields]];
    }

    /** Arbeitgeberfelder sind selbst bei manipuliertem Request niemals Selfservice-Felder. */
    public static function employer(string $profile): array
    {
        $f = self::field(...);
        $choice = ['yes' => 'Ja', 'no' => 'Nein', 'unknown' => 'Noch zu klären'];
        $sections = [
            'employment' => ['label' => 'Beschäftigung & Vertrag', 'fields' => [
                'started_on' => $f('Eintrittsdatum', 'date', true), 'first_started_on' => $f('Ersteintritt (falls abweichend)', 'date'),
                'profession_title' => $f('Berufsbezeichnung', required: true), 'occupation_code' => $f('Tätigkeit / BA-Kennziffer – Kanzlei prüfen'),
                'weekly_hours' => $f('Vereinbarte Wochenstunden', 'hours', true), 'annual_vacation_days' => $f('Urlaubsanspruch im Kalenderjahr', 'days', true),
                'vacation_reference_days' => $f('Bezugsarbeitstage pro Woche', 'weekdays'),
                'contract_form' => $f('Vertragsform', 'select', true, ['full_time' => 'Vollzeit', 'part_time' => 'Teilzeit']),
                'fixed_term_type' => $f('Befristung', 'select', true, ['permanent' => 'Unbefristet', 'fixed_date' => 'Kalendermäßig befristet', 'purpose' => 'Zweckbefristet']),
                'fixed_term_end_on' => $f('Befristet bis', 'date'), 'fixed_term_purpose' => $f('Befristungszweck (bei Zweckbefristung)'),
                'contract_concluded_on' => $f('Vertrag abgeschlossen am', 'date'),
                'probation_agreed' => $f('Probezeit vereinbart?', 'select', false, $choice), 'probation_duration' => $f('Probezeitdauer mit Einheit'),
                'cost_center' => $f('Kostenstelle'), 'department_number' => $f('Abteilungsnummer'),
                'training_started_on' => $f('Ausbildungsbeginn', 'date'), 'training_expected_end_on' => $f('Voraussichtliches Ausbildungsende', 'date'),
            ]],
            'payroll' => ['label' => 'Abrechnungseinordnung – keine DATEV-Übertragung', 'fields' => [
                'person_group_code' => $f('Personengruppe – Kanzlei prüfen'), 'tax_class' => $f('Steuerklasse – bestätigter Wert', 'tax_class'),
                'tax_factor' => $f('Faktor – falls anwendbar', 'factor'), 'child_tax_allowance' => $f('Kinderfreibetrag – bestätigter Wert', 'allowance'),
                'health_contribution_code' => $f('KV-Gruppe – bestätigter Code'), 'pension_contribution_code' => $f('RV-Gruppe – bestätigter Code'),
                'unemployment_contribution_code' => $f('AV-Gruppe – bestätigter Code'), 'care_contribution_code' => $f('PV-Gruppe – bestätigter Code'),
                'accident_risk_tariff' => $f('UV-Gefahrtarif'), 'deuev_status_code' => $f('DEÜV-Status – bestätigter Code'),
                'flat_rate_tax_method' => $f('Pauschalierung – bestätigtes Verfahren'),
                'flat_rate_tax_employee_charge' => $f('Abwälzung der Pauschalsteuer – vereinbartes Verfahren'),
                'combined_earnings_assessment' => $f('Entgeltgrenzen-Beurteilung', 'select', false, $choice),
                'disability_status' => $f('Schwerbehindertenstatus – nur zweckgebunden geprüft', 'select', false, $choice),
                'vwl_employer_share' => $f('VWL-AG-Anteil monatlich in € (0 möglich)', 'money'),
            ]],
            'distribution' => ['label' => 'Feste Wochenverteilung (nur falls vereinbart)', 'fields' => []],
        ];
        foreach (['monday' => 'Montag', 'tuesday' => 'Dienstag', 'wednesday' => 'Mittwoch', 'thursday' => 'Donnerstag', 'friday' => 'Freitag', 'saturday' => 'Samstag', 'sunday' => 'Sonntag'] as $key => $label) {
            $sections['distribution']['fields'][$key] = $f($label.' in Stunden', 'daily_hours');
        }

        if ($profile !== 'training') {
            unset($sections['employment']['fields']['training_started_on'], $sections['employment']['fields']['training_expected_end_on']);
        }
        if ($profile !== 'general') {
            unset($sections['employment']['fields']['probation_agreed'], $sections['employment']['fields']['probation_duration']);
        }
        if (! in_array($profile, ['minijob', 'short_term'], true)) {
            unset($sections['payroll']['fields']['flat_rate_tax_method'], $sections['payroll']['fields']['flat_rate_tax_employee_charge'], $sections['payroll']['fields']['combined_earnings_assessment']);
        } else {
            foreach (['health_contribution_code', 'pension_contribution_code', 'unemployment_contribution_code', 'care_contribution_code'] as $key) {
                unset($sections['payroll']['fields'][$key]);
            }
        }
        if ($profile === 'training') {
            unset($sections['distribution']);
        }

        return $sections;
    }

    /**
     * Validiert ausschließlich die bekannte Seite und Fallgruppe. Fehler enthalten
     * Labels, keine Personenwerte. Der Aufrufer rendert Fehler direkt ohne old()-Flash.
     * Leere Wiederholungszeilen verschwinden, nicht zutreffende VWL-Details bleiben leer.
     */
    public static function validate(array $data, string $side, string $profile, bool $complete): array
    {
        $sections = $side === 'personal' ? self::personal($profile) : self::employer($profile);
        $repeats = self::repeats($side, $profile);
        $allowed = [...array_keys($sections), ...array_keys($repeats)];
        $rules = ['data' => ['array:'.implode(',', $allowed)]];
        $names = [];
        foreach ($sections as $section => $definition) {
            $rules['data.'.$section] = ['array:'.implode(',', array_keys($definition['fields']))];
            foreach ($definition['fields'] as $key => $field) {
                $path = 'data.'.$section.'.'.$key;
                $rules[$path] = self::rules($field, $complete);
                $names[$path] = $field['label'];
                if (isset($data[$section][$key]) && is_string($data[$section][$key])) {
                    $data[$section][$key] = self::normalize($data[$section][$key], $field['type']);
                }
            }
        }
        foreach ($repeats as $group => $definition) {
            if (isset($data[$group]) && is_array($data[$group])) {
                $data[$group] = array_values(array_filter($data[$group], fn ($row) => ! is_array($row) || count(array_filter($row, fn ($value) => $value !== null && $value !== '')) > 0));
            }
            $rules['data.'.$group] = ['array', 'list', 'max:20'];
            $rules['data.'.$group.'.*'] = ['array:'.implode(',', array_keys($definition['fields']))];
            foreach ($definition['fields'] as $key => $field) {
                $rules['data.'.$group.'.*.'.$key] = self::rules($field, false);
                $names['data.'.$group.'.*.'.$key] = $field['label'];
                foreach (is_array($data[$group] ?? null) ? $data[$group] : [] as $i => $row) {
                    if (is_array($row) && isset($row[$key]) && is_string($row[$key])) {
                        $data[$group][$i][$key] = self::normalize($row[$key], $field['type']);
                    }
                }
            }
        }
        $validator = Validator::make(['data' => $data], $rules, [
            'required' => ':attribute fehlt noch.', 'array' => 'Die Feldgruppe enthält unerlaubte Angaben.',
            'in' => ':attribute enthält eine unbekannte Auswahl.', 'max' => ':attribute überschreitet die zulässige Länge oder Anzahl.',
            'regex' => ':attribute hat ein ungültiges Format.',
            'numeric' => ':attribute muss eine Zahl sein.', 'integer' => ':attribute muss eine ganze Zahl sein.',
            'decimal' => ':attribute hat zu viele oder ungültige Nachkommastellen.',
            'min' => ':attribute unterschreitet den zulässigen Mindestwert.',
            'string' => ':attribute muss als Text angegeben werden.',
            'date_format' => ':attribute enthält kein gültiges Datum im angezeigten Format.',
            'before_or_equal' => ':attribute darf nicht in der Zukunft liegen.',
        ], $names);
        $validator->after(function ($validator) use ($data, $side, $profile, $complete): void {
            if (! $complete) {
                return;
            }
            $require = function (string $path, string $label) use ($validator, $data): void {
                if (blank(data_get($data, $path))) {
                    $validator->errors()->add('data.'.$path, $label.' fehlt noch.');
                }
            };
            if ($side === 'personal') {
                if (data_get($data, 'bank.payment_method') === 'transfer') {
                    $require('bank.salary_iban', 'IBAN für die vereinbarte Überweisung');
                }
                if (data_get($data, 'jobs.has_other_jobs') === 'yes' && empty($data['other_jobs'])) {
                    $validator->errors()->add('data.other_jobs', 'Bitte mindestens eine weitere Beschäftigung angeben.');
                }
                if (data_get($data, 'jobs.has_other_jobs') === 'no' && ! empty($data['other_jobs'])) {
                    $validator->errors()->add('data.other_jobs', 'Die weiteren Jobs passen nicht zur Auswahl „Nein“. Bitte Angaben oder Auswahl korrigieren.');
                }
                if (data_get($data, 'vwl.has_vwl') !== 'yes' && count(array_filter(is_array($data['vwl'] ?? null) ? $data['vwl'] : [], fn ($value, $key) => $key !== 'has_vwl' && filled($value), ARRAY_FILTER_USE_BOTH)) > 0) {
                    $validator->errors()->add('data.vwl', 'Bitte den vorhandenen VWL-Vertrag bestätigen oder die zusätzlichen VWL-Angaben leeren.');
                }
                if (data_get($data, 'vwl.has_vwl') === 'yes') {
                    foreach (['recipient' => 'VWL-Empfänger', 'amount' => 'VWL-Vertragsbetrag', 'started_on' => 'VWL-Beginn', 'iban' => 'VWL-IBAN'] as $key => $label) {
                        $require('vwl.'.$key, $label);
                    }
                }
            } else {
                if (empty($data['pay_components'])) {
                    $validator->errors()->add('data.pay_components', 'Bitte mindestens einen vereinbarten Entgeltbestandteil erfassen.');
                }
                foreach ([['fixed_term_end_on', 'started_on'], ['training_expected_end_on', 'training_started_on']] as [$end, $start]) {
                    if (filled(data_get($data, 'employment.'.$end)) && filled(data_get($data, 'employment.'.$start)) && data_get($data, 'employment.'.$end) < data_get($data, 'employment.'.$start)) {
                        $validator->errors()->add('data.employment.'.$end, 'Das Ende darf nicht vor dem Beginn liegen.');
                    }
                }
                if (data_get($data, 'employment.fixed_term_type') === 'fixed_date') {
                    $require('employment.fixed_term_end_on', 'Enddatum der Befristung');
                }
                if (data_get($data, 'employment.fixed_term_type') === 'purpose') {
                    $require('employment.fixed_term_purpose', 'Befristungszweck');
                }
                if (data_get($data, 'employment.probation_agreed') === 'yes') {
                    $require('employment.probation_duration', 'Dauer der Probezeit');
                }
                if ($profile === 'training') {
                    $require('employment.training_started_on', 'Ausbildungsbeginn');
                    $require('employment.training_expected_end_on', 'Voraussichtliches Ausbildungsende');
                }
            }
            foreach (['other_jobs', 'previous_jobs', 'children', 'pay_components'] as $group) {
                foreach (is_array($data[$group] ?? null) ? $data[$group] : [] as $i => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $required = match ($group) {
                        'children' => ['first_name', 'last_name', 'birth_date'],
                        'other_jobs' => ['employer_name', 'started_on'],
                        'previous_jobs' => ['started_on', 'ended_on', 'type'],
                        default => ['label'],
                    };
                    foreach ($required as $key) {
                        $require($group.'.'.$i.'.'.$key, 'Vollständige Angaben in Zeile '.($i + 1));
                    }
                    if (! empty($row['ended_on']) && ! empty($row['started_on']) && $row['ended_on'] < $row['started_on']) {
                        $validator->errors()->add('data.'.$group, 'Ein Ende darf nicht vor dem Beginn liegen.');
                    }
                    if ($group === 'pay_components') {
                        if (blank($row['amount'] ?? null) && blank($row['hourly_rate'] ?? null)) {
                            $validator->errors()->add('data.pay_components', 'Ein Entgeltbestandteil benötigt Betrag oder Stundenlohn.');
                        }
                        if (filled($row['amount'] ?? null)) {
                            $require($group.'.'.$i.'.valid_from', 'Gültigkeit des Betrags');
                        }
                        if (filled($row['hourly_rate'] ?? null)) {
                            $require($group.'.'.$i.'.hourly_valid_from', 'Gültigkeit des Stundenlohns');
                        }
                        if ($profile === 'training') {
                            $require($group.'.'.$i.'.training_year', 'Ausbildungsjahr');
                        }
                    }
                }
            }
        });
        $validated = $validator->validate()['data'];
        if ($side === 'personal' && data_get($validated, 'vwl.has_vwl') === 'no') {
            $validated['vwl'] = array_intersect_key($validated['vwl'] ?? [], ['has_vwl' => true]);
        }

        return $validated;
    }

    /** Formale Formate ohne behauptete amtliche Prüfung; Geld bleibt Dezimaltext. */
    private static function rules(array $field, bool $complete): array
    {
        $base = [$complete && $field['required'] ? 'required' : 'nullable'];
        $extra = match ($field['type']) {
            'date' => ['date_format:Y-m-d'], 'birth' => ['date_format:Y-m-d', 'before_or_equal:today'],
            'month' => ['date_format:Y-m'], 'iban' => ['string', new Iban],
            'country' => ['string', 'regex:/^[A-Z]{2}$/D'], 'bic' => ['string', 'regex:/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/D'],
            'tax' => ['string', 'regex:/^[0-9]{11}$/D'], 'ssn' => ['string', 'regex:/^[0-9]{8}[A-Z][0-9]{3}$/D'],
            'select' => ['string', Rule::in(array_keys($field['options']))],
            'money' => ['string', 'regex:/^\d{1,7}(\.\d{1,2})?$/D'],
            'hours' => ['numeric', 'min:0', 'max:168', 'decimal:0,2'], 'daily_hours' => ['numeric', 'min:0', 'max:24', 'decimal:0,2'],
            'days' => ['numeric', 'min:0', 'max:366', 'decimal:0,1'], 'weekdays' => ['integer', 'min:1', 'max:7'],
            'year' => ['integer', 'min:1', 'max:10'], 'tax_class' => ['integer', 'min:1', 'max:6'],
            'factor' => ['string', 'regex:/^0\.\d{1,4}$/D'], 'allowance' => ['numeric', 'min:0', 'max:20', 'decimal:0,1'],
            default => ['string', 'max:160'],
        };

        return [...$base, ...$extra];
    }

    private static function normalize(string $value, string $type): ?string
    {
        $value = trim($value);
        if (in_array($type, ['money', 'hours', 'daily_hours', 'days', 'factor', 'allowance'], true)) {
            $value = str_replace(',', '.', $value);
        }
        if (in_array($type, ['iban', 'bic', 'ssn', 'country'], true)) {
            $value = strtoupper(preg_replace('/\s+/', '', $value));
        }

        return $value === '' ? null : $value;
    }
}
