<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationProfile;
use App\Models\User;
use App\Tenancy\PartnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Ausschließlich synthetische Quellen und getrennte Testdatenbank, kein echter Abruf. */
class StationPriceRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function setupRun(bool $failSecond = false): array
    {
        $user = User::factory()->create();
        $partner = Partner::create(['name' => 'Fiktiver Preispartner']);
        $partner->forceFill(['registration_owner_id' => $user->id, 'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(7), 'trial_station_limit' => 3])->save();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $user->id, 'role' => PartnerRole::Owner]);
        app(PartnerContext::class)->set($partner);
        $station = Station::create(['name' => 'Fiktive Stammstation', 'street' => 'Testweg 1', 'postal_code' => '12345', 'city' => 'Musterstadt', 'country_code' => 'DE']);
        $station->forceFill(['external_source_id' => 'a111-own'])->save();
        StationProfile::forceCreate(['station_id' => $station->id, 'updated_by' => $user->id, 'version' => 1, 'data' => ['latitude' => 50, 'longitude' => 9, 'price_super' => '1.001']]);
        $records = [];
        foreach (['b222-test', 'c333-test'] as $id) {
            $records[] = StationCompetitor::forceCreate(['station_id' => $station->id, 'source_id' => $id, 'updated_by' => $user->id, 'snapshot' => ['status' => 'ok', 'fetched_at' => now()->toIso8601String(), 'source_url' => 'https://www.benzinpreis-aktuell.de/preise-t'.$id, 'data' => ['source_id' => $id, 'name' => 'Fiktiver Wettbewerber '.$id, 'street' => 'Testweg 2', 'postal_code' => '12345', 'city' => 'Musterstadt', 'coordinates' => ['latitude' => 50.01, 'longitude' => 9], 'prices' => ['e5' => '1.111']]]]);
        }
        $this->actingAs($user);
        Http::preventStrayRequests();
        $html = file_get_contents(base_path('tests/Fixtures/benzinpreis/details.html')).'<meta property="place:location:latitude" content="50.01"><meta property="place:location:longitude" content="9">';
        Http::fake(['*preise-t*' => fn ($request) => Http::response($html, $failSecond && str_ends_with($request->url(), 'b222-test') ? 503 : 200, ['Content-Type' => 'text/html'])]);

        return [$partner, $station, route('stations.prices.refresh-all', [$partner->id, $station->id]), 'price_refresh_runs.'.$partner->id.'_'.$station->id, $records];
    }

    public function test_json_steps_return_prices_without_navigation_or_private_profile_fields(): void
    {
        [$partner, $station, $url, $key] = $this->setupRun();
        app(PartnerContext::class)->set($partner);
        $profile = StationProfile::firstOrFail();
        $profile->forceFill(['data' => [...$profile->data, 'private_test_marker' => 'Nicht an Browser übertragen']])->save();
        $response = $this->postJson($url, ['action' => 'start'])->assertOk()->assertHeaderMissing('Location')
            ->assertJsonStructure(['widget', 'points', 'sourcePrices', 'lastKnown', 'sourceStamp']);
        $this->assertStringNotContainsString('Nicht an Browser übertragen', $response->getContent());
        Http::assertNothingSent();
        $token = session($key.'.token');
        $response = $this->postJson($url, ['action' => 'next', 'run_token' => $token])->assertOk()->assertHeaderMissing('Location');
        $response->assertJsonPath('sourcePrices.e5', '1.659')->assertJsonPath('points.0.prices.e5', 1.659);
        $this->assertStringContainsString('1 von 3 Preisständen aktualisiert.', $response->json('widget'));
        Http::assertSentCount(1);
        $response = $this->postJson($url, ['action' => 'pause', 'run_token' => $token])->assertOk();
        $this->assertStringContainsString('Angehalten.', $response->json('widget'));
        Http::assertSentCount(1);
    }

    public function test_explicit_start_refreshes_own_then_competitors_without_touching_manual_data(): void
    {
        [$partner, $station, $url, $key] = $this->setupRun();
        $details = route('stations.details', [$partner->id, $station->id]);
        $this->get($details)->assertOk()->assertSee('Alle Benzinpreise aktualisieren');
        Http::assertNothingSent();
        $this->post($url, ['action' => 'start'])->assertRedirect($details.'#price-refresh');
        Http::assertNothingSent();
        $token = session($key.'.token');
        foreach (range(1, 3) as $step) {
            $this->travel(5)->seconds();
            $this->post($url, ['action' => 'next', 'run_token' => $token])->assertSessionHasNoErrors();
            $this->assertSame($step, session($key.'.done'));
            Http::assertSentCount($step);
        }
        $this->post($url, ['action' => 'next', 'run_token' => $token]);
        Http::assertSentCount(3);
        $this->get($details)->assertOk()->assertSee('3 von 3 Preisständen aktualisiert.')->assertSee('Fertig.');
        app(PartnerContext::class)->set($partner);
        $this->assertSame('1.659', $station->fresh()->external_source_data['data']['prices']['e5']);
        foreach (StationCompetitor::all() as $record) {
            $this->assertSame('1.659', $record->snapshot['data']['prices']['e5']);
        }
        $this->assertSame('1.001', StationProfile::firstOrFail()->data['price_super']);
        $this->assertDatabaseCount('station_competitors', 2);
    }

    public function test_error_stops_run_without_losing_successful_steps_or_old_prices(): void
    {
        [$partner, $station, $url, $key, $records] = $this->setupRun(true);
        $this->post($url, ['action' => 'start']);
        $token = session($key.'.token');
        $this->post($url, ['action' => 'pause', 'run_token' => $token]);
        $this->assertTrue(session($key.'.paused'));
        Http::assertNothingSent();
        $this->post($url, ['action' => 'next', 'run_token' => $token]);
        $this->travel(5)->seconds();
        $this->post($url, ['action' => 'next', 'run_token' => $token]);
        $this->assertSame(1, session($key.'.done'));
        $this->assertTrue(session($key.'.paused'));
        $this->assertNotEmpty(session($key.'.error'));
        $this->get(route('stations.details', [$partner->id, $station->id]))->assertOk()->assertSee('Aktualisierung fortsetzen');
        Http::assertSentCount(2);
        app(PartnerContext::class)->set($partner);
        $this->assertSame('1.111', $records[0]->fresh()->snapshot['data']['prices']['e5']);
        $this->assertSame('1.659', $station->fresh()->external_source_data['data']['prices']['e5']);
    }

    public function test_replaced_token_and_removed_competitor_stop_before_http(): void
    {
        [$partner, $station, $url, $key, $records] = $this->setupRun();
        $this->post($url, ['action' => 'start']);
        $oldToken = session($key.'.token');
        $this->post($url, ['action' => 'start']);
        $token = session($key.'.token');
        $this->assertNotSame($token, $oldToken);
        $this->from($url)->post($url, ['action' => 'next', 'run_token' => $oldToken])->assertSessionHasErrors('prices');
        Http::assertNothingSent();
        $this->post($url, ['action' => 'next', 'run_token' => $token]);
        app(PartnerContext::class)->set($partner);
        $records[0]->delete();
        $this->travel(5)->seconds();
        $this->post($url, ['action' => 'next', 'run_token' => $token]);
        $this->assertSame(1, session($key.'.done'));
        $this->assertTrue(session($key.'.paused'));
        Http::assertSentCount(1);
    }

    public function test_scope_role_expiry_and_changed_links_prevent_unwanted_requests(): void
    {
        [$partner, $station, $url, $key, $records] = $this->setupRun();
        $other = Partner::create(['name' => 'Fremder Preispartner']);
        $this->post(route('stations.prices.refresh-all', [$other->id, $station->id]), ['action' => 'start'])->assertNotFound();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => 'staff']);
        $this->post($url, ['action' => 'start'])->assertForbidden();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => 'owner']);
        $this->post($url, ['action' => 'start']);
        $token = session($key.'.token');
        $this->from($url)->post($url, ['action' => 'next', 'run_token' => 'foreign'])->assertSessionHasErrors('prices');
        app(PartnerContext::class)->set($partner);
        $station->forceFill(['external_source_id' => 'd444-relinked'])->save();
        $this->post($url, ['action' => 'next', 'run_token' => $token]);
        $this->assertSame(0, session($key.'.done'));
        $this->assertTrue(session($key.'.paused'));
        Http::assertNothingSent();
        $this->travel(16)->minutes();
        $this->from($url)->post($url, ['action' => 'next', 'run_token' => $token])->assertSessionHasErrors('prices');
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->post($url, ['action' => 'start'])->assertForbidden();
        Http::assertNothingSent();
    }
}
