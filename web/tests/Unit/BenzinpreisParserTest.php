<?php

namespace Tests\Unit;

use App\Stations\Providers\BenzinpreisHtmlParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Lokale Strukturtests ersetzen weder Nutzungsfreigabe noch einen echten Anbieter-Vertragstest. */
class BenzinpreisParserTest extends TestCase
{
    public function test_closed_station_structured_prices_require_exact_identity_and_remain_historical(): void
    {
        $parser = new BenzinpreisHtmlParser;
        $base = '<title>Fiktive Station | Preise</title><h2>Wo finde ich die Tankstelle?</h2><p>Testweg 1<br>12345 Musterstadt</p><aside class="ns_p">Diese Tankstelle hat aktuell geschlossen</aside>';
        $product = ['@type' => 'Product', 'name' => 'Super Benzin', 'offers' => ['@type' => 'Offer', 'price' => '1.234', 'priceCurrency' => 'EUR', 'url' => 'https://www.benzinpreis-aktuell.de/preise-tabc123-test']];
        $script = fn ($data) => '<script type="application/ld+json">'.json_encode($data).'</script>';
        $result = $parser->details($base.$script($product), 'abc123-test');
        $this->assertSame('1.234', $result['data']['prices']['e5']);
        $this->assertTrue($result['data']['prices_last_known']['e5']);
        $this->assertNull($result['data']['prices_updated_at']);
        $this->assertNull($parser->details($base.$script($product).$script($product), 'abc123-test')['data']['prices']['e5']);
        foreach (['url' => 'https://example.test/preise-tabc123-test', 'priceCurrency' => 'USD', 'price' => '0.000'] as $key => $value) {
            $bad = $product;
            $bad['offers'][$key] = $value;
            $this->assertNull($parser->details($base.$script($bad), 'abc123-test')['data']['prices']['e5']);
        }
        $product['name'] = ['invalid'];
        $this->assertNull($parser->details($base.$script($product), 'abc123-test')['data']['prices']['e5']);
    }

    public function test_local_search_deduplicates_and_rejects_invalid_identity(): void
    {
        $result = (new BenzinpreisHtmlParser)->search($this->fixture('search'));
        $this->assertSame('ok', $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('abc123-musterstation', $result['data'][0]['source_id']);
    }

    public function test_local_details_preserve_address_and_precise_prices_without_invented_time(): void
    {
        $result = (new BenzinpreisHtmlParser)->details($this->fixture('details'), 'abc123-musterstation');
        $this->assertSame('ok', $result['status']);
        $this->assertSame('Musterstadt', $result['data']['city']);
        $this->assertSame(['e5' => '1.659', 'e10' => null, 'diesel' => '1.559'], $result['data']['prices']);
        $this->assertNull($result['data']['prices_updated_at']);
        $this->assertSame([], $result['data']['opening_hours']);
    }

    public function test_errors_are_not_empty_success_and_aggregate_prices_are_not_station_prices(): void
    {
        $parser = new BenzinpreisHtmlParser;
        $this->assertSame('parse_failed', $parser->search('<html>Geänderte Seite</html>')['status']);
        $this->assertSame('parse_failed', $parser->details('<title>404 nicht gefunden</title>', 'abc-test')['status']);
        $this->assertSame('invalid_identity', $parser->details($this->fixture('details'), 'https://localhost/')['status']);
        $html = preg_replace('/<div class="preis25.*?<\/div>/s', '', $this->fixture('details')).'<table class="stbl"><tr><td>Diesel</td><td>1.999</td></tr></table>';
        $this->assertSame(['e5' => null, 'e10' => null, 'diesel' => null], $parser->details($html, 'abc-test')['data']['prices']);
    }

    public function test_external_entities_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new BenzinpreisHtmlParser)->search('<!DOCTYPE html SYSTEM "http://127.0.0.1/private"><html></html>');
    }

    public function test_ambiguous_and_malformed_prices_remain_unknown(): void
    {
        $html = $this->fixture('details').'<div class="preis25 preis_benzin"><em>9,999</em></div>';
        $html = str_replace('1,55<sup>9</sup>', '-1,559', $html);
        $result = (new BenzinpreisHtmlParser)->details($html, 'abc-test');
        $this->assertNull($result['data']['prices']['e5']);
        $this->assertNull($result['data']['prices']['diesel']);
    }

    public function test_oversized_html_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new BenzinpreisHtmlParser)->search(str_repeat('x', 1_000_001));
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/benzinpreis/'.$name.'.html');
    }

    public function test_supported_seo_title_preserves_identity_checks_and_radius_is_read_from_heading(): void
    {
        $html = str_replace('Fiktive Musterstation | Musterweg 1 | Spritpreise', 'Fiktive Musterstation: Aktuelle Spritpreise & Öffnungszeiten', $this->fixture('details'));
        $parser = new BenzinpreisHtmlParser;
        $result = $parser->details($html, 'abc123-musterstation');
        $this->assertSame('ok', $result['status']);
        $this->assertSame('Fiktive Musterstation', $result['data']['name']);
        $broken = str_replace('12345 Musterstadt', 'Adresse fehlt', $html);
        $this->assertSame('parse_failed', $parser->details($broken, 'abc123-musterstation')['status']);
        $this->assertSame(5, $parser->search($this->fixture('search'))['reported_radius']);
        $this->assertFalse($parser->search($this->fixture('search'))['truncated']);
    }
}
