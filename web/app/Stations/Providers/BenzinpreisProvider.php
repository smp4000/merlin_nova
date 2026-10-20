<?php

namespace App\Stations\Providers;

use App\Tenancy\PartnerContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Nutzerinitiierter Adapter auf ausdrücklichen Auftrag für den lokalen Betrieb.
 *
 * Feste HTTPS-Ziele, keine Redirects/Schlüssel, keine Hintergrundjobs. Öffentliche
 * Ergebnisse werden getrennt von Partnerdaten gecacht. Aktivierung auf Nutzerauftrag
 * ist keine Anbieter-Nutzungsfreigabe. Rechteprüfung erfolgt vor Aufruf im Controller.
 */
final class BenzinpreisProvider implements StationDataProvider
{
    public const string HOST = 'https://www.benzinpreis-aktuell.de/';

    public function __construct(private BenzinpreisHtmlParser $parser, private PartnerContext $context) {}

    /** Expliziter Ort vermeidet zusätzliche Geocoder und verdeckte Suchketten. */
    public function search(string $postalCode, string $city = ''): array
    {
        return $this->searchNearby($postalCode, $city, 5);
    }

    /** Ausdrücklich gewählter Quellumkreis; sequenzielle Distanzprüfung steuert der Controller. */
    public function searchNearby(string $postalCode, string $city, int $radius): array
    {
        $slug = Str::slug(strtr($city, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss']));
        if ($radius < 2 || $radius > 25 || ! preg_match('/^\d{5}$/D', $postalCode) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 100) {
            return $this->failure('invalid_query');
        }

        // Echtdatenprüfung: beliebige Kilometerwerte werden von der Quelle
        // still auf 5 km zurückgesetzt. Nur angebotene Stufen verwenden und
        // den angezeigten Quellradius prüfen. Oberhalb 20 km keine vollständige
        // Abdeckung behaupten; der gewünschte Stationsradius bleibt getrennt.
        $sourceRadius = match (true) {
            $radius <= 3 => 3, $radius <= 5 => 5, $radius <= 10 => 10, default => 20
        };
        $result = $this->fetch($postalCode.'-'.$slug.'-aktuelle-e10preise?umkreis='.$sourceRadius, fn ($html) => $this->parser->search($html), 15);
        if ($result['status'] === 'ok' && ($result['reported_radius'] ?? null) !== $sourceRadius) {
            return $this->failure('radius_mismatch');
        }

        return [...$result, 'requested_radius' => $radius, 'source_radius' => $sourceRadius, 'radius_limited' => $sourceRadius < $radius];
    }

    /**
     * Bereits geprüfte öffentliche Standortkoordinaten ohne externen Abruf lesen.
     * Die Suchseite enthält nur den Ortsmittelpunkt, keine Trefferkoordinaten.
     * Niemals diesen Mittelpunkt ersatzweise einer einzelnen Station zuordnen.
     * Der bestehende Detailcache und seine Ablaufzeit bleiben unverändert.
     */
    public function cachedCoordinates(string $sourceId): array
    {
        if (strlen($sourceId) > 160 || ! preg_match('/^[a-f0-9]{1,40}-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $sourceId)) {
            return [];
        }
        $value = Cache::store(app()->environment('testing') ? 'array' : 'file')->get('bpa:v5:'.hash('sha256', 'preise-t'.$sourceId));

        return ($value['status'] ?? null) === 'ok' && ($value['data']['source_id'] ?? null) === $sourceId ? ($value['data']['coordinates'] ?? []) : [];
    }

    /** Nur begrenzte Quellkennungen; keine frei wählbaren Pfade oder API-URLs. */
    public function details(string $sourceId): array
    {
        if (strlen($sourceId) > 160 || ! preg_match('/^[a-f0-9]{1,40}-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $sourceId)) {
            return $this->failure('invalid_identity');
        }

        return $this->fetch('preise-t'.$sourceId, fn ($html) => $this->parser->details($html, $sourceId), 5);
    }

    /**
     * Atomare gemeinsame Drosselung sowie 30 Abrufe pro Stunde und Partner.
     * TLS bleibt geprüft; keine Retries. Antworten maximal 1 MB im Speicher.
     * Cache enthält nur öffentliche Quellinformationen, keine Nutzer-/Partnerdaten.
     */
    private function fetch(string $path, callable $parse, int $minutes): array
    {
        $cache = Cache::store(app()->environment('testing') ? 'array' : 'file');
        $key = 'bpa:v5:'.hash('sha256', $path);
        if ($value = $cache->get($key)) {
            return [...$value, 'cached' => true];
        }
        $lock = $cache->lock('bpa:request-lock', 20);
        if (! $lock->get()) {
            return $this->failure('rate_limited');
        }
        try {
            $partnerKey = 'bpa:hour:'.$this->context->id().':'.now()->format('YmdH');
            if ($cache->has('bpa:cooldown') || $cache->get($partnerKey, 0) >= 30) {
                return $this->failure('rate_limited');
            }
            $cache->put('bpa:cooldown', true, 2);
            $cache->put($partnerKey, $cache->get($partnerKey, 0) + 1, 3600);
            $deadline = microtime(true) + 12;
            $options = ['allow_redirects' => false, 'stream' => true, 'read_timeout' => 4];
            $ca = base_path('../.local/mail/ca-bundle.pem');
            if (is_file($ca)) {
                $options['verify'] = $ca;
            }
            $response = Http::withOptions($options)->withUserAgent('MerlinNova/1.0 (local; user-initiated station lookup)')
                ->accept('text/html')->connectTimeout(4)->timeout(12)->get(self::HOST.$path);
            if (! $response->successful()) {
                return $this->failure(match ($response->status()) {
                    429 => 'rate_limited', 404 => 'not_found', default => 'unavailable'
                });
            }
            if (! str_contains(strtolower($response->header('Content-Type')), 'text/html')) {
                return $this->failure('parse_failed');
            }
            $body = $response->toPsrResponse()->getBody();
            $html = '';
            while (! $body->eof() && strlen($html) <= 1_000_000) {
                if (microtime(true) > $deadline) {
                    $body->close();

                    return $this->failure('unavailable');
                }
                $html .= $body->read(8192);
            }
            $body->close();
            if (strlen($html) > 1_000_000) {
                return $this->failure('parse_failed');
            }
            $result = [...$parse($html), 'fetched_at' => now()->toIso8601String(), 'source_url' => self::HOST.$path, 'cached' => false];
            if ($result['status'] === 'ok') {
                $cache->put($key, $result, now()->addMinutes($minutes));
            }

            return $result;
        } catch (Throwable) {
            // Keine Rohantworten, Ausnahme-URLs oder Mandantendaten protokollieren.
            return $this->failure('unavailable');
        } finally {
            $lock->release();
        }
    }

    /** Begrenzter Fehlerstatus ohne Rohantworten oder interne technische Details. */
    private function failure(string $status): array
    {
        return ['status' => $status, 'provider' => 'benzinpreis_aktuell', 'data' => []];
    }
}
