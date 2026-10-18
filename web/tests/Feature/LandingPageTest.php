<?php

namespace Tests\Feature;

use App\Billing\BillingCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Öffentliche Seite gegen echtes getrenntes MySQL prüfen: keine Entwürfe,
 * Beispielpreise, internen Bearbeiterdaten oder unbemerkten Teststarts.
 * Sämtliche angelegten Tarife/Konten sind synthetisch und werden zurückgerollt.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function draft(User $admin, array $changes = [], ?int $tariffId = null)
    {
        return app(BillingCatalog::class)->draft($admin, $tariffId, array_replace([
            'code' => 'landing-test', 'name' => 'Veröffentlichter Testtarif',
            'description' => 'Synthetische Leistungsbeschreibung', 'price' => '19,90',
            'billing_interval' => 'monthly', 'billing_basis' => 'partner', 'station_limit' => 3,
        ], $changes));
    }

    public function test_unconfigured_landing_is_public_and_does_not_invent_offers_or_trial_days(): void
    {
        $this->get('/')->assertOk()->assertSee('Ihre Stationen. Ihr Team.')
            ->assertSee('Kostenlos testen')->assertSee('Noch keine veröffentlichten Angebote')
            ->assertSee('Die Testbedingungen werden gerade vorbereitet.')
            ->assertDontSee('30 Tage kostenlos testen')->assertSee('noch keinen Test starten');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('partners', 0);
        $this->assertDatabaseCount('trial_policies', 0);
        $this->assertGuest();
    }

    public function test_only_published_versions_are_visible_with_exact_price_and_terms(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true, 'email' => 'private-editor@example.test']);
        $catalog = app(BillingCatalog::class);
        $published = $this->draft($admin);
        $catalog->publish($admin, $published->tariff_id, $published->id, null);
        $this->draft($admin, ['name' => 'Geheimer Entwurf', 'price' => '999,99'], $published->tariff_id);
        $this->draft($admin, ['code' => 'hidden', 'name' => 'Unveröffentlichter Tarif']);

        $this->get('/')->assertOk()->assertSee('Veröffentlichter Testtarif')->assertSee('19,90')
            ->assertSee('pro Monat')->assertSee('pro Partnerfirma')->assertSee('Bis zu 3 Station(en)')
            ->assertDontSee('Geheimer Entwurf')->assertDontSee('999,99')
            ->assertDontSee('Unveröffentlichter Tarif')->assertDontSee('private-editor@example.test');
    }

    public function test_landing_uses_active_test_rules_without_creating_a_partner_trial(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $catalog = app(BillingCatalog::class);
        $first = $catalog->trialPolicy($admin, ['duration_days' => 14, 'station_limit' => 2], null);
        $catalog->trialPolicy($admin, ['duration_days' => 21, 'station_limit' => 4], $first->id);
        $this->get('/')->assertOk()->assertSee('21 Tage kostenlos testen')
            ->assertSee('21 Tage für bis zu 4 Station(en)')->assertDontSee('14 Tage kostenlos testen')
            ->assertSee('Keine automatische Kostenpflicht')->assertSee('E-Mail-Adresse')
            ->assertSee('gültiges Mandat')->assertSee('nur lesend');
        $this->assertDatabaseCount('partners', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('trial_policies', 2);
    }

    public function test_publication_updates_the_display_without_mutating_history(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $catalog = app(BillingCatalog::class);
        $first = $this->draft($admin);
        $catalog->publish($admin, $first->tariff_id, $first->id, null);
        $next = $this->draft($admin, ['name' => 'Neue Angebotsfassung', 'price' => '39,01', 'billing_interval' => 'yearly', 'billing_basis' => 'station', 'station_limit' => null], $first->tariff_id);
        $catalog->publish($admin, $next->tariff_id, $next->id, $first->id);
        $this->get('/')->assertOk()->assertSee('Neue Angebotsfassung')->assertSee('39,01')
            ->assertSee('pro Jahr')->assertSee('pro Station')->assertSee('Ohne tarifliches Stationslimit')
            ->assertDontSee('19,90');
        $this->assertSame(1990, $first->fresh()->price_cents);
    }

    public function test_tariff_text_is_escaped_and_cannot_inject_script_or_html(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $catalog = app(BillingCatalog::class);
        $version = $this->draft($admin, ['name' => '<script>alert("test")</script>', 'description' => '<img src=x onerror=alert(1)>']);
        $catalog->publish($admin, $version->tariff_id, $version->id, null);
        $this->get('/')->assertOk()->assertSee('<script>alert("test")</script>')
            ->assertDontSee('<script>alert("test")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_page_has_accessible_navigation_and_no_fake_registration_form(): void
    {
        $this->get('/')->assertOk()->assertSee('lang="de"', false)
            ->assertSee('Zum Inhalt springen')->assertSee('aria-label="Hauptnavigation"', false)
            ->assertSee('id="kostenlos-testen"', false)->assertSee('id="tarife"', false)
            ->assertSee('noindex, nofollow')->assertSee(route('login'), false)
            ->assertDontSee('<form', false)->assertSee('Die Testkonfiguration folgt als Nächstes.');
    }

    public function test_mockup_layout_uses_local_assets_and_a_clearly_labelled_fictional_app_preview(): void
    {
        $this->get('/')->assertOk()->assertSee('Alles im Blick.')
            ->assertSee('App-Vorschau · Beispieldaten')->assertSee('fiktiven Beispieldaten')
            ->assertSee('Station Musterstadt')->assertSee('Kostenlos registrieren')
            ->assertSee('Stationen anlegen')->assertSee('Mitarbeiter zuordnen')
            ->assertSee('Team &amp; Verträge', false)->assertSee('MHD &amp; Etiketten', false)
            ->assertSee('Kasse &amp; Übergaben', false)
            ->assertSee(asset('images/station-background-v1.png'), false)
            ->assertSee(asset('css/nova-landing.css'), false)
            ->assertDontSee('fonts.googleapis.com')->assertDontSee('cdn.jsdelivr.net');
        $this->assertFileExists(public_path('images/station-background-v1.png'));
        $this->assertStringContainsString('--teal: #007e8c', file_get_contents(public_path('css/nova-landing.css')));
        $this->assertStringContainsString('prefers-reduced-motion', file_get_contents(public_path('css/nova-landing.css')));
    }
}
