<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\Station;
use App\Models\StationProfile;
use App\Models\User;
use App\Stations\StationSourceImport;
use App\Tenancy\PartnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Übernahme aus gespeichertem Snapshot ohne Netzwerk und ohne Ersetzen manueller Werte. */
class StationSourceImportTest extends TestCase
{
    use RefreshDatabase;

    private function station(array $data = []): array
    {
        $user = User::factory()->create();
        $partner = Partner::create(['name' => 'Fiktiver Importpartner']);
        $partner->forceFill(['registration_owner_id' => $user->id, 'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(7), 'trial_station_limit' => 3])->save();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $user->id, 'role' => PartnerRole::Owner]);
        app(PartnerContext::class)->set($partner);
        $source = ['data' => ['name' => 'Fiktive Quelle', 'street' => 'Quellweg 1', 'postal_code' => '12345', 'city' => 'Musterort', 'source_id' => 'abc-test', 'prices' => [], 'opening_hours' => ['monday' => ['status' => 'all_day'], 'tuesday' => ['status' => 'interval', 'open' => '22:00', 'close' => '06:00']], 'coordinates' => ['latitude' => '50.123', 'longitude' => '9.123']], 'fetched_at' => now()->toIso8601String(), 'source_url' => 'https://www.benzinpreis-aktuell.de/preise-tabc-test'];
        $station = Station::create(['name' => 'Manuelle Station']);
        $station->forceFill(['external_source_id' => 'abc-test', 'external_source_data' => $source])->save();
        StationProfile::forceCreate(['station_id' => $station->id, 'updated_by' => $user->id, 'version' => 1, 'data' => $data]);
        $this->actingAs($user);
        Http::fake();

        return [$partner, $station, ['version' => 1, 'source_snapshot' => StationSourceImport::snapshot($station), 'confirmed' => 1, 'import_hours' => 1, 'import_coordinates' => 1]];
    }

    public function test_confirmation_is_required_and_manual_days_and_coordinates_survive(): void
    {
        [$partner, $station, $input] = $this->station(['latitude' => '0', 'longitude' => '0', 'opening_hours' => ['monday' => ['status' => 'closed']], 'tax_id' => 'Fiktiver Altwert']);
        $url = route('stations.source.import', [$partner->id, $station->id]);
        $this->get($url)->assertOk()->assertSee('Montag')->assertSee('bis Folgetag');
        $this->post($url, [...$input, 'confirmed' => 0])->assertSessionHasErrors('confirmed');
        $this->post($url, $input)->assertSessionHasNoErrors();
        $data = json_decode(decrypt(DB::table('station_profiles')->where('station_id', $station->id)->value('data'), false), true);
        $this->assertSame(['status' => 'closed'], $data['opening_hours']['monday']);
        $this->assertSame('22:00', $data['opening_hours']['tuesday']['open']);
        $this->assertSame('0', $data['latitude']);
        $this->assertSame('Fiktiver Altwert', $data['tax_id']);
        Http::assertNothingSent();
    }

    public function test_free_fields_are_imported_and_parallel_changes_reject_stale_form(): void
    {
        [$partner, $station, $input] = $this->station();
        $url = route('stations.source.import', [$partner->id, $station->id]);
        $this->post($url, $input)->assertSessionHasNoErrors();
        $this->post($url, $input)->assertSessionHasErrors('source');
        app(PartnerContext::class)->set($partner);
        $data = StationProfile::where('station_id', $station->id)->first()->data;
        $this->assertSame('50.123', $data['latitude']);
        $this->assertSame('9.123', $data['longitude']);
        $this->assertSame('all_day', $data['opening_hours']['monday']['status']);
    }

    public function test_changed_source_expiry_and_foreign_partner_are_rejected_before_mutation(): void
    {
        [$partner, $station, $input] = $this->station();
        $url = route('stations.source.import', [$partner->id, $station->id]);
        $this->post($url, [...$input, 'source_snapshot' => str_repeat('0', 64)])->assertSessionHasErrors('source');
        $foreign = Partner::create(['name' => 'Andere Firma']);
        $this->post(route('stations.source.import', [$foreign->id, $station->id]), $input)->assertNotFound();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => PartnerRole::Viewer]);
        $this->post($url, $input)->assertForbidden();
        PartnerMembership::where('partner_id', $partner->id)->update(['role' => PartnerRole::Owner]);
        $partner->forceFill(['trial_ends_at' => now()->subMinute()])->save();
        $this->post($url, $input)->assertForbidden();
        $this->assertSame(1, DB::table('station_profiles')->where('station_id', $station->id)->value('version'));
        Http::assertNothingSent();
    }

    public function test_partial_manual_coordinate_pair_and_partial_manual_day_are_not_repaired_by_import(): void
    {
        [$partner, $station, $input] = $this->station(['latitude' => '0', 'opening_hours' => ['monday' => ['status' => 'unknown', 'open' => '08:00']]]);
        $url = route('stations.source.import', [$partner->id, $station->id]);
        $this->post($url, $input)->assertSessionHasNoErrors();
        app(PartnerContext::class)->set($partner);
        $data = StationProfile::where('station_id', $station->id)->first()->data;
        $this->assertSame('0', $data['latitude']);
        $this->assertArrayNotHasKey('longitude', $data);
        $this->assertSame('08:00', $data['opening_hours']['monday']['open']);
        $this->assertSame('unknown', $data['opening_hours']['monday']['status']);
    }
}
