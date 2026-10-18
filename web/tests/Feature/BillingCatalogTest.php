<?php

namespace Tests\Feature;

use App\Billing\BillingCatalog;
use App\Filament\Pages\BillingSettings;
use App\Models\Tariff;
use App\Models\TariffVersion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Echte MySQL-Tests für Katalog, Freigabe und Testregeln. Kein Bankkontakt.
 * Neben der Oberfläche wird der Fachservice direkt angegriffen, damit
 * versteckte Navigation niemals als Berechtigungsprüfung missverstanden wird.
 */
class BillingCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function data(array $changes = []): array
    {
        return array_replace(['code' => 'nova-test', 'name' => 'Synthetischer Testtarif', 'description' => 'Nur Testdaten', 'price' => '12,34', 'billing_interval' => 'monthly', 'billing_basis' => 'partner', 'station_limit' => 3], $changes);
    }

    public function test_unconfigured_catalog_has_no_implicit_prices_or_trial_duration(): void
    {
        $catalog = app(BillingCatalog::class);
        $this->assertNull($catalog->activeTrialPolicy());
        $this->assertCount(0, $catalog->publishedOffers());
    }

    public function test_draft_is_not_public_until_explicitly_published(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $version = $catalog->draft($admin, null, $this->data());
        $this->assertSame(1234, $version->price_cents);
        $this->assertSame(1, $version->number);
        $this->assertCount(0, $catalog->publishedOffers());
        $catalog->publish($admin, $version->tariff_id, $version->id, null);
        $this->assertSame($version->id, $catalog->publishedOffers()->sole()->id);
        $this->assertArrayNotHasKey('created_by', $catalog->publishedOffers()->sole()->toArray());
    }

    public function test_revisions_never_overwrite_previously_published_prices(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $first = $catalog->draft($admin, null, $this->data());
        $catalog->publish($admin, $first->tariff_id, $first->id, null);
        $next = $catalog->draft($admin, $first->tariff_id, $this->data(['price' => '99.99', 'billing_basis' => 'station', 'billing_interval' => 'yearly']));
        $this->assertSame(2, $next->number);
        $this->assertSame(1234, $catalog->publishedOffers()->sole()->price_cents);
        $catalog->publish($admin, $next->tariff_id, $next->id, $first->id);
        $this->assertSame(9999, $catalog->publishedOffers()->sole()->price_cents);
        $this->assertSame(1234, $first->fresh()->price_cents);
        $this->assertDatabaseCount('tariff_publications', 2);
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_or_ambiguous_prices_are_rejected(mixed $price): void
    {
        $this->expectException(ValidationException::class);
        app(BillingCatalog::class)->draft($this->admin(), null, $this->data(['price' => $price]));
    }

    public static function invalidPrices(): array
    {
        return [['-1'], ['0'], ['12,345'], ['1e2'], ['1.234,56'], [12.34], ['99999999']];
    }

    #[DataProvider('invalidTariffFields')]
    public function test_invalid_tariff_fields_never_create_a_partial_tariff(array $changes): void
    {
        try {
            app(BillingCatalog::class)->draft($this->admin(), null, $this->data($changes));
            $this->fail('Ungültige Tarifdaten wurden akzeptiert.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('tariffs', 0);
            $this->assertDatabaseCount('tariff_versions', 0);
        }
    }

    public static function invalidTariffFields(): array
    {
        return [[['code' => '../invalid']], [['name' => '']], [['billing_interval' => 'daily']], [['billing_basis' => 'user']], [['station_limit' => 0]], [['station_limit' => 10001]]];
    }

    public function test_money_conversion_does_not_use_floating_point_rounding(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $first = $catalog->draft($admin, null, $this->data(['price' => '0,01']));
        $second = $catalog->draft($admin, $first->tariff_id, $this->data(['price' => '19.9']));
        $this->assertSame(1, $first->price_cents);
        $this->assertSame(1990, $second->price_cents);
    }

    public function test_stale_admin_cannot_overwrite_a_newer_publication(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $first = $catalog->draft($admin, null, $this->data());
        $next = $catalog->draft($admin, $first->tariff_id, $this->data(['price' => '20']));
        $catalog->publish($admin, $first->tariff_id, $first->id, null);
        try {
            $catalog->publish($admin, $next->tariff_id, $next->id, null);
            $this->fail('Veraltete Freigabe wurde nicht abgewiesen.');
        } catch (ValidationException) {
            $this->assertSame($first->id, Tariff::findOrFail($first->tariff_id)->published_version_id);
            $this->assertDatabaseCount('tariff_publications', 1);
        }
    }

    public function test_publication_is_idempotent(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $version = $catalog->draft($admin, null, $this->data());
        $catalog->publish($admin, $version->tariff_id, $version->id, null);
        $catalog->publish($admin, $version->tariff_id, $version->id, null);
        $this->assertDatabaseCount('tariff_publications', 1);
    }

    public function test_a_version_from_another_tariff_cannot_be_published_under_the_wrong_tariff(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $first = $catalog->draft($admin, null, $this->data());
        $other = $catalog->draft($admin, null, $this->data(['code' => 'other']));
        $this->expectException(ModelNotFoundException::class);
        $catalog->publish($admin, $first->tariff_id, $other->id, null);
    }

    public function test_older_unpublished_drafts_cannot_roll_back_the_catalog(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $first = $catalog->draft($admin, null, $this->data());
        $next = $catalog->draft($admin, $first->tariff_id, $this->data(['price' => '20']));
        $catalog->publish($admin, $next->tariff_id, $next->id, null);
        $this->expectException(ValidationException::class);
        $catalog->publish($admin, $first->tariff_id, $first->id, $next->id);
    }

    public function test_trial_changes_preserve_old_terms_and_record_the_actor(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $first = $catalog->trialPolicy($admin, ['duration_days' => 14, 'station_limit' => 2], null);
        $next = $catalog->trialPolicy($admin, ['duration_days' => 30, 'station_limit' => 4], $first->id);
        $this->assertSame($next->id, $catalog->activeTrialPolicy()->id);
        $this->assertSame(14, $first->fresh()->duration_days);
        $this->assertSame(2, $first->fresh()->station_limit);
        $this->assertSame($admin->id, $next->created_by);
        $this->assertDatabaseCount('trial_policies', 2);
    }

    public function test_stale_trial_form_is_rejected_without_creating_another_revision(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $catalog->trialPolicy($admin, ['duration_days' => 14, 'station_limit' => 2], null);
        try {
            $catalog->trialPolicy($admin, ['duration_days' => 60, 'station_limit' => 9], null);
            $this->fail('Veraltetes Formular akzeptiert.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('trial_policies', 1);
        }
    }

    #[DataProvider('invalidTrialRules')]
    public function test_invalid_trial_rules_are_rejected(array $rules): void
    {
        $this->expectException(ValidationException::class);
        app(BillingCatalog::class)->trialPolicy($this->admin(), $rules, null);
    }

    public static function invalidTrialRules(): array
    {
        return [[['duration_days' => 0, 'station_limit' => 1]], [['duration_days' => 14, 'station_limit' => 0]], [['duration_days' => 1.5, 'station_limit' => 1]], [['duration_days' => 3651, 'station_limit' => 1]]];
    }

    public function test_versions_cannot_be_updated_through_eloquent(): void
    {
        $version = app(BillingCatalog::class)->draft($this->admin(), null, $this->data());
        $version->forceFill(['price_cents' => 1]);
        $this->expectException(LogicException::class);
        $version->save();
    }

    public function test_trial_history_cannot_be_deleted_through_eloquent(): void
    {
        $policy = app(BillingCatalog::class)->trialPolicy($this->admin(), ['duration_days' => 14, 'station_limit' => 1], null);
        $this->expectException(LogicException::class);
        $policy->delete();
    }

    public function test_non_admin_is_denied_on_page_and_direct_service_calls(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/superadmin/tarife')->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(BillingCatalog::class)->draft($user, null, $this->data());
    }

    public function test_superadmin_can_use_both_forms_but_publication_needs_confirmation(): void
    {
        $this->actingAs($this->admin())->get('/superadmin/tarife')->assertOk()->assertSee('Kostenloser Test');
        $component = Livewire::test(BillingSettings::class)
            ->set('code', 'ui-test')->set('name', 'UI-Test')->set('price', '10,50')
            ->call('saveDraft')->assertHasNoErrors();
        $version = TariffVersion::sole();
        $component->call('publishVersion', $version->id)->assertHasErrors('confirmPublication');
        $this->assertDatabaseCount('tariff_publications', 0);
        $component->set('confirmPublication', true)->call('publishVersion', $version->id)->assertHasNoErrors();
        $component->set('trialDays', '21')->set('trialStationLimit', '2')->call('saveTrialPolicy')->assertHasNoErrors();
        $this->assertDatabaseCount('tariff_publications', 1);
        $this->assertSame(21, app(BillingCatalog::class)->activeTrialPolicy()->duration_days);
    }

    public function test_livewire_mutation_rechecks_revoked_platform_rights(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/superadmin/tarife')->assertOk();
        $component = Livewire::test(BillingSettings::class);
        $component->set('trialDays', '21');
        $admin->forceFill(['is_super_admin' => false])->save();
        $component->call('saveTrialPolicy')->assertForbidden();
        $this->assertDatabaseCount('trial_policies', 0);
    }

    public function test_inactive_superadmin_cannot_change_trial_rules_directly(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin)->get('/superadmin/tarife')->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(BillingCatalog::class)->trialPolicy($admin, ['duration_days' => 14, 'station_limit' => 1], null);
    }

    public function test_unverified_superadmin_cannot_publish_directly(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $version = $catalog->draft($admin, null, $this->data());
        $admin->forceFill(['email_verified_at' => null])->save();
        $this->expectException(AuthorizationException::class);
        $catalog->publish($admin, $version->tariff_id, $version->id, null);
    }

    public function test_duplicate_tariff_code_does_not_create_another_tariff(): void
    {
        $catalog = app(BillingCatalog::class);
        $admin = $this->admin();
        $catalog->draft($admin, null, $this->data());
        try {
            $catalog->draft($admin, null, $this->data());
            $this->fail('Doppelter Tarifcode wurde akzeptiert.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('tariffs', 1);
            $this->assertDatabaseCount('tariff_versions', 1);
        }
    }
}
