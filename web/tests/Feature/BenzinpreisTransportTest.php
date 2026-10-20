<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Stations\Providers\StationDataProvider;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Netzwerkverhalten wird mit Fake-HTTP geprüft; die Suite sendet keine echten Anfragen. */
class BenzinpreisTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(PartnerContext::class)->set((new Partner)->forceFill(['id' => 1]));
        Http::preventStrayRequests();
    }

    public function test_real_adapter_uses_fixed_host_and_caches_public_results(): void
    {
        Http::fake(['*' => Http::response(file_get_contents(base_path('tests/Fixtures/benzinpreis/search.html')), 200, ['Content-Type' => 'text/html'])]);
        $provider = app(StationDataProvider::class);
        $this->assertSame('ok', $provider->search('12345', 'Musterstadt')['status']);
        $this->assertTrue($provider->search('12345', 'Musterstadt')['cached']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.benzinpreis-aktuell.de/12345-musterstadt-aktuelle-e10preise?umkreis=5');
    }

    public function test_invalid_inputs_do_not_send_and_cross_partner_global_limit_is_shared(): void
    {
        Http::fake(['*' => Http::response('Fehler', 429)]);
        $provider = app(StationDataProvider::class);
        $this->assertSame('invalid_identity', $provider->details('https://localhost/private')['status']);
        $this->assertSame('invalid_query', $provider->search('1234', 'Ort')['status']);
        Http::assertNothingSent();
        $this->assertSame('rate_limited', $provider->details('abc-test')['status']);
        app(PartnerContext::class)->set((new Partner)->forceFill(['id' => 2]));
        $this->assertSame('rate_limited', $provider->details('def-test')['status']);
        Http::assertSentCount(1);
    }

    public function test_redirect_and_changed_content_are_not_followed_or_cached(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);
        $this->assertSame('unavailable', app(StationDataProvider::class)->details('abc-test')['status']);
        Http::assertSentCount(1);
    }

    public function test_non_html_is_rejected(): void
    {
        Http::fake(['*' => Http::response('{}', 200, ['Content-Type' => 'application/json'])]);
        $this->assertSame('parse_failed', app(StationDataProvider::class)->details('abc-test')['status']);
    }

    public function test_large_response_and_connection_failure_are_bounded_errors(): void
    {
        Http::fake(['*' => Http::response(str_repeat('x', 1_000_001), 200, ['Content-Type' => 'text/html'])]);
        $this->assertSame('parse_failed', app(StationDataProvider::class)->details('abc-test')['status']);
    }

    public function test_timeout_has_no_raw_exception_output(): void
    {
        Http::fake(fn () => throw new ConnectionException('private internal diagnostic'));
        $result = app(StationDataProvider::class)->details('abc-test');
        $this->assertSame('unavailable', $result['status']);
        $this->assertStringNotContainsString('private', json_encode($result));
    }

    public function test_details_handle_navigation_links_without_corrupting_city(): void
    {
        $html = str_replace('Musterstadt</p>', 'Musterstadt<a href="https://example.test">Navigation</a></p>', file_get_contents(base_path('tests/Fixtures/benzinpreis/details.html')));
        Http::fake(['*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);
        $result = app(StationDataProvider::class)->details('abc-test');
        $this->assertSame('ok', $result['status']);
        $this->assertSame('Musterstadt', $result['data']['city']);
        $this->assertSame('1.659', $result['data']['prices']['e5']);
    }
}
