<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Lokale formale IBAN-Prüfung: allgemeines Format, Länderlänge und Modulo 97.
 * Quelle für Längen: SWIFT IBAN Registry. Keine Konto-/Inhaberbestätigung,
 * Deckungsprüfung oder Zusage der SEPA-Erreichbarkeit. Die Prüfziffer wird
 * stellenweise berechnet, damit auch lange IBANs keine Integer überlaufen.
 * Nationale BBAN-/BLZ-Sonderregeln (außer deutschem Ziffernformat) und ein
 * darauf beruhender verlässlicher IBAN-Rechner sind noch nicht enthalten.
 */
final class Iban implements ValidationRule
{
    public const LENGTHS = [
        'AL' => 28, 'AD' => 24, 'AT' => 20, 'BE' => 16, 'BG' => 22, 'CH' => 21,
        'CY' => 28, 'CZ' => 24, 'DE' => 22, 'DK' => 18, 'EE' => 20, 'ES' => 24,
        'FI' => 18, 'FR' => 27, 'GB' => 22, 'GI' => 23, 'GR' => 27, 'HR' => 21,
        'HU' => 28, 'IE' => 22, 'IS' => 26, 'IT' => 27, 'LI' => 21, 'LT' => 20,
        'LU' => 20, 'LV' => 21, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19,
        'MT' => 31, 'NL' => 18, 'NO' => 15, 'PL' => 28, 'PT' => 25, 'RO' => 24,
        'RS' => 22, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27, 'VA' => 22,
    ];

    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', $value));
    }

    public static function valid(string $value): bool
    {
        $iban = self::normalize($value);
        if (! preg_match('/\A[A-Z]{2}[0-9]{2}[A-Z0-9]+\z/', $iban)
            || strlen($iban) !== (self::LENGTHS[substr($iban, 0, 2)] ?? 0)
            || (str_starts_with($iban, 'DE') && ! ctype_digit(substr($iban, 2)))) {
            return false;
        }
        $remainder = 0;
        foreach (str_split(substr($iban, 4).substr($iban, 0, 4)) as $character) {
            $digits = ctype_alpha($character) ? (string) (ord($character) - 55) : $character;
            foreach (str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder === 1;
    }

    /**
     * Nur formaler deutscher Standardvorschlag. Institutsindividuelle Umrechnungen
     * werden hier ausdrücklich nicht behauptet; vor Verwendung Bankunterlagen prüfen.
     * Kontonummer bleibt Text, damit führende Nullen nicht verloren gehen.
     */
    public static function germanProposal(string $blz, string $account): ?string
    {
        if (! preg_match('/^\d{8}$/D', $blz) || ! preg_match('/^\d{1,10}$/D', $account)) {
            return null;
        }
        $bban = $blz.str_pad($account, 10, '0', STR_PAD_LEFT);
        $remainder = 0;
        foreach (str_split($bban.'131400') as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return 'DE'.str_pad((string) (98 - $remainder), 2, '0', STR_PAD_LEFT).$bban;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::valid($value)) {
            $fail('Bitte eine IBAN mit unterstützter Länderlänge und gültiger Prüfziffer eingeben.');
        }
    }
}
