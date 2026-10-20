<?php

namespace App\Stations;

use Collator;

/** Deutsche alphabetische Trefferfolge, unabhängig von der Preissortierung der Quelle. */
final class StationCompetitionChoices
{
    public static function sort(array $hits): array
    {
        $collator = new Collator('de_DE');
        $collator->setStrength(Collator::SECONDARY);
        usort($hits, fn ($a, $b) => $collator->compare($a['name'], $b['name'])
            ?: $collator->compare($a['street'] ?? '', $b['street'] ?? '')
            ?: strcmp($a['source_id'], $b['source_id']));

        return $hits;
    }
}
