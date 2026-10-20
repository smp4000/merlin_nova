<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\Station;
use App\Models\StationProfile;
use App\Models\User;
use App\Stations\StationCatalogs;
use App\Stations\StationProfiles;
use App\Tenancy\PartnerContext;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Prüft echte MySQL-Persistenz, vertrauliche Feldsicht und fremde Bild-/Profilzugriffe. */
class StationDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(PartnerRole $role = PartnerRole::Owner): array
    {
        $actor = User::factory()->create();
        $partner = Partner::forceCreate(['name' => 'Fiktiver Stammdatenpartner', 'registration_owner_id' => $actor->id, 'trial_started_at' => now(), 'trial_ends_at' => now()->addWeek(), 'trial_station_limit' => 3]);
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $actor->id, 'role' => $role]);
        app(PartnerContext::class)->set($partner);
        $station = Station::create(['name' => 'Fiktive Station', 'brand' => 'Freie Marke', 'street' => 'Beispielweg 12', 'postal_code' => '12345', 'city' => 'Musterort', 'country_code' => 'DE']);
        app(PartnerContext::class)->clear();
        $this->actingAs($actor);

        return [$actor, $partner, $station];
    }

    private function url(Partner $partner, Station $station): string
    {
        return "/app/partners/{$partner->id}/stations/{$station->id}/details";
    }

    private function payload(Station $station, array $profile = [], int $version = 0): array
    {
        return [...$station->only(['name', 'brand', 'street', 'postal_code', 'city', 'country_code']), 'version' => $version, 'snapshot' => StationProfiles::snapshot($station), 'profile' => $profile];
    }

    public function test_vat_area_and_company_numbers_are_separate_from_the_internal_code(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $data = ['vat_id' => 'FIKTIV-UST-0001', 'shop_area_sqm' => '85,5', 'external_number' => '0000123456',
            'station_number_fuel' => '00004', 'station_number_shop' => '00005', 'shop_operation_number' => '00006'];
        $payload = [...$this->payload($station, $data), 'short_code' => 'fd-p'];
        $this->patch($this->url($partner, $station), $payload)->assertSessionHasNoErrors()->assertRedirect();
        app(PartnerContext::class)->set($partner);
        $stored = StationProfile::sole()->data;
        $this->assertSame('85.5', $stored['shop_area_sqm']);
        $this->assertSame('FIKTIV-UST-0001', $stored['vat_id']);
        $this->assertSame('0000123456', $stored['external_number']);
        $this->assertSame('00004', $stored['station_number_fuel']);
        $this->assertSame('FD-P', Station::findOrFail($station->id)->short_code);
        app(PartnerContext::class)->clear();
        $this->get($this->url($partner, $station))->assertOk()->assertSee('USt-IdNr.')->assertSee('Verkaufsfläche (m²)')->assertSee('Stationsnummer der Gesellschaft')->assertSee('Interner Kurzcode');
        $this->getJson("/app/partners/{$partner->id}/stations")->assertDontSee('FIKTIV-UST')->assertDontSee('0000123456');
    }

    public function test_legacy_texts_are_not_automatically_reinterpreted_and_survive_other_updates(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $this->patch($this->url($partner, $station), $this->payload($station, ['tax_id' => 'Alte freie Kennung', 'shop_size' => '85 m² samt Lager', 'station_number' => '000-ALT']))->assertSessionHasNoErrors();
        $this->patch($this->url($partner, $station), $this->payload($station, ['shop_area_sqm' => '0'], 1))->assertSessionHasNoErrors();
        app(PartnerContext::class)->set($partner);
        $data = StationProfile::sole()->data;
        $this->assertSame('85 m² samt Lager', $data['shop_size']);
        $this->assertSame('000-ALT', $data['station_number']);
        $this->assertSame('Alte freie Kennung', $data['tax_id']);
        $this->assertSame('0.0', $data['shop_area_sqm']);
        $this->assertArrayNotHasKey('vat_id', $data);
        $this->assertArrayNotHasKey('external_number', $data);
        app(PartnerContext::class)->clear();
    }

    public function test_number_area_and_code_boundaries_are_enforced_without_partial_writes(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        foreach ([['shop_area_sqm' => '-1'], ['shop_area_sqm' => '85.55'], ['shop_area_sqm' => '1000000'],
            ['vat_id' => str_repeat('X', 21)], ['external_number' => str_repeat('0', 21)], ['station_number_fuel' => str_repeat('0', 21)]] as $invalid) {
            $this->patchJson($this->url($partner, $station), $this->payload($station, $invalid))->assertUnprocessable();
        }
        foreach (['BAD CODE', 'ABCDEFGHI', 'A--B', ''] as $code) {
            $this->patchJson($this->url($partner, $station), [...$this->payload($station), 'short_code' => $code])->assertUnprocessable();
        }
        $this->assertDatabaseCount('station_profiles', 0);
    }

    public function test_short_code_is_unique_per_partner_and_cannot_be_taken_from_a_foreign_partner(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        app(PartnerContext::class)->set($partner);
        $other = new Station(['name' => 'Zweite Teststation']);
        $other->forceFill(['short_code' => 'FD'])->save();
        app(PartnerContext::class)->clear();
        $this->patchJson($this->url($partner, $station), [...$this->payload($station), 'short_code' => 'fd'])->assertUnprocessable()->assertJsonValidationErrors('short_code');
        [$foreignActor,$foreignPartner,$foreignStation] = $this->fixture();
        $this->patch($this->url($foreignPartner, $foreignStation), [...$this->payload($foreignStation), 'short_code' => 'FD'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('station_profiles', 1);
        app(PartnerContext::class)->set($partner);
        $this->expectException(UniqueConstraintViolationException::class);
        $station->forceFill(['short_code' => 'FD'])->save();
    }

    public function test_vat_is_owner_only_and_not_flashed_after_validation_errors(): void
    {
        [$owner,$partner,$station] = $this->fixture();
        $this->from($this->url($partner, $station))->patch($this->url($partner, $station), $this->payload($station, ['vat_id' => str_repeat('X', 21)]))
            ->assertSessionHasErrors('vat_id')->assertSessionMissing('_old_input.profile.vat_id');
        $manager = User::factory()->create();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $manager->id, 'role' => PartnerRole::Manager]);
        $this->actingAs($manager)->get($this->url($partner, $station))->assertOk()->assertDontSee('name="profile[vat_id]"', false);
        $this->patchJson($this->url($partner, $station), $this->payload($station, ['vat_id' => 'FIKTIV']))->assertForbidden();
    }

    public function test_catalog_codes_roundtrip_and_custom_suggestions_remain_station_local(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $data = ['ownership_type' => 'COFO', 'academic_title' => 'Prof. Dr.', 'shop_type' => 'rewe_to_go',
            'shop_class' => 'A', 'assortment_level' => 'premium', 'nielsen_area' => '3a',
            'car_wash_brand' => 'Fiktive Eigenmarke', 'car_wash_type' => 'Eigener Testtyp',
            'first_opening_ok' => '2020-01-02', 'first_opening_dk' => '2021-03-04',
            'bank_accounts' => [['iban' => 'DE89370400440532013000', 'account_type' => 'Eigenes Stationskonto']]];
        $this->patch($this->url($partner, $station), $this->payload($station, $data))->assertSessionHasNoErrors()->assertRedirect();
        app(PartnerContext::class)->set($partner);
        $stored = StationProfile::sole()->data;
        $this->assertSame('COFO', $stored['ownership_type']);
        $this->assertSame('rewe_to_go', $stored['shop_type']);
        $this->assertSame('3a', $stored['nielsen_area']);
        $this->assertSame('Fiktive Eigenmarke', $stored['car_wash_brand']);
        $this->assertSame('Eigenes Stationskonto', $stored['bank_accounts'][0]['account_type']);
        $this->assertSame('2020-01-02', $stored['first_opening_ok']);
        app(PartnerContext::class)->clear();
        $this->get($this->url($partner, $station))->assertOk()->assertSee('REWE To Go')
            ->assertSee('station-options-car_wash_brand')->assertSee('station-options-account_type')
            ->assertSee('Erstöffnung OK')->assertSee('Erstöffnung DK');
        $this->assertNotContains('Fiktive Eigenmarke', StationCatalogs::suggestions()['car_wash_brand']);
    }

    public function test_unknown_new_catalog_values_are_rejected_atomically(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $this->patchJson($this->url($partner, $station), $this->payload($station, ['shop_type' => 'invented', 'ownership_type' => 'unknown']))
            ->assertUnprocessable()->assertJsonValidationErrors(['shop_type', 'ownership_type']);
        $this->assertDatabaseCount('station_profiles', 0);
    }

    public function test_existing_custom_catalog_value_is_preserved_only_at_its_own_station(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        app(PartnerContext::class)->set($partner);
        StationProfile::forceCreate(['station_id' => $station->id, 'updated_by' => $actor->id,
            'version' => 1, 'data' => ['shop_type' => 'Historischer Shop', 'ownership_type' => 'Eigener Altwert']]);
        $other = Station::create(['name' => 'Andere Station']);
        app(PartnerContext::class)->clear();
        $this->get($this->url($partner, $station))->assertOk()->assertSee('Historischer Shop · bisheriger Wert');
        $this->patch($this->url($partner, $station), $this->payload($station, ['shop_type' => 'Historischer Shop', 'ownership_type' => 'Eigener Altwert'], 1))->assertSessionHasNoErrors();
        $this->patchJson($this->url($partner, $other), $this->payload($other, ['shop_type' => 'Historischer Shop']))->assertUnprocessable();
        $this->patch($this->url($partner, $station), $this->payload($station, ['shop_type' => 'bistro'], 2))->assertSessionHasNoErrors();
        $this->patchJson($this->url($partner, $station), $this->payload($station, ['shop_type' => 'Historischer Shop'], 3))->assertUnprocessable();
    }

    public function test_full_manual_profile_is_saved_encrypted_and_rendered_in_the_defined_tabs(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $data = ['contact_first_name' => 'Fiktiver', 'contact_last_name' => 'Testkontakt', 'phone' => '+49 000 123', 'station_number' => '000012', 'has_camera' => '0', 'shop_size' => '85 m²', 'timezone' => 'Europe/Berlin', 'latitude' => '0', 'longitude' => '0', 'price_e10' => '1.859', 'services' => ['bakery'], 'opening_hours' => ['monday' => ['status' => 'interval', 'open' => '22:00', 'close' => '06:00']], 'bank_accounts' => [['iban' => 'DE89 3704 0044 0532 0130 00', 'description' => 'Öffentliches Testbeispiel']]];
        $this->patch($this->url($partner, $station), $this->payload($station, $data))->assertRedirect($this->url($partner, $station));
        $raw = DB::table('station_profiles')->sole();
        $this->assertStringNotContainsString('Testkontakt', $raw->data);
        $this->assertStringNotContainsString('DE89370400440532013000', $raw->data);
        $this->assertSame(1, $raw->version);
        app(PartnerContext::class)->set($partner);
        $profile = StationProfile::sole();
        $this->assertSame('000012', $profile->data['station_number']);
        $this->assertSame('DE89370400440532013000', $profile->data['bank_accounts'][0]['iban']);
        $this->assertArrayNotHasKey('data', $profile->toArray());
        app(PartnerContext::class)->clear();
        $this->get($this->url($partner, $station))->assertOk()->assertSee('Adresse &amp; Kontakt', false)->assertSee('Öffnungszeiten')->assertSee('Testkontakt')->assertSee('000012')->assertSee('Finanzen');
        $this->getJson("/app/partners/{$partner->id}/stations")->assertOk()->assertDontSee('Testkontakt')->assertDontSee('DE89370400440532013000');
        $this->get(route('partner.overview', $partner->id))->assertOk()->assertDontSee('Testkontakt');
    }

    public function test_manager_cannot_read_or_overwrite_finance_and_keeps_existing_accounts(): void
    {
        [$owner, $partner, $station] = $this->fixture();
        $this->patch($this->url($partner, $station), $this->payload($station, ['bank_accounts' => [['iban' => 'DE89370400440532013000']]]))->assertRedirect();
        $manager = User::factory()->create();
        PartnerMembership::forceCreate(['partner_id' => $partner->id, 'user_id' => $manager->id, 'role' => PartnerRole::Manager]);
        $this->actingAs($manager)->get($this->url($partner, $station))->assertOk()->assertDontSee('DE89370400440532013000')->assertDontSee('name="profile[bank_accounts]', false);
        $this->patchJson($this->url($partner, $station), $this->payload($station, ['tax_id' => 'Angriff'], 1))->assertForbidden();
        $this->patch($this->url($partner, $station), $this->payload($station, ['region' => 'Testregion'], 1))->assertRedirect();
        app(PartnerContext::class)->set($partner);
        $this->assertSame('DE89370400440532013000', StationProfile::sole()->data['bank_accounts'][0]['iban']);
        $this->assertSame('Testregion', StationProfile::sole()->data['region']);
        app(PartnerContext::class)->clear();
    }

    public function test_inactive_test_is_read_only_and_role_revocation_blocks_access(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $partner->forceFill(['trial_ends_at' => now()])->save();
        $this->get($this->url($partner, $station))->assertOk()->assertSee('Nur lesender Zugriff')->assertDontSee('Stationsdaten speichern');
        $this->patchJson($this->url($partner, $station), $this->payload($station))->assertForbidden();
        PartnerMembership::where('partner_id', $partner->id)->update(['is_active' => false]);
        $this->get($this->url($partner, $station))->assertNotFound();
    }

    public function test_staff_and_readers_have_no_extended_detail_access(): void
    {
        foreach ([PartnerRole::Viewer, PartnerRole::Staff] as $role) {
            [$actor, $partner, $station] = $this->fixture($role);
            $this->get($this->url($partner, $station))->assertForbidden();
            $this->patchJson($this->url($partner, $station), $this->payload($station))->assertForbidden();
        }
    }

    public function test_foreign_station_and_superadmin_without_membership_are_rejected(): void
    {
        [$first, $own, $station] = $this->fixture();
        [$other, $foreign, $hidden] = $this->fixture();
        $this->actingAs($first)->get($this->url($own, $hidden))->assertNotFound();
        $this->patchJson($this->url($own, $hidden), $this->payload($station))->assertNotFound();
        $this->actingAs(User::factory()->create(['is_super_admin' => true]))->get($this->url($own, $station))->assertNotFound();
    }

    public function test_stale_profile_and_parallel_name_changes_do_not_overwrite_data(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $stale = $this->payload($station, ['region' => 'Veraltet']);
        $this->patch($this->url($partner, $station), $this->payload($station, ['region' => 'Aktuell']))->assertRedirect();
        $this->patchJson($this->url($partner, $station), $stale)->assertUnprocessable()->assertJsonValidationErrors('version');
        $current = $this->payload($station, ['region' => 'Aktuell'], 1);
        $this->patchJson("/app/partners/{$partner->id}/stations/{$station->id}", ['name' => 'Zwischenzeitlich geändert'])->assertOk();
        $this->patchJson($this->url($partner, $station), $current)->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseHas('stations', ['id' => $station->id, 'name' => 'Zwischenzeitlich geändert']);
    }

    #[DataProvider('invalidProfiles')]
    public function test_invalid_and_privileged_profile_values_are_rejected(array $profile): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $this->patchJson($this->url($partner, $station), $this->payload($station, $profile))->assertUnprocessable();
        $this->assertDatabaseCount('station_profiles', 0);
    }

    public static function invalidProfiles(): array
    {
        return [
            'fremder Partner' => [['partner_id' => 99]], 'Gerätetoken' => [['device_setup_token' => 'Angriff']],
            'unbekannte Settings' => [['settings' => ['role' => 'owner']]],
            'Koordinatenbereich' => [['latitude' => '91', 'longitude' => '1']],
            'halbe Koordinaten' => [['latitude' => '0']], 'Datum' => [['shop_setup_date' => '2026-02-31']],
            'Preis' => [['price_e10' => '1.1234']], 'negative Anzahl' => [['num_pumps' => -1]],
            'URL' => [['website' => 'javascript:alert(1)']], 'E-Mail' => [['email' => 'keine-adresse']],
            'unvollständige Zeiten' => [['opening_hours' => ['monday' => ['status' => 'interval', 'open' => '12:00']]]],
            'unbekannte Leistung' => [['services' => ['admin']]],
            'IBAN' => [['bank_accounts' => [['iban' => 'DE00000000000000000000']]]],
            'zu viele Wettbewerber' => [['competitors' => array_fill(0, 9, ['name' => 'Test'])]],
            'falsche Struktur' => [['opening_hours' => ['monday' => ['status' => ['Angriff']]]]],
        ];
    }

    public function test_failed_bank_submission_never_flashes_bank_or_tax_values(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $this->from($this->url($partner, $station))->patch($this->url($partner, $station), $this->payload($station, ['tax_id' => 'Privater Testwert', 'bank_accounts' => [['iban' => 'DE00000000000000000000']]]))->assertSessionHasErrors();
        $this->assertNull(session()->getOldInput('profile.bank_accounts'));
        $this->assertNull(session()->getOldInput('profile.tax_id'));
        $this->get($this->url($partner, $station))->assertOk()->assertDontSee('DE00000000000000000000')->assertDontSee('Privater Testwert');
    }

    public function test_malformed_html_input_is_safely_redisplayed(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $this->from($this->url($partner, $station))->patch($this->url($partner, $station), $this->payload($station, ['phone' => ['nested' => 'Angriff'], 'fuel_types' => [['nested' => 'Angriff']]]))->assertSessionHasErrors();
        $this->get($this->url($partner, $station))->assertOk();
    }

    public function test_private_images_are_reencoded_and_block_cross_partner_and_role_access(): void
    {
        Storage::fake('local');
        [$actor, $partner, $station] = $this->fixture();
        $this->patch($this->url($partner, $station), [...$this->payload($station), 'photos' => [UploadedFile::fake()->image('synthetisch.png', 20, 20)]])->assertRedirect();
        app(PartnerContext::class)->set($partner);
        $item = StationProfile::sole()->data['media'][0];
        app(PartnerContext::class)->clear();
        Storage::disk('local')->assertExists($item['path']);
        $this->assertStringNotContainsString('synthetisch', $item['path']);
        $url = "/app/partners/{$partner->id}/stations/{$station->id}/images/{$item['id']}";
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        [$other, $foreign, $hidden] = $this->fixture();
        $this->get("/app/partners/{$foreign->id}/stations/{$hidden->id}/images/{$item['id']}")->assertNotFound();
        $this->actingAs($actor)->get("/app/partners/{$partner->id}/stations/{$hidden->id}/images/{$item['id']}")->assertNotFound();
        PartnerMembership::where('user_id', $actor->id)->update(['role' => PartnerRole::Viewer]);
        $this->get($url)->assertForbidden();
    }

    public function test_non_image_uploads_and_untrusted_file_paths_are_rejected(): void
    {
        Storage::fake('local');
        [$actor, $partner, $station] = $this->fixture();
        $this->patchJson($this->url($partner, $station), [...$this->payload($station), 'logo' => UploadedFile::fake()->create('angriff.svg', 1, 'image/svg+xml')])->assertUnprocessable();
        $this->patchJson($this->url($partner, $station), $this->payload($station, ['media' => [['path' => '../../.env']]]))->assertUnprocessable();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_separate_house_number_remains_visible_and_preserves_duplicate_identity(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $payload = $this->payload($station, ['house_number' => '12']);
        $payload['street'] = 'Fiktiver Beispielweg';
        $this->patch($this->url($partner, $station), $payload)->assertRedirect();
        $this->assertDatabaseHas('stations', ['id' => $station->id, 'street' => 'Fiktiver Beispielweg', 'house_number' => '12']);
        $this->get(route('partner.overview', $partner->id))->assertSee('Fiktiver Beispielweg 12');
        $this->assertSame(Station::identityFor(['name' => 'Test', 'street' => 'Beispielweg 12', 'postal_code' => '12345', 'city' => 'Ort', 'country_code' => 'DE']), Station::identityFor(['name' => 'Test', 'street' => 'Beispielweg', 'house_number' => '12', 'postal_code' => '12345', 'city' => 'Ort', 'country_code' => 'DE']));
    }

    public function test_rejected_photo_count_cleans_only_new_uploads_and_keeps_profile_version(): void
    {
        Storage::fake('local');
        [$actor, $partner, $station] = $this->fixture();
        $photos = [];
        for ($i = 0; $i < 10; $i++) {
            $photos[] = UploadedFile::fake()->image('test'.$i.'.png', 10, 10);
        }
        $this->patch($this->url($partner, $station), [...$this->payload($station), 'photos' => $photos])->assertRedirect();
        $this->patchJson($this->url($partner, $station), [...$this->payload($station, [], 1), 'photos' => [UploadedFile::fake()->image('elftes.png', 10, 10)]])->assertUnprocessable();
        $this->assertCount(10, Storage::disk('local')->allFiles());
        $this->assertSame(1, DB::table('station_profiles')->sole()->version);
    }

    public function test_old_input_cannot_bleed_into_another_authorized_station(): void
    {
        [$actor, $partner, $station] = $this->fixture();
        $this->from($this->url($partner, $station))->patch($this->url($partner, $station), $this->payload($station, ['contact_first_name' => 'Nur für Station eins', 'latitude' => '91']))->assertSessionHasErrors();
        app(PartnerContext::class)->set($partner);
        $second = Station::create(['name' => 'Zweite Station']);
        app(PartnerContext::class)->clear();
        $this->get($this->url($partner, $second))->assertOk()->assertDontSee('Nur für Station eins');
    }

    public function test_database_rejects_profile_station_from_another_partner(): void
    {
        [$actor, $own, $station] = $this->fixture();
        [$other, $foreign, $hidden] = $this->fixture();
        app(PartnerContext::class)->set($own);
        try {
            $this->expectException(QueryException::class);
            StationProfile::forceCreate(['station_id' => $hidden->id, 'data' => [], 'updated_by' => $actor->id]);
        } finally {
            app(PartnerContext::class)->clear();
        }
    }
}
