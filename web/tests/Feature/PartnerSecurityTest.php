<?php

namespace Tests\Feature;

use App\Enums\PartnerRole;
use App\Models\Partner;
use App\Models\PartnerMembership;
use App\Models\Station;
use App\Models\User;
use App\Tenancy\PartnerContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Prüft Mandantengrenzen gegen echte MySQL-Tabellen, nicht nur Menüanzeigen. */
class PartnerSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function member(Partner $partner, PartnerRole $role = PartnerRole::Owner): User
    {
        $user = User::factory()->create();
        PartnerMembership::forceCreate(['user_id' => $user->id, 'partner_id' => $partner->id, 'role' => $role]);

        return $user;
    }

    private function station(Partner $partner, string $name): Station
    {
        $context = app(PartnerContext::class);
        $context->set($partner);
        try {
            return Station::create(['name' => $name]);
        } finally {
            $context->clear();
        }
    }

    public function test_guest_cannot_read_partner_data(): void
    {
        $partner = Partner::create(['name' => 'Testpartner']);
        $this->getJson("/app/partners/{$partner->id}")->assertUnauthorized();
    }

    public function test_membership_does_not_allow_access_to_another_partner(): void
    {
        $own = Partner::create(['name' => 'Eigener Partner']);
        $foreign = Partner::create(['name' => 'Fremder Partner']);
        $user = $this->member($own);
        $this->actingAs($user)->getJson("/app/partners/{$own->id}")->assertOk()->assertJsonPath('name', 'Eigener Partner');
        $this->getJson("/app/partners/{$foreign->id}")->assertNotFound();
        $this->getJson('/app/partners/99999999')->assertNotFound();
    }

    public function test_station_lists_and_route_binding_are_partner_scoped(): void
    {
        $own = Partner::create(['name' => 'Partner A']);
        $foreign = Partner::create(['name' => 'Partner B']);
        $user = $this->member($own);
        $this->station($own, 'Sichtbare Station');
        $hidden = $this->station($foreign, 'Verborgene Station');
        $this->actingAs($user)->getJson("/app/partners/{$own->id}/stations")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Sichtbare Station')
            ->assertDontSee('Verborgene Station');
        $this->patchJson("/app/partners/{$own->id}/stations/{$hidden->id}", ['name' => 'Manipuliert'])->assertNotFound();
        $this->assertDatabaseHas('stations', ['id' => $hidden->id, 'name' => 'Verborgene Station', 'partner_id' => $foreign->id]);
    }

    #[DataProvider('roles')]
    public function test_roles_are_enforced_on_writes_and_member_lists(PartnerRole $role, bool $canManageStations, bool $isOwner): void
    {
        $partner = Partner::create(['name' => 'Rollentest']);
        $user = $this->member($partner, $role);
        $station = $this->station($partner, 'Ausgangsname');
        $this->actingAs($user)->getJson("/app/partners/{$partner->id}")->assertOk();
        $this->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Neue Station'])->assertStatus($canManageStations ? 201 : 403);
        $this->patchJson("/app/partners/{$partner->id}/stations/{$station->id}", ['name' => 'Neuer Name'])->assertStatus($canManageStations ? 200 : 403);
        $this->patchJson("/app/partners/{$partner->id}", ['name' => 'Partner umbenannt'])->assertStatus($isOwner ? 200 : 403);
        $this->getJson("/app/partners/{$partner->id}/members")->assertStatus($isOwner ? 200 : 403);
    }

    public static function roles(): array
    {
        return [
            'Inhaber' => [PartnerRole::Owner, true, true],
            'Verwaltung' => [PartnerRole::Manager, true, false],
            'Mitarbeiter' => [PartnerRole::Staff, false, false],
            'Lesend' => [PartnerRole::Viewer, false, false],
        ];
    }

    public function test_partner_assignment_cannot_be_injected_into_station_creation(): void
    {
        $partner = Partner::create(['name' => 'Eigener Partner']);
        $foreign = Partner::create(['name' => 'Fremder Partner']);
        $this->actingAs($this->member($partner))->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Angriff', 'partner_id' => $foreign->id])
            ->assertUnprocessable()->assertJsonValidationErrors('partner_id');
        $this->assertDatabaseMissing('stations', ['name' => 'Angriff']);
    }

    public function test_successful_station_creation_uses_only_the_resolved_partner(): void
    {
        $partner = Partner::create(['name' => 'Eigener Partner']);
        $this->actingAs($this->member($partner))->postJson("/app/partners/{$partner->id}/stations", ['name' => 'Sicher zugeordnet'])->assertCreated();
        $this->assertDatabaseHas('stations', ['name' => 'Sicher zugeordnet', 'partner_id' => $partner->id]);
    }

    public function test_privilege_fields_cannot_be_submitted_with_partner_changes(): void
    {
        $partner = Partner::create(['name' => 'Eigener Partner']);
        $user = $this->member($partner);
        $this->actingAs($user)->patchJson("/app/partners/{$partner->id}", ['name' => 'Angriff', 'is_super_admin' => true, 'role' => 'owner'])
            ->assertUnprocessable()->assertJsonValidationErrors(['is_super_admin', 'role']);
        $this->assertFalse($user->fresh()->is_super_admin);
        $this->assertDatabaseHas('partners', ['id' => $partner->id, 'name' => 'Eigener Partner']);
    }

    public function test_inactive_membership_is_rejected(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $user = $this->member($partner);
        PartnerMembership::where('user_id', $user->id)->update(['is_active' => false]);
        $this->actingAs($user)->getJson("/app/partners/{$partner->id}")->assertNotFound();
    }

    public function test_inactive_partner_is_rejected(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $user = $this->member($partner);
        $partner->forceFill(['is_active' => false])->save();
        $this->actingAs($user)->getJson("/app/partners/{$partner->id}")->assertNotFound();
    }

    public function test_inactive_user_is_rejected_even_with_an_existing_session(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $user = $this->member($partner);
        $user->forceFill(['is_active' => false])->save();
        $this->actingAs($user)->getJson("/app/partners/{$partner->id}")->assertForbidden();
    }

    public function test_unverified_user_cannot_enter_a_partner(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $user = $this->member($partner);
        $user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($user)->getJson("/app/partners/{$partner->id}")->assertForbidden();
    }

    public function test_superadmin_is_not_implicitly_a_partner_member(): void
    {
        $partner = Partner::create(['name' => 'Geschützter Partner']);
        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->getJson("/app/partners/{$partner->id}")->assertNotFound();
    }

    public function test_partner_context_is_cleared_after_a_request(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $this->actingAs($this->member($partner))->getJson("/app/partners/{$partner->id}")->assertOk();
        $this->expectException(AuthorizationException::class);
        app(PartnerContext::class)->id();
    }

    public function test_partner_owned_queries_fail_without_context(): void
    {
        $this->expectException(AuthorizationException::class);
        Station::count();
    }

    public function test_saving_a_foreign_loaded_model_is_rejected(): void
    {
        $own = Partner::create(['name' => 'Partner A']);
        $foreign = Partner::create(['name' => 'Partner B']);
        $station = $this->station($foreign, 'Fremde Station');
        app(PartnerContext::class)->set($own);
        try {
            $this->expectException(AuthorizationException::class);
            $station->update(['name' => 'Angriff']);
        } finally {
            app(PartnerContext::class)->clear();
        }
    }

    public function test_partner_id_cannot_be_changed_on_a_loaded_model(): void
    {
        $own = Partner::create(['name' => 'Partner A']);
        $foreign = Partner::create(['name' => 'Partner B']);
        $station = $this->station($own, 'Eigene Station');
        app(PartnerContext::class)->set($own);
        try {
            $station->partner_id = $foreign->id;
            $this->expectException(AuthorizationException::class);
            $station->save();
        } finally {
            app(PartnerContext::class)->clear();
        }
    }

    public function test_multiple_memberships_have_independent_roles(): void
    {
        $first = Partner::create(['name' => 'Partner A']);
        $second = Partner::create(['name' => 'Partner B']);
        $user = $this->member($first);
        PartnerMembership::forceCreate(['user_id' => $user->id, 'partner_id' => $second->id, 'role' => PartnerRole::Viewer]);
        $this->actingAs($user)->patchJson("/app/partners/{$first->id}", ['name' => 'Erlaubt'])->assertOk();
        $this->patchJson("/app/partners/{$second->id}", ['name' => 'Verboten'])->assertForbidden();
    }

    public function test_membership_revocation_takes_effect_on_the_next_request(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $user = $this->member($partner);
        $this->actingAs($user)->getJson("/app/partners/{$partner->id}")->assertOk();
        PartnerMembership::where('user_id', $user->id)->update(['is_active' => false]);
        $this->getJson("/app/partners/{$partner->id}")->assertNotFound();
    }

    public function test_workspace_lists_only_explicitly_authorized_partners(): void
    {
        $partner = Partner::create(['name' => 'Sichtbare Firma']);
        Partner::create(['name' => 'Nicht freigegebene Firma']);
        $this->actingAs($this->member($partner))->get('/app')->assertOk()
            ->assertSee('Sichtbare Firma')->assertDontSee('Nicht freigegebene Firma');
    }

    public function test_context_is_cleared_after_a_validation_error(): void
    {
        $partner = Partner::create(['name' => 'Partner']);
        $this->actingAs($this->member($partner))->postJson("/app/partners/{$partner->id}/stations", [])
            ->assertUnprocessable();
        $this->expectException(AuthorizationException::class);
        app(PartnerContext::class)->id();
    }

    public function test_a_loaded_foreign_station_cannot_be_deleted(): void
    {
        $own = Partner::create(['name' => 'Partner A']);
        $foreign = Partner::create(['name' => 'Partner B']);
        $station = $this->station($foreign, 'Geschützte Station');
        app(PartnerContext::class)->set($own);
        try {
            $this->expectException(AuthorizationException::class);
            $station->delete();
        } finally {
            app(PartnerContext::class)->clear();
        }
    }
}
