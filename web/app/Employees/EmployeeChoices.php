<?php

namespace App\Employees;

use App\Models\EmployeeChoice;
use ResourceBundle;

/**
 * Vorschläge statt ungeprüfter DATEV-Schlüssel. Eigene Texte bleiben immer erlaubt.
 * Gemeinsame Ergänzungen stammen nur aus dem explizit gepflegten Partnerkatalog,
 * niemals automatisch aus Mitarbeiterentwürfen oder fremden Mandanten.
 */
final class EmployeeChoices
{
    public const CATEGORIES = ['nationality' => 'Staatsangehörigkeit / Länder', 'school' => 'Schulabschlüsse',
        'vocational' => 'Berufsausbildungen', 'insurer' => 'Krankenkassen / Versicherungen'];

    public static function base(string $category): array
    {
        if ($category === 'nationality') {
            $regions = ResourceBundle::create('de', 'ICUDATA-region')->get('Countries');
            $names = ['Staatenlos', 'Ungeklärt'];
            foreach ($regions as $code => $name) {
                if (strlen($code) === 2 && ! in_array($code, ['EU', 'EZ', 'QO', 'UN', 'ZZ', 'AC', 'CP', 'DG', 'EA', 'IC', 'TA'], true)) {
                    $names[] = (string) $name;
                }
            }
        } elseif ($category === 'school') {
            $names = ['Ohne Schulabschluss', 'Haupt-/Volksschulabschluss', 'Mittlere Reife oder gleichwertiger Abschluss', 'Abitur / Fachabitur', 'Abschluss unbekannt'];
        } elseif ($category === 'vocational') {
            $names = ['Ohne beruflichen Ausbildungsabschluss', 'Abschluss einer anerkannten Berufsausbildung',
                'Meister-/Techniker- oder gleichwertiger Fachschulabschluss', 'Bachelor', 'Diplom / Magister / Master / Staatsexamen', 'Promotion', 'Abschluss unbekannt'];
        } elseif ($category === 'insurer') {
            $names = json_decode(file_get_contents(resource_path('reference/health-insurers.json')), true, 512, JSON_THROW_ON_ERROR)['names'];
        } else {
            return [];
        }
        $names = array_values(array_unique($names));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /** Aufruf nur im bereits autorisierten Partnerkontext, auch beim Selfservice. */
    public static function forPartner(): array
    {
        $result = [];
        $custom = EmployeeChoice::orderBy('label')->get();
        foreach (array_keys(self::CATEGORIES) as $category) {
            $result[$category] = array_values(array_unique(array_merge(self::base($category), $custom->where('category', $category)->pluck('label')->all())));
        }

        return $result;
    }
}
