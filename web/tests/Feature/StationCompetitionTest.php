<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationProfile;
use App\Models\User;
use App\Stations\StationMap;
use App\Tenancy\PartnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Nur lokale Strukturbeispiele, keine echten Wettbewerbspreise oder OSM-Kacheln. */
class StationCompetitionTest extends TestCase
{
    use RefreshDatabase;

    private function setupStation(bool $distantCoordinates = false, ?int $reportedRadius = null, bool $batch = false, bool $nearCoordinates = true): array
    {
        $user = User::factory()->create();
        $partner = Partner::create(['name' => 'Fiktiver Kartenpartner']);
        $partner->forceFill(['trial_started_at' => now(), 'trial_ends_at' => now()->addDays(7), 'trial_station_limit' => 3, 'registration_owner_id' => $user->id])->save();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $user->id, 'role' => PartnerRole::Owner]);
        app(PartnerContext::class)->set($partner);
        $station = Station::create(['name' => 'Eigene Fiktivstation', 'postal_code' => '12345', 'city' => 'Musterstadt', 'street' => 'Fiktivweg 1', 'country_code' => 'DE']);
        StationProfile::forceCreate(['station_id' => $station->id, 'updated_by' => $user->id, 'version' => 1, 'data' => ['latitude' => '50.0', 'longitude' => '9.0']]);
        $this->actingAs($user);
        Http::preventStrayRequests();
        Http::fake([
            '*aktuelle-e10preise*' => function ($request) use ($reportedRadius, $batch) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
                $shown = $reportedRadius ?? (int) $query['umkreis'];
                $html = str_replace('+ 5 km', '+ '.$shown.' km', file_get_contents(base_path('tests/Fixtures/benzinpreis/search.html')));

                if ($batch) {
                    $html .= '<div id="station-tdef456-zweitstation"><strong class="isstrong">Zweite Fiktivstation</strong><br>Musterweg 2</div>';
                }

                return Http::response($html, 200, ['Content-Type' => 'text/html']);
            },
            '*preise-tdef456-zweitstation' => Http::response(str_replace(['Fiktive Musterstation', 'Musterweg 1'], ['Zweite Fiktivstation', 'Musterweg 2'], file_get_contents(base_path('tests/Fixtures/benzinpreis/details.html'))).'<meta property="place:location:latitude" content="50.01"><meta property="place:location:longitude" content="9">', 200, ['Content-Type' => 'text/html']),
            '*preise-tabc123-musterstation' => Http::response(file_get_contents(base_path('tests/Fixtures/benzinpreis/details.html')).($distantCoordinates || $nearCoordinates ? '<meta property="place:location:latitude" content="'.($distantCoordinates ? '51.0' : '50.01').'"><meta property="place:location:longitude" content="9.0">' : ''), 200, ['Content-Type' => 'text/html']),
        ]);

        return [$partner, $station, route('stations.competition', [$partner->id, $station->id])];
    }

    /** Simuliert vorliegende Sitzungskoordinaten; eigentliche Detail-/Importprüfung bleibt aktiv. */
    private function primeDistances(Partner $partner, Station $station): void
    {
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $draft = session($key);
        foreach ($draft['result']['data'] ?? [] as $hit) {
            $draft['hit_previews'][$hit['source_id']] = ['data' => ['coordinates' => ['latitude' => 50.01, 'longitude' => 9]]];
        }
        $this->withSession([$key => $draft]);
    }

    public function test_automatic_distance_scan_is_sequential_bound_and_fail_closed(): void
    {
        [$partner, $station, $url] = $this->setupStation(batch: true);
        $this->post($url, ['action' => 'search', 'radius' => 5]);
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $token = session($key.'.distance_scan.token');
        $this->from($url)->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation']])->assertSessionHasErrors('source_ids');
        $this->post($url, ['action' => 'check_distances', 'scan_token' => 'stale'])->assertUnprocessable();
        Http::assertSentCount(1);
        $this->post($url, ['action' => 'pause_distances', 'scan_token' => $token])->assertSessionHasNoErrors();
        $this->assertTrue(session($key.'.distance_scan.paused'));
        Http::assertSentCount(1);
        $this->travel(5)->seconds();
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token])->assertSessionHasNoErrors();
        Http::assertSentCount(2);
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token])->assertSessionHasNoErrors();
        $this->assertNotEmpty(session($key.'.distance_scan.error'));
        $this->assertCount(1, session($key.'.hit_previews'));
        $this->get($url)->assertOk()->assertSee('Die automatische Prüfung wurde angehalten.');
        $this->travel(5)->seconds();
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token])->assertSessionHasNoErrors();
        Http::assertSentCount(3);
        $this->assertCount(2, session($key.'.hit_previews'));
        $this->get($url)->assertOk()->assertSee('2 von 2 Treffern bearbeitet');
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token]);
        Http::assertSentCount(3);
        $this->travel(16)->minutes();
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token])->assertUnprocessable();
    }

    public function test_missing_origin_prevents_scan_requests_and_selection(): void
    {
        [$partner, $station, $url] = $this->setupStation();
        StationProfile::where('station_id', $station->id)->firstOrFail()->forceFill(['data' => []])->save();
        $this->post($url, ['action' => 'search', 'radius' => 5]);
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $this->post($url, ['action' => 'check_distances', 'scan_token' => session($key.'.distance_scan.token')]);
        $this->get($url)->assertOk()->assertSee('Ohne diesen Ausgangspunkt bleiben Treffer gesperrt.');
        $this->from($url)->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation']])->assertSessionHasErrors('source_ids');
        Http::assertSentCount(1);
    }

    public function test_successful_scan_without_coordinates_stays_disabled_without_endless_retry(): void
    {
        [$partner, $station, $url] = $this->setupStation(nearCoordinates: false);
        $this->post($url, ['action' => 'search', 'radius' => 5]);
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $token = session($key.'.distance_scan.token');
        $this->travel(5)->seconds();
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token]);
        $this->get($url)->assertOk()->assertSee('1 von 1 Treffern bearbeitet')->assertSee('nicht auswählbar');
        $this->from($url)->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation']])->assertSessionHasErrors('source_ids');
        $this->post($url, ['action' => 'check_distances', 'scan_token' => $token]);
        Http::assertSentCount(2);
        $this->assertDatabaseCount('station_competitors', 0);
    }

    public function test_hit_distance_is_shown_and_outside_hit_cannot_be_selected(): void
    {
        [$partner, $station, $url] = $this->setupStation(distantCoordinates: true);
        $this->post($url, ['action' => 'search', 'radius' => 5]);
        $this->get($url)->assertOk()->assertSee('Entfernung ungeprüft oder Koordinaten fehlen');
        $this->post($url, ['action' => 'check_hit', 'source_id' => 'def-foreign'])->assertUnprocessable();
        Http::assertSentCount(1);
        $this->travel(3)->seconds();
        $this->post($url, ['action' => 'check_hit', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $page = $this->get($url)->assertOk()->assertSee('111,19 km Luftlinie')->assertSee('Außerhalb des gewählten Radius von 5 km');
        $this->assertMatchesRegularExpression('/name="source_ids\[\]"[^>]*value="abc123-musterstation"[^>]*disabled/', $page->getContent());
        $this->from($url)->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation'], 'distance' => 0])
            ->assertRedirect($url)->assertSessionHasErrors('source_ids');
        $this->assertDatabaseCount('station_competitors', 0);
        Http::assertSentCount(2);
    }

    public function test_known_hit_distance_is_reused_without_network_and_missing_stays_unknown(): void
    {
        [$partner, $station, $url] = $this->setupStation(nearCoordinates: true);
        $this->post($url, ['action' => 'search', 'radius' => 2]);
        $this->travel(3)->seconds();
        $this->post($url, ['action' => 'check_hit', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $page = $this->get($url)->assertOk()->assertSee('1,11 km Luftlinie')->assertDontSee('nicht auswählbar');
        $this->assertDoesNotMatchRegularExpression('/name="source_ids\[\]"[^>]*value="abc123-musterstation"[^>]*disabled/', $page->getContent());
        $this->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation']])->assertSessionHasNoErrors();
        // Neue Suche verwirft die Sitzungsvorschau; der geprüfte Detailcache reicht.
        $this->post($url, ['action' => 'search', 'radius' => 2]);
        $this->get($url)->assertOk()->assertSee('1,11 km Luftlinie');
        Http::assertSentCount(2);
    }

    public function test_batch_checks_only_selected_hits_and_imports_together(): void
    {
        [$partner, $station, $url] = $this->setupStation(batch: true);
        $this->post($url, ['action' => 'search', 'radius' => 5])->assertSessionHasNoErrors();
        $this->primeDistances($partner, $station);
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $this->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation', 'abc123-musterstation']])->assertSessionHasErrors('source_ids.0');
        $this->post($url, ['action' => 'select_batch', 'source_ids' => ['def-foreign']])->assertUnprocessable();
        Http::assertSentCount(1);
        $this->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation', 'def456-zweitstation']])->assertSessionHasNoErrors();
        $token = session($key.'.batch.token');
        $payload = ['action' => 'check_batch', 'batch_token' => $token];
        $this->post($url, ['action' => 'add_batch', 'confirmed' => 1, 'batch_token' => $token])->assertUnprocessable();
        $this->post($url, ['action' => 'check_batch', 'batch_token' => 'stale'])->assertUnprocessable();
        $this->travel(3)->seconds();
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('1 von 2 Standorten geprüft');
        $this->post($url, $payload)->assertSessionHasNoErrors(); // Quell-Cooldown stoppt ohne Datenverlust.
        $this->assertCount(1, session($key.'.batch.previews'));
        $this->travel(3)->seconds();
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('Auswahl zusammen übernehmen (2)');
        $this->post($url, ['action' => 'add_batch', 'batch_token' => $token])->assertSessionHasErrors('confirmed');
        $this->post($url, ['action' => 'add_batch', 'confirmed' => 1, 'batch_token' => $token])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('station_competitors', 2);
        $this->post($url, ['action' => 'add_batch', 'confirmed' => 1, 'batch_token' => $token])->assertUnprocessable();
        Http::assertSentCount(3);
    }

    public function test_batch_rolls_back_every_insert_if_last_entry_is_duplicate(): void
    {
        [$partner, $station, $url] = $this->setupStation(batch: true);
        $this->post($url, ['action' => 'search', 'radius' => 5]);
        $this->primeDistances($partner, $station);
        $this->post($url, ['action' => 'select_batch', 'source_ids' => ['abc123-musterstation', 'def456-zweitstation']]);
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $token = session($key.'.batch.token');
        foreach (range(1, 2) as $number) {
            $this->travel(3)->seconds();
            $this->post($url, ['action' => 'check_batch', 'batch_token' => $token])->assertSessionHasNoErrors();
        }
        app(PartnerContext::class)->set($partner);
        StationCompetitor::forceCreate(['station_id' => $station->id, 'source_id' => 'def456-zweitstation', 'snapshot' => session($key.'.batch.previews.def456-zweitstation'), 'updated_by' => auth()->id()]);
        $this->post($url, ['action' => 'add_batch', 'confirmed' => 1, 'batch_token' => $token])->assertSessionHasErrors('source_id');
        $this->assertDatabaseCount('station_competitors', 1);
        $this->assertDatabaseMissing('station_competitors', ['source_id' => 'abc123-musterstation']);
    }

    public function test_search_preview_confirmation_and_duplicate_protection(): void
    {
        [$partner, $station, $url] = $this->setupStation();
        $this->get($url)->assertOk()->assertSee('2–25 km');
        foreach ([1, 26] as $radius) {
            $this->post($url, ['action' => 'search', 'radius' => $radius])->assertSessionHasErrors('radius');
        }
        Http::assertNothingSent();
        $this->post($url, ['action' => 'search', 'radius' => 25])->assertSessionHasNoErrors();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'umkreis=20'));
        $this->get($url)->assertOk()->assertSee('Tatsächlicher Quellradius: 20 km')->assertSee('nicht vollständig abgedeckt');
        $this->post($url, ['action' => 'preview', 'source_id' => 'def-foreign'])->assertUnprocessable();
        $this->travel(3)->seconds();
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('Auswahl prüfen')->assertSee('1,659')->assertSee('type="checkbox"', false)->assertSee('Als Wettbewerber übernehmen');
        $this->post($url, ['action' => 'add'])->assertSessionHasErrors('confirmed');
        $this->post($url, ['action' => 'add', 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('station_competitors', 1);
        $this->assertDatabaseCount('stations', 1);
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'add', 'confirmed' => '1'])->assertSessionHasErrors('source_id');
        $this->assertStringNotContainsString('Fiktive Musterstation', DB::table('station_competitors')->value('snapshot'));
        $this->get(route('stations.details', [$partner->id, $station->id]))->assertOk()->assertSee('data-station-map', false)->assertSee('Fiktive Musterstation');
        Http::assertSentCount(2);
    }

    public function test_scope_roles_expiry_and_readonly_prevent_external_actions(): void
    {
        [$partner, $station, $url] = $this->setupStation();
        $other = Partner::create(['name' => 'Fremder Kartenpartner']);
        $this->post(route('stations.competition', [$other->id, $station->id]), ['action' => 'search', 'radius' => 5])->assertNotFound();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => 'staff']);
        $this->post($url, ['action' => 'search', 'radius' => 5])->assertForbidden();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => 'owner']);
        $this->post($url, ['action' => 'add', 'confirmed' => '1'])->assertUnprocessable();
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->get($url)->assertOk();
        $this->post($url, ['action' => 'search', 'radius' => 5])->assertForbidden();
        $this->post($url, ['action' => 'check_distances', 'scan_token' => 'foreign'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_map_colors_per_fuel_ties_and_historical_unknown_prices(): void
    {
        [$partner, $station] = $this->setupStation();
        $snap = fn ($name, $price, $last = false) => ['data' => ['name' => $name, 'prices' => ['e5' => $price], 'prices_last_known' => ['e5' => $last], 'coordinates' => ['latitude' => 50.1, 'longitude' => 9.1]], 'fetched_at' => now()->toIso8601String()];
        $points = StationMap::points($station, ['latitude' => 50, 'longitude' => 9], [$snap('A', '1.111'), $snap('B', '1.111'), $snap('C', '1.999'), $snap('D', null), $snap('E', '1.000', true)]);
        $this->assertSame(['blue', 'green', 'green', 'red', 'black', 'black'], array_column(array_column($points, 'colors'), 'e5'));
        $this->assertSame(['blue', 'black', 'black', 'black', 'black', 'black'], array_column(array_column($points, 'colors'), 'e10'));
        $equal = StationMap::points($station, [], [$snap('A', '1.111'), $snap('B', '1.111')]);
        $this->assertSame('black', $equal[1]['colors']['e5']);
        $this->assertNull($equal[0]['latitude']);
        $manualOwn = StationMap::points($station, ['price_super' => '1.234'], []);
        $this->assertSame(1.234, $manualOwn[0]['prices']['e5']);
        $this->assertNull($manualOwn[0]['stamps']['e5']);
        Http::assertNothingSent();
    }

    public function test_radius_distance_and_expired_selection_are_not_silently_accepted(): void
    {
        [$partner, $station, $url] = $this->setupStation();
        $this->assertNull(StationMap::distance([], ['latitude' => 50, 'longitude' => 9]));
        $this->assertEqualsWithDelta(11.119, StationMap::distance(['latitude' => 50, 'longitude' => 9], ['latitude' => 50.1, 'longitude' => 9]), 0.01);
        $this->post($url, ['action' => 'search', 'radius' => 2])->assertSessionHasNoErrors();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'umkreis=3'));
        $this->travel(16)->minutes();
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertUnprocessable();
        $this->post($url, ['action' => 'add', 'confirmed' => '1'])->assertUnprocessable();
        $this->assertDatabaseCount('station_competitors', 0);
        Http::assertSentCount(1);
    }

    public function test_common_limit_includes_manual_competitors(): void
    {
        [$partner,$station,$url] = $this->setupStation();
        $manual = [];
        foreach (range(1, 8) as $number) {
            $manual[] = ['name' => 'Fiktiver Wettbewerber '.$number];
        }
        StationProfile::where('station_id', $station->id)->firstOrFail()->forceFill(['data' => ['competitors' => $manual, 'latitude' => 50, 'longitude' => 9]])->save();
        $this->post($url, ['action' => 'search', 'radius' => 5])->assertSessionHasNoErrors();
        $this->travel(3)->seconds();
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'add', 'confirmed' => '1'])->assertUnprocessable();
        $this->assertDatabaseCount('station_competitors', 0);
    }

    public function test_foreign_station_competitor_cannot_be_refreshed_or_removed(): void
    {
        [$partner,$station,$url] = $this->setupStation();
        $other = Station::create(['name' => 'Andere fiktive Station']);
        $entry = StationCompetitor::forceCreate(['station_id' => $other->id, 'source_id' => 'abc123-musterstation', 'snapshot' => ['data' => ['name' => 'Fremder Wettbewerber']], 'updated_by' => auth()->id()]);
        foreach (['refresh', 'remove'] as $action) {
            $this->post($url, ['action' => $action, 'competitor_id' => $entry->id, 'confirmed' => '1'])->assertNotFound();
        }
        $this->assertDatabaseCount('station_competitors', 1);
        Http::assertNothingSent();
    }

    public function test_batch_radius_error_returns_to_form_preserves_selection_and_rolls_back(): void
    {
        [$partner, $station, $url] = $this->setupStation(distantCoordinates: true, batch: true);
        $this->post($url, ['action' => 'search', 'radius' => 5]);
        $this->primeDistances($partner, $station);
        $ids = ['def456-zweitstation', 'abc123-musterstation'];
        $this->post($url, ['action' => 'select_batch', 'source_ids' => $ids]);
        $key = 'competition_drafts.'.$partner->id.'_'.$station->id;
        $token = session($key.'.batch.token');
        foreach ($ids as $id) {
            $this->travel(3)->seconds();
            $this->post($url, ['action' => 'check_batch', 'batch_token' => $token])->assertSessionHasNoErrors();
        }
        $this->get($url)->assertOk()->assertSee('Außerhalb des gewählten Stationsradius.')->assertSee('111,19 km')
            ->assertSee('class="button primary" disabled>Auswahl zusammen übernehmen (2)', false);
        $this->from($url)->post($url, ['action' => 'add_batch', 'confirmed' => 1, 'batch_token' => $token])
            ->assertRedirect($url)->assertSessionHasErrors('source_ids');
        $this->get($url)->assertOk()->assertSee('Fiktive Musterstation')->assertSee('Außerhalb des gewählten Stationsradius.');
        $this->assertDatabaseCount('station_competitors', 0);
        $this->assertSame($ids, session($key.'.batch.ids'));
        $this->assertCount(2, session($key.'.batch.previews'));
        // Nach Abwahl bleibt der normale Fachablauf verwendbar, ohne Radiusfreigabe.
        $this->post($url, ['action' => 'select_batch', 'source_ids' => ['def456-zweitstation']]);
        $token = session($key.'.batch.token');
        $this->post($url, ['action' => 'check_batch', 'batch_token' => $token]);
        $this->post($url, ['action' => 'add_batch', 'confirmed' => 1, 'batch_token' => $token])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('station_competitors', 1);
    }

    public function test_verified_coordinates_outside_radius_are_rejected(): void
    {
        [$partner,$station,$url] = $this->setupStation(true);
        $this->post($url, ['action' => 'search', 'radius' => 2])->assertSessionHasNoErrors();
        $this->travel(3)->seconds();
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->from($url)->post($url, ['action' => 'add', 'confirmed' => '1'])->assertRedirect($url)->assertSessionHasErrors('source_ids');
        $this->assertDatabaseCount('station_competitors', 0);
    }

    public function test_silently_changed_source_radius_is_rejected(): void
    {
        [$partner,$station,$url] = $this->setupStation(false, 5);
        $this->post($url, ['action' => 'search', 'radius' => 2])->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('abweichenden Umkreis');
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertUnprocessable();
        $this->assertDatabaseCount('station_competitors', 0);
        Http::assertSentCount(1);
    }
}
