<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\User;
use App\Stations\Providers\PostalPlaceProvider;
use App\Tenancy\PartnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Ortsvorschläge sind eine begrenzte Stationsfunktion, kein öffentlicher Proxy. */
class PostalPlaceTest extends TestCase
{
    use RefreshDatabase;

    private function partner(): Partner
    {
        $user = User::factory()->create();
        $partner = Partner::create(['name' => 'Fiktiver Ortstest']);
        $partner->forceFill(['registration_owner_id' => $user->id, 'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(7)])->save();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $user->id, 'role' => PartnerRole::Owner]);
        $this->actingAs($user);
        Http::preventStrayRequests();

        return $partner;
    }

    public function test_candidates_are_distinct_and_do_not_expose_postal_centroid_coordinates(): void
    {
        $partner = $this->partner();
        $rows = [];
        foreach (['Musterstadt', 'Zweiter Ort', 'Musterstadt'] as $city) {
            $rows[] = ['lat' => '51.0', 'lon' => '9.0', 'address' => ['country_code' => 'de', 'postcode' => '12345', 'city' => $city]];
        }
        $rows[] = ['address' => ['country_code' => 'at', 'postcode' => '12345', 'city' => 'Falsches Land']];
        Http::fake(['*' => Http::response($rows)]);
        $url = route('partner.postal-place', $partner->id);
        $this->postJson($url, ['postal_code' => '12345', 'country_code' => 'DE'])->assertExactJson(['status' => 'ok', 'cities' => ['Musterstadt', 'Zweiter Ort']]);
        $this->postJson($url, ['postal_code' => '12345', 'country_code' => 'DE'])->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/search?') && $request['postalcode'] === '12345' && ! isset($request['q']));
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_invalid_country_role_foreign_partner_and_expiry_never_send(): void
    {
        $partner = $this->partner();
        Http::fake();
        $url = route('partner.postal-place', $partner->id);
        $this->postJson($url, ['postal_code' => '1234', 'country_code' => 'DE'])->assertUnprocessable();
        $this->postJson($url, ['postal_code' => '12345', 'country_code' => 'AT'])->assertUnprocessable();
        $foreign = Partner::create(['name' => 'Andere Firma']);
        $this->postJson(route('partner.postal-place', $foreign->id), ['postal_code' => '12345', 'country_code' => 'DE'])->assertNotFound();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => PartnerRole::Viewer]);
        $this->postJson($url, ['postal_code' => '12345', 'country_code' => 'DE'])->assertForbidden();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => PartnerRole::Owner]);
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->postJson($url, ['postal_code' => '12345', 'country_code' => 'DE'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_failed_lookup_is_not_a_fake_city_and_global_throttle_applies(): void
    {
        $partner = $this->partner();
        Http::fake(['*' => Http::response('bad json', 200)]);
        $url = route('partner.postal-place', $partner->id);
        $this->postJson($url, ['postal_code' => '12345', 'country_code' => 'DE'])->assertJsonPath('status', 'unavailable');
        $this->postJson($url, ['postal_code' => '54321', 'country_code' => 'DE'])->assertJsonPath('status', 'rate_limited');
        Http::assertSentCount(1);
    }

    public function test_service_can_be_disabled_without_code_change(): void
    {
        $partner = $this->partner();
        app(PartnerContext::class)->set($partner);
        config(['station_sources.postal_endpoint' => '']);
        Http::fake();
        $this->assertSame('unavailable', app(PostalPlaceProvider::class)->lookup('12345')['status']);
        Http::assertNothingSent();
    }
}
