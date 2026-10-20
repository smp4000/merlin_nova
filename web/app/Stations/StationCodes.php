<?php

namespace App\Stations;

use App\Models\Station;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Menschlich lesbarer interner Kurzcode, unabhängig von Gesellschafts-/Kassennummern.
 * Vorschläge werden ausschließlich im autorisierten Partnerkontext gesucht.
 * Schreibende Aufrufer müssen den Partner sperren; der zusammengesetzte Unique-
 * Index bleibt die letzte Datenbankgrenze. Fremde Partnercodes werden nie gelesen.
 * Orts-/Straßenänderung benennt einen bestehenden Code niemals automatisch um.
 */
final class StationCodes
{
    public static function normalize(string $value): string
    {
        return strtoupper(trim($value));
    }

    /** Reiner Ortsvorschlag ohne Datenbankzugriff, mit deutscher Transliteration. */
    public static function base(string $city): string
    {
        $city = Str::ascii(strtr($city, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'AE', 'Ö' => 'OE', 'Ü' => 'UE', 'ß' => 'ss']));
        $words = preg_split('/[^A-Z]+/', strtoupper($city), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_values(array_diff($words, ['AM', 'AN', 'DER', 'DIE', 'DAS', 'IM', 'IN', 'ZU', 'ZUM', 'ZUR', 'BEI']));
        if ($words === []) {
            return 'ST';
        }
        if (count($words) > 1) {
            return implode('', array_map(fn ($word) => $word[0], array_slice($words, 0, 3)));
        }
        $word = $words[0];
        // Konsonant vor der letzten Vokalgruppe: Fulda → FD. Fehlt diese
        // Konstellation, folgt der erste weitere Konsonant: Ulm → UL.
        if (preg_match('/([B-DF-HJ-NP-TV-Z])[AEIOUY]+[^AEIOUY]*$/', substr($word, 1), $match)) {
            return $word[0].$match[1];
        }
        preg_match('/[B-DF-HJ-NP-TV-Z]/', substr($word, 1), $match);

        return $word[0].($match[0] ?? $word[1] ?? 'X');
    }

    /** Unbenutzter Vorschlag; nur beim Anlegen unter Partnersperre reservieren. */
    public static function suggest(string $city, string $street = ''): string
    {
        $taken = Station::whereNotNull('short_code')->pluck('short_code')->all();
        $base = self::base($city);
        if (! in_array($base, $taken, true)) {
            return $base;
        }
        $letter = preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($street)))[0] ?? '';
        if ($letter !== '' && ! in_array($base.'-'.$letter, $taken, true)) {
            return $base.'-'.$letter;
        }
        for ($number = 1; $number <= 9999; $number++) {
            $candidate = $base.'-'.$number;
            if (strlen($candidate) <= 8 && ! in_array($candidate, $taken, true)) {
                return $candidate;
            }
        }
        throw ValidationException::withMessages(['short_code' => 'Für diesen Ort konnte kein freier Kurzcode ermittelt werden.']);
    }
}
