<?php

namespace App\Stations\Providers;

use App\Tenancy\PartnerContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Explizite deutsche PLZ-Ortsabfrage; kein Autocomplete und keine Stationsgeocodierung.
 *
 * Nur PLZ und Land verlassen Nova. Mittelpunktkoordinaten werden bewusst verworfen:
 * ein PLZ-Gebiet ist kein Stationsstandort. Endpunkt nur aus Betreiberkonfiguration,
 * feste Queryparameter, kein Redirect, kein personenbezogener Suchtext. Öffentliche
 * Ergebnisse mit getrenntem Cache; lokale Anwendungsgrenzen gelten für alle Nutzer.
 */
final class PostalPlaceProvider
{
    public function __construct(private PartnerContext $context) {}

    /** Mehrdeutige Orte bleiben eine Liste zur Auswahl; Fehler verändern keine Eingabefelder. */
    public function lookup(string $postalCode): array
    {
        if (! preg_match('/^\d{5}$/D', $postalCode)) {
            return ['status' => 'invalid_query', 'cities' => []];
        }
        $endpoint = config('station_sources.postal_endpoint');
        $url = is_string($endpoint) ? parse_url($endpoint) : false;
        if (! $url || ($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
            return ['status' => 'unavailable', 'cities' => []];
        }
        $cache = Cache::store(app()->environment('testing') ? 'array' : 'file');
        $key = 'postal:v1:'.hash('sha256', $endpoint.':'.$postalCode);
        if (($result = $cache->get($key)) !== null) {
            return $result;
        }
        $lock = $cache->lock('postal:request-lock', 20);
        if (! $lock->get()) {
            return ['status' => 'rate_limited', 'cities' => []];
        }
        try {
            $hour = 'postal:hour:'.$this->context->id().':'.now()->format('YmdH');
            if ($cache->has('postal:cooldown') || $cache->get($hour, 0) >= 30) {
                return ['status' => 'rate_limited', 'cities' => []];
            }
            $cache->put('postal:cooldown', true, 2);
            $cache->put($hour, $cache->get($hour, 0) + 1, 3600);
            $options = ['allow_redirects' => false, 'stream' => true, 'read_timeout' => 4];
            $ca = base_path('../.local/mail/ca-bundle.pem');
            if (is_file($ca)) {
                $options['verify'] = $ca;
            }
            $deadline = microtime(true) + 12;
            $response = Http::withOptions($options)->withUserAgent('MerlinNova/1.0 (local station setup; user-triggered postal lookup)')->acceptJson()->connectTimeout(4)->timeout(12)
                ->get($endpoint, ['format' => 'jsonv2', 'postalcode' => $postalCode, 'countrycodes' => 'de', 'addressdetails' => 1, 'limit' => 10]);
            if (! $response->successful()) {
                return ['status' => $response->status() === 429 ? 'rate_limited' : 'unavailable', 'cities' => []];
            }
            $body = $response->toPsrResponse()->getBody();
            $json = '';
            while (! $body->eof() && strlen($json) <= 200_000 && microtime(true) <= $deadline) {
                $json .= $body->read(4096);
            }
            $complete = $body->eof();
            $body->close();
            if (! $complete || strlen($json) > 200_000) {
                return ['status' => 'unavailable', 'cities' => []];
            }
            $rows = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($rows) || ! array_is_list($rows)) {
                return ['status' => 'unavailable', 'cities' => []];
            }
            $cities = [];
            foreach (array_slice($rows, 0, 10) as $row) {
                $address = $row['address'] ?? null;
                if (! is_array($address) || ($address['country_code'] ?? '') !== 'de' || ($address['postcode'] ?? '') !== $postalCode) {
                    continue;
                }
                $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;
                if (is_string($city) && trim($city) !== '' && mb_strlen($city) <= 100) {
                    $cities[] = trim($city);
                }
            }
            $cities = array_values(array_unique($cities));
            $result = ['status' => $cities ? 'ok' : 'not_found', 'cities' => $cities];
            $cache->put($key, $result, now()->addDays(7));

            return $result;
        } catch (Throwable) {
            return ['status' => 'unavailable', 'cities' => []];
        } finally {
            $lock->release();
        }
    }
}
