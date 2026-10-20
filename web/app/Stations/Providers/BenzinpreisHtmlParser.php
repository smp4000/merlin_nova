<?php

namespace App\Stations\Providers;

use App\Stations\StationFields;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;

/**
 * Nova-eigener Parser anhand der gelieferten und öffentlich geprüften HTML-Struktur.
 *
 * Kein HTTP, keine Dateiöffnung, kein Cache und keine Datenbank. Fremdes HTML ist
 * unzuverlässige Eingabe: Größe begrenzen, externe XML-Auflösung verhindern und nur
 * erlaubte Textfelder zurückgeben. Unbekannte Struktur ist ein Fehler, kein leerer
 * Sucherfolg. HTML-Struktur ist kein stabiler API-Vertrag; Importrechte prüft der Controller.
 */
final class BenzinpreisHtmlParser
{
    private const string SOURCE = 'benzinpreis_aktuell';

    /** Extrahiert erkennbare Treffer ohne Duplikate; beliebige URLs werden nie übernommen. */
    public function search(string $html): array
    {
        $xpath = $this->document($html);
        $hits = [];
        foreach ($xpath->query('//div[starts-with(@id,"station-t")]') as $node) {
            $id = substr($node->getAttribute('id'), 9);
            if (! $this->validId($id) || isset($hits[$id])) {
                continue;
            }
            $name = $this->text($xpath, './/strong[contains(concat(" ",normalize-space(@class)," ")," isstrong ")]', $node);
            if ($name !== '') {
                $street = $this->text($xpath, './/strong[contains(@class,"isstrong")]/following-sibling::text()[1]', $node);
                $hits[$id] = ['source_id' => $id, 'name' => mb_substr($name, 0, 255), 'street' => mb_substr($street, 0, 255)];
            }
        }

        $result = $this->result($hits === [] ? 'parse_failed' : 'ok', array_slice(array_values($hits), 0, 50));
        $heading = $this->text($xpath, '//h1');
        $result['reported_radius'] = preg_match('/\+\s*(\d+)\s*km\b/u', $heading, $radius) ? (int) $radius[1] : null;
        $result['truncated'] = count($hits) > 50;

        return $result;
    }

    /**
     * Prüft Identität und Pflichtanschrift; Preise nur aus der Hauptanzeige.
     *
     * Vergleichstabellen/regionale Durchschnittswerte dienen absichtlich nicht als
     * Ersatz. Lieferzeit wird nicht aus „heute“ erfunden; Abrufzeit setzt später der
     * Adapter. Mehrdeutige/mehrteilige Öffnungszeiten werden sichtbar ausgespart;
     * Feiertagsangaben dürfen niemals auf einen normalen Wochentag abgebildet werden.
     */
    public function details(string $html, string $sourceId): array
    {
        if (! $this->validId($sourceId)) {
            return $this->result('invalid_identity', []);
        }
        $xpath = $this->document($html);
        $name = trim(explode('|', $this->text($xpath, '//title'))[0]);
        // Einige echte Detailseiten verwenden statt des bisherigen | einen
        // eindeutig erkannten SEO-Suffix. Nur diesen entfernen; Pflichtanschrift
        // und kanonische Quellenkennung bleiben unabhängige Sicherheitsprüfungen.
        $name = trim(preg_replace('/:\s*Aktuelle\s+Spritpreise(?:\s*&\s*Öffnungszeiten)?$/iu', '', $name));
        $address = $xpath->query('//h2[normalize-space(.)="Wo finde ich die Tankstelle?"]/following-sibling::p[1]')->item(0);
        $parts = [];
        if ($address) {
            $line = '';
            foreach ($address->childNodes as $child) {
                if ($child->nodeName === 'br') {
                    $parts[] = trim($line);
                    $line = '';
                } elseif ($child->nodeName !== 'a') {
                    $line .= $child->textContent;
                }
            }
            $parts[] = trim($line);
        }
        if ($name === '' || preg_match('/(?:nicht gefunden|404|spritpreise)/iu', $name) || count($parts) !== 2 || $parts[0] === '' || ! preg_match('/^(\d{5})\s+(.+)$/u', $parts[1], $city)) {
            return $this->result('parse_failed', []);
        }
        $prices = ['e5' => null, 'e10' => null, 'diesel' => null];
        foreach (['benzin' => 'e5', 'e10' => 'e10', 'diesel' => 'diesel'] as $class => $fuel) {
            $nodes = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," preis25 ") and contains(concat(" ",normalize-space(@class)," ")," preis_'.$class.' ")]/em');
            // Mehrdeutige Anzeigen werden nicht willkürlich auf einen Wert reduziert.
            if ($nodes->length === 1) {
                $raw = trim($nodes->item(0)->textContent);
                if (preg_match('/^([0-9]{1,2})[.,]([0-9]{3})$/', $raw, $match) && (int) ($match[1].$match[2]) > 0) {
                    $prices[$fuel] = ((string) (int) $match[1]).'.'.$match[2];
                }
            }
        }

        // Eine abweichende kanonische Quellenkennung darf keine falsche Verknüpfung erzeugen.
        $canonical = $xpath->query('//link[@rel="canonical"]')->item(0)?->getAttribute('href');
        if (($canonical && $canonical !== BenzinpreisProvider::HOST.'preise-t'.$sourceId) || mb_strlen($name) > 255 || mb_strlen($parts[0]) > 255 || mb_strlen($city[2]) > 100) {
            return $this->result('invalid_identity', []);
        }

        // Bei geschlossener Station fehlen sichtbare Hauptpreise. Strukturierte
        // Produktangebote sind nur bei exakt dieser Stations-URL und EUR zulässig;
        // sie sind zuletzt bekannte Angaben ohne belegten Preisänderungszeitpunkt.
        $lastKnown = [];
        if (str_contains($this->text($xpath, '//aside[contains(@class,"ns_p")]'), 'Tankstelle hat aktuell geschlossen')) {
            $offers = [];
            foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
                $product = json_decode($script->textContent, true);
                if (! is_array($product) || ($product['@type'] ?? null) !== 'Product' || ! is_string($product['name'] ?? null) || ! is_array($product['offers'] ?? null)) {
                    continue;
                }
                $fuel = ['Super Benzin' => 'e5', 'Super E10' => 'e10', 'Diesel' => 'diesel'][$product['name'] ?? ''] ?? null;
                $offer = $product['offers'] ?? [];
                if ($fuel && ($offer['@type'] ?? null) === 'Offer' && ($offer['url'] ?? null) === BenzinpreisProvider::HOST.'preise-t'.$sourceId && ($offer['priceCurrency'] ?? null) === 'EUR' && is_scalar($offer['price'] ?? null) && preg_match('/^[0-9]{1,2}\.[0-9]{3}$/D', (string) $offer['price']) && (float) $offer['price'] > 0) {
                    $offers[$fuel][] = (string) $offer['price'];
                }
            }
            foreach ($offers as $fuel => $values) {
                if ($prices[$fuel] === null && count($values) === 1) {
                    $prices[$fuel] = $values[0];
                    $lastKnown[$fuel] = true;
                }
            }
        }

        [$hours, $notes] = $this->hours($xpath);

        return $this->result('ok', ['source_id' => $sourceId, 'name' => $name, 'street' => $parts[0], 'postal_code' => $city[1], 'city' => trim($city[2]), 'prices' => $prices, 'prices_last_known' => $lastKnown, 'prices_updated_at' => null, 'opening_hours' => $hours, 'hours_notes' => $notes, 'coordinates' => $this->coordinates($xpath)]);
    }

    /** Nur ein vollständiges, eindeutiges Koordinatenpaar innerhalb geografischer Grenzen. */
    private function coordinates(DOMXPath $xpath): ?array
    {
        $result = [];
        foreach (['latitude' => 90, 'longitude' => 180] as $key => $limit) {
            $nodes = $xpath->query('//meta[@property="place:location:'.$key.'"]');
            if ($nodes->length !== 1) {
                return null;
            }
            $value = trim($nodes->item(0)->getAttribute('content'));
            if (! preg_match('/^-?\d{1,3}(?:\.\d{1,12})?$/D', $value) || abs((float) $value) > $limit) {
                return null;
            }
            $result[$key] = rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        return $result;
    }

    /**
     * Wochentage nur bei eindeutiger einzelner Angabe. 00:00–00:00 bedeutet nicht
     * automatisch 24h. Mehrere Intervalle oder doppelte Tageszeilen bleiben als
     * Hinweis sichtbar und sind nicht importierbar. Übernachtintervalle sind erlaubt.
     */
    private function hours(DOMXPath $xpath): array
    {
        $days = ['montag' => 'monday', 'dienstag' => 'tuesday', 'mittwoch' => 'wednesday', 'donnerstag' => 'thursday', 'freitag' => 'friday', 'samstag' => 'saturday', 'sonntag' => 'sunday'];
        $hours = $notes = $seen = [];
        foreach ($xpath->query('//p[contains(concat(" ", normalize-space(@class), " "), " e-otimes ")]') as $node) {
            $label = mb_strtolower($this->text($xpath, './span', $node));
            $raw = mb_strtolower($this->text($xpath, './em', $node));
            $day = $days[$label] ?? null;
            if ($day === null) {
                if (in_array($label, ['feiertag', 'feiertage'], true)) {
                    $notes[] = 'Feiertage: '.mb_substr($raw, 0, 150).' (separat prüfen; keine Wochenübernahme)';
                }

                continue;
            }
            if (isset($seen[$day])) {
                unset($hours[$day]);
                $notes[] = 'Mehrere Angaben für '.StationFields::DAYS[$day].' – bitte manuell prüfen.';

                continue;
            }
            $seen[$day] = true;
            if (preg_match('/^24\s*(?:h|stunden)(?:\s+geöffnet)?$/u', $raw)) {
                $hours[$day] = ['status' => 'all_day'];
            } elseif ($raw === 'geschlossen') {
                $hours[$day] = ['status' => 'closed'];
            } elseif (preg_match('/^(\d{1,2}):([0-5]\d)\s*(?:bis|–|-|—)\s*(\d{1,2}):([0-5]\d)(?:\s*uhr)?$/u', $raw, $match) && (int) $match[1] < 24 && ((int) $match[3] < 24 || ((int) $match[3] === 24 && $match[4] === '00'))) {
                $open = sprintf('%02d:%s', $match[1], $match[2]);
                // 24:00 als ausdrücklich benanntes Tagesende ist eindeutig;
                // 00:00–00:00 ohne diese Angabe bleibt dagegen unbekannt.
                $close = sprintf('%02d:%s', (int) $match[3] % 24, $match[4]);
                if ($open === '00:00' && (int) $match[3] === 24) {
                    $hours[$day] = ['status' => 'all_day'];
                } elseif ($open !== $close) {
                    $hours[$day] = ['status' => 'interval', 'open' => $open, 'close' => $close];
                } else {
                    $notes[] = StationFields::DAYS[$day].': gleiche Uhrzeiten – Bedeutung bitte manuell prüfen.';
                }
            } elseif ($raw !== '' && $raw !== 'keine angaben') {
                $notes[] = StationFields::DAYS[$day].': '.mb_substr($raw, 0, 150).' (nicht eindeutig übernehmbar)';
            }
        }

        return [$hours, array_values(array_unique($notes))];
    }

    /** Begrenzt Quellkennungen; ein Quellenpfad darf nicht aus Benutzereingaben entstehen. */
    private function validId(string $id): bool
    {
        return strlen($id) <= 160 && preg_match('/^[a-f0-9]{1,40}-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $id) === 1;
    }

    /** HTML wird nur im Speicher geladen; DTDs sind abgelehnt, Netzwerkauflösung gesperrt. */
    private function document(string $html): DOMXPath
    {
        if (strlen($html) > 1_000_000 || preg_match('/<!ENTITY|<!DOCTYPE[^>]*(?:SYSTEM|PUBLIC)/i', $html)) {
            throw new InvalidArgumentException('Unzulässige oder zu große lokale HTML-Eingabe.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            return new DOMXPath($document);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** Nur normalisierter Text wird ausgegeben; kein fremdes HTML für die Oberfläche. */
    private function text(DOMXPath $xpath, string $query, ?\DOMNode $context = null): string
    {
        return trim(preg_replace('/\s+/u', ' ', $xpath->query($query, $context)->item(0)?->textContent ?? '') ?? '');
    }

    /** Ergebnisse bleiben eindeutig als externe, unbestätigte Vorschau gekennzeichnet. */
    private function result(string $status, array $data): array
    {
        return ['status' => $status, 'provider' => self::SOURCE, 'data' => $data];
    }
}
