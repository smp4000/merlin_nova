<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\Station;
use App\Models\User;
use App\Stations\Providers\StationDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Prüft den vollständigen manuellen Ablauf und seine serverseitigen Sicherheitsgrenzen. */
class StationWizardTest extends TestCase
{
    use RefreshDatabase;

    private function setupPartner(PartnerRole $role = PartnerRole::Owner): Partner
    {
        $user = User::factory()->create();
        $partner = Partner::create(['name' => 'Fiktiver Testpartner']);
        $partner->forceFill(['registration_owner_id' => $user->id, 'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(7), 'trial_station_limit' => 2])->save();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $user->id, 'role' => $role]);
        $this->actingAs($user);

        return $partner;
    }

    private function start(Partner $partner): string
    {
        $response = $this->get("/app/partners/{$partner->id}/stations/create")->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        return $query['draft'];
    }

    private function ready(Partner $partner): string
    {
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->post($url, ['draft' => $token, 'name' => 'Teststation', 'brand' => 'Freie Marke'])->assertRedirect();
        $this->post($url, ['draft' => $token, 'street' => 'Beispielweg 1', 'postal_code' => '12345', 'city' => 'Musterort', 'country_code' => 'DE'])->assertRedirect();
        $this->post($url, ['draft' => $token])->assertRedirect();

        return $token;
    }

    public function test_external_selection_is_explicit_and_final_creation_links_source_without_overwriting_corrections(): void
    {
        $partner = $this->setupPartner();
        Http::preventStrayRequests();
        Http::fake([
            '*aktuelle-e10preise*' => Http::response(file_get_contents(base_path('tests/Fixtures/benzinpreis/search.html')), 200, ['Content-Type' => 'text/html']),
            '*preise-tabc123-musterstation' => Http::response(file_get_contents(base_path('tests/Fixtures/benzinpreis/details.html')).'<p class="e-otimes"><em>24 Stunden</em><span>Montag</span></p>', 200, ['Content-Type' => 'text/html']),
        ]);
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345', 'search_city' => 'Musterstadt'])->assertSessionHasNoErrors();
        $this->get($url.'?draft='.$token)->assertOk()->assertSee('Fiktive Musterstation');
        $this->assertDatabaseCount('stations', 0);
        $this->travel(3)->seconds();
        $this->post($url, ['draft' => $token, 'action' => 'source_select', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->get($url.'?draft='.$token)->assertOk()->assertSee('Musterweg 1');
        $this->post($url, ['draft' => $token, 'street' => 'Bewusst korrigiert 2', 'postal_code' => '12345', 'city' => 'Musterstadt', 'country_code' => 'DE'])->assertSessionHasNoErrors();
        $this->post($url, ['draft' => $token, 'import_hours' => 1])->assertSessionHasNoErrors();
        $this->post($url, ['draft' => $token, 'confirmed' => 1])->assertRedirect();
        $station = DB::table('stations')->first();
        $this->assertSame('abc123-musterstation', $station->external_source_id);
        $this->assertSame('Bewusst korrigiert 2', $station->street);
        $this->assertDatabaseHas('station_profiles', ['station_id' => $station->id, 'version' => 1]);
        $this->get(route('stations.details', [$partner->id, $station->id]))->assertOk()->assertSee('1,659 €/l')->assertSee('Nicht verfügbar');
        $this->post(route('stations.source.refresh', [$partner->id, $station->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stations', ['id' => $station->id, 'street' => 'Bewusst korrigiert 2']);
        Http::assertSentCount(2);
    }

    public function test_unselected_expired_or_foreign_search_cannot_trigger_detail_fetch(): void
    {
        $partner = $this->setupPartner();
        Http::fake();
        $token = $this->start($partner);
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'action' => 'source_select', 'source_id' => 'abc-test'])->assertSessionHasErrors('source_id');
        $other = Partner::create(['name' => 'Andere Firma']);
        $this->post("/app/partners/{$other->id}/stations/create", ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345', 'search_city' => 'Musterstadt'])->assertNotFound();
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345', 'search_city' => 'Musterstadt'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_reader_cannot_search_external_source(): void
    {
        $partner = $this->setupPartner(PartnerRole::Viewer);
        Http::fake();
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => (string) Str::uuid(), 'action' => 'source_search', 'search_postal_code' => '12345', 'search_city' => 'Musterstadt'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_existing_station_source_link_is_confirmed_and_keeps_manual_data(): void
    {
        $partner = $this->setupPartner();
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Eigene manuelle Station'])->assertCreated();
        $id = DB::table('stations')->value('id');
        $url = route('stations.source.configure', [$partner->id, $id]);
        Http::preventStrayRequests();
        Http::fake([
            '*aktuelle-e10preise*' => Http::response(file_get_contents(base_path('tests/Fixtures/benzinpreis/search.html')), 200, ['Content-Type' => 'text/html']),
            '*preise-tabc123-musterstation' => Http::response(file_get_contents(base_path('tests/Fixtures/benzinpreis/details.html')), 200, ['Content-Type' => 'text/html']),
        ]);
        $this->get($url)->assertOk()->assertSee('Benzinpreisanbindung');
        Http::assertNothingSent();
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasErrors('source_id');
        $this->post($url, ['action' => 'search', 'postal_code' => '12345', 'city' => 'Musterstadt'])->assertSessionHasNoErrors();
        $this->travel(3)->seconds();
        $this->post($url, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('1,659 €/l');
        $this->assertDatabaseHas('stations', ['id' => $id, 'external_source_id' => null]);
        $this->post($url, ['action' => 'link'])->assertSessionHasErrors('confirmed');
        $this->post($url, ['action' => 'link', 'confirmed' => 1])->assertSessionHasNoErrors()->assertRedirect(route('stations.details', [$partner->id, $id]));
        $this->assertDatabaseHas('stations', ['id' => $id, 'name' => 'Eigene manuelle Station', 'external_source_id' => 'abc123-musterstation']);
        $this->getJson("/app/partners/{$partner->id}/stations")->assertDontSee('external_source_id')->assertDontSee('1.659');
        Http::assertSentCount(2);
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Zweite manuelle Station'])->assertCreated();
        $secondId = DB::table('stations')->max('id');
        $secondUrl = route('stations.source.configure', [$partner->id, $secondId]);
        $this->post($secondUrl, ['action' => 'search', 'postal_code' => '12345', 'city' => 'Musterstadt'])->assertSessionHasNoErrors();
        $this->post($secondUrl, ['action' => 'preview', 'source_id' => 'abc123-musterstation'])->assertSessionHasNoErrors();
        $this->post($secondUrl, ['action' => 'link', 'confirmed' => 1])->assertSessionHasErrors('source_id');
        $this->assertDatabaseHas('stations', ['id' => $secondId, 'external_source_id' => null]);
        Http::assertSentCount(2);
        $other = Partner::create(['name' => 'Fremde Firma']);
        $this->post(route('stations.source.refresh', [$other->id, $id]))->assertNotFound();
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->post(route('stations.source.refresh', [$partner->id, $id]))->assertForbidden();
        $this->get(route('stations.details', [$partner->id, $id]))->assertOk()->assertSee('1,659 €/l')->assertDontSee('>Benzinpreise aktualisieren<', false);
        Http::assertSentCount(2);
    }

    public function test_wizard_provides_brand_suggestions_without_requiring_a_catalog_match(): void
    {
        $partner = $this->setupPartner();
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->get($url.'?draft='.$token)->assertOk()->assertSee('list="station-options-brand"', false)->assertSee('Freie Tankstelle');
        $this->post($url, ['draft' => $token, 'name' => 'Teststation', 'brand' => 'Eigene fiktive Marke'])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_manual_creation_and_repeated_confirmation_are_idempotent_even_at_limit(): void
    {
        $partner = $this->setupPartner(PartnerRole::Manager);
        $partner->forceFill(['trial_station_limit' => 1])->save();
        $token = $this->ready($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->get($url.'?draft='.$token)->assertOk()->assertSee('Zusammenfassung')->assertSee('Beispielweg 1');
        $this->assertDatabaseCount('stations', 0);
        for ($i = 0; $i < 2; $i++) {
            $this->post($url, ['draft' => $token, 'confirmed' => 1])->assertRedirect(route('partner.overview', $partner->id));
        }
        $this->assertDatabaseCount('stations', 1);
        $this->assertDatabaseHas('stations', ['partner_id' => $partner->id, 'street' => 'Beispielweg 1']);
        $this->assertNotEmpty(DB::table('stations')->sole()->short_code);
        $this->get(route('partner.overview', $partner->id))->assertOk()->assertSee('Teststation')->assertSee('Stationslimit erreicht');
    }

    public function test_duplicate_in_a_separate_draft_is_rejected(): void
    {
        $partner = $this->setupPartner();
        $first = $this->ready($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->post($url, ['draft' => $first, 'confirmed' => 1]);
        $second = $this->ready($partner);
        $this->post($url, ['draft' => $second, 'confirmed' => 1])->assertSessionHasErrors('station');
        $this->assertDatabaseCount('stations', 1);
    }

    public function test_expiry_during_wizard_rejects_confirmation_but_preserves_read_access(): void
    {
        $partner = $this->setupPartner();
        $token = $this->ready($partner);
        $partner->forceFill(['trial_ends_at' => now()])->save();
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'confirmed' => 1])->assertForbidden();
        $this->get(route('partner.overview', $partner->id))->assertOk()->assertSee('Test abgelaufen');
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_revoked_membership_and_foreign_partner_cannot_use_draft(): void
    {
        $partner = $this->setupPartner();
        $token = $this->ready($partner);
        $foreign = Partner::create(['name' => 'Unsichtbare Firma']);
        $this->get(route('partner.overview', $foreign->id))->assertNotFound();
        $this->post("/app/partners/{$foreign->id}/stations/create", ['draft' => $token, 'confirmed' => 1])->assertNotFound();
        PartnerMembership::where('partner_id', $partner->id)->update(['is_active' => false]);
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'confirmed' => 1])->assertNotFound();
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_reader_cannot_open_or_submit_wizard(): void
    {
        $partner = $this->setupPartner(PartnerRole::Viewer);
        $this->get(route('partner.overview', $partner->id))->assertOk()->assertDontSee('>Station anlegen<', false);
        $this->get("/app/partners/{$partner->id}/stations/create")->assertForbidden();
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => (string) Str::uuid()])->assertForbidden();
    }

    public function test_validation_back_navigation_and_escaping(): void
    {
        $partner = $this->setupPartner();
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->post($url, ['draft' => $token, 'name' => ' '])->assertSessionHasErrors('name');
        $this->post($url, ['draft' => $token, 'name' => '<script>alert(1)</script>']);
        $this->post($url, ['draft' => $token, 'city' => 'Ort'])->assertSessionHasErrors(['street', 'postal_code', 'country_code']);
        $this->post($url, ['draft' => $token, 'action' => 'back', 'street' => 'Unfertige Anschrift'])->assertRedirect();
        $this->assertSame('Unfertige Anschrift', session('station_drafts.'.$token.'.data.street'));
        $this->get($url.'?draft='.$token)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_unknown_draft_injected_partner_and_missing_confirmation_fail(): void
    {
        $partner = $this->setupPartner();
        $url = "/app/partners/{$partner->id}/stations/create";
        $this->post($url, ['draft' => (string) Str::uuid()])->assertNotFound();
        $token = $this->ready($partner);
        $this->post($url, ['draft' => $token, 'partner_id' => $partner->id])->assertSessionHasErrors('partner_id');
        $this->post($url, ['draft' => $token])->assertSessionHasErrors('confirmed');
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_technical_creation_replay_does_not_consume_another_slot(): void
    {
        $partner = $this->setupPartner();
        $url = "/app/partners/{$partner->id}/stations";
        $this->postJson($url, ['name' => 'Technische Station'])->assertCreated();
        $this->postJson($url, ['name' => 'Technische Station'])->assertCreated();
        $this->assertDatabaseCount('stations', 1);
    }

    public function test_renaming_keeps_duplicate_identity_consistent(): void
    {
        $partner = $this->setupPartner();
        $token = $this->ready($partner);
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'confirmed' => 1]);
        $id = DB::table('stations')->value('id');
        $this->patchJson("/app/partners/{$partner->id}/stations/{$id}", ['name' => 'Neuer Stationsname'])->assertOk();
        $this->assertDatabaseHas('stations', [
            'id' => $id,
            'identity_hash' => Station::identityFor(['name' => 'Neuer Stationsname', 'street' => 'Beispielweg 1', 'postal_code' => '12345', 'city' => 'Musterort', 'country_code' => 'DE']),
        ]);
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'confirmed' => 1])->assertSessionHasErrors('station');
        $this->assertDatabaseCount('stations', 1);
    }

    public function test_another_authorized_partner_still_cannot_use_the_draft(): void
    {
        $first = $this->setupPartner();
        $token = $this->ready($first);
        $second = Partner::create(['name' => 'Zweite Testfirma']);
        PartnerMembership::forceCreate(['partner_id' => $second->id, 'user_id' => auth()->id(), 'role' => PartnerRole::Owner]);
        $this->post("/app/partners/{$second->id}/stations/create", ['draft' => $token, 'confirmed' => 1])->assertNotFound();
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_changed_limit_is_checked_at_final_confirmation(): void
    {
        $partner = $this->setupPartner();
        $token = $this->ready($partner);
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Andere Station'])->assertCreated();
        $partner->forceFill(['trial_station_limit' => 1])->save();
        $this->post("/app/partners/{$partner->id}/stations/create", ['draft' => $token, 'confirmed' => 1])->assertSessionHasErrors('station_limit');
        $this->assertDatabaseCount('stations', 1);
    }

    public function test_postal_only_search_resolves_a_unique_city_without_creating_a_station(): void
    {
        $partner = $this->setupPartner();
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([['address' => ['country_code' => 'de', 'postcode' => '12345', 'city' => 'Fiktiver Musterort']]])]);
        $this->mock(StationDataProvider::class, function ($mock) {
            $mock->shouldReceive('search')->once()->with('12345', 'Fiktiver Musterort')->andReturn(['status' => 'not_found', 'data' => []]);
        });
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345'])->assertRedirect()->assertSessionHasNoErrors();
        $this->get($url.'?draft='.$token)->assertOk()->assertSee('Fiktiver Musterort')->assertSee('Ort für die Suche (optional)');
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_postal_search_does_not_guess_ambiguous_or_unavailable_places(): void
    {
        $partner = $this->setupPartner();
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([['address' => ['country_code' => 'de', 'postcode' => '12345', 'city' => 'Ort A']], ['address' => ['country_code' => 'de', 'postcode' => '12345', 'city' => 'Ort B']]])]);
        $this->mock(StationDataProvider::class, function ($mock) {
            $mock->shouldNotReceive('search');
        });
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345'])->assertSessionHasErrors('search_city');
        $this->travel(3)->seconds();
        Http::fake(['*' => Http::response('', 503)]);
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12346'])->assertSessionHasErrors('search_city');
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '123'])->assertSessionHasErrors('search_postal_code');
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_search_preserves_manual_city_and_readonly_trial_blocks_lookup(): void
    {
        $partner = $this->setupPartner();
        $token = $this->start($partner);
        $url = "/app/partners/{$partner->id}/stations/create";
        Http::preventStrayRequests();
        $this->mock(StationDataProvider::class, function ($mock) {
            $mock->shouldReceive('search')->once()->with('12345', 'Manueller Ort')->andReturn(['status' => 'not_found', 'data' => []]);
        });
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345', 'search_city' => 'Manueller Ort'])->assertSessionHasNoErrors();
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->post($url, ['draft' => $token, 'action' => 'source_search', 'search_postal_code' => '12345'])->assertForbidden();
        Http::assertNothingSent();
    }
}
