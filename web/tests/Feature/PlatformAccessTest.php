<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Plattformrechte müssen auch lokal und bei Livewire-Folgeanfragen gelten. */
class PlatformAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_platform_login(): void
    {
        $this->get('/superadmin')->assertRedirect('/superadmin/login');
        $this->get('/superadmin/login')->assertOk();
    }

    public function test_partner_user_cannot_open_platform_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/superadmin')->assertForbidden();
    }

    public function test_verified_active_superadmin_can_open_custom_dashboard(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->get('/superadmin')->assertOk()->assertSee('Plattformverwaltung')->assertSee('keine Personalakten');
    }

    public function test_unverified_superadmin_is_denied(): void
    {
        $admin = User::factory()->unverified()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->get('/superadmin')->assertForbidden();
    }

    public function test_inactive_superadmin_is_denied(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true, 'is_active' => false]);
        $this->actingAs($admin)->get('/superadmin')->assertForbidden();
    }

    public function test_dashboard_rechecks_access_after_role_revocation(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->get('/superadmin')->assertOk();
        $component = Livewire::test(PlatformOverview::class);
        $admin->forceFill(['is_super_admin' => false])->save();
        $component->call('$refresh')->assertForbidden();
    }

    public function test_mass_assignment_cannot_create_a_superadmin(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@example.test', 'password' => 'TestPassword!12345', 'is_super_admin' => true, 'email_verified_at' => now()]);
        $this->assertFalse($user->fresh()->is_super_admin);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_local_bootstrap_requires_a_strong_confirmed_password(): void
    {
        $this->artisan('nova:superadmin', ['email' => 'admin@example.test'])
            ->expectsQuestion('Neues Passwort (mindestens 14 Zeichen, Groß-/Kleinbuchstaben, Zahl und Sonderzeichen)', 'weak')
            ->expectsQuestion('Passwort wiederholen', 'weak')->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_local_bootstrap_creates_a_hashed_verified_superadmin_without_membership(): void
    {
        $password = 'Local-Test-Password!42';
        $this->artisan('nova:superadmin', ['email' => 'admin@example.test'])
            ->expectsQuestion('Neues Passwort (mindestens 14 Zeichen, Groß-/Kleinbuchstaben, Zahl und Sonderzeichen)', $password)
            ->expectsQuestion('Passwort wiederholen', $password)->assertSuccessful();
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->is_super_admin);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotSame($password, $user->password);
        $this->assertTrue(password_verify($password, $user->password));
        $this->assertDatabaseCount('partner_memberships', 0);
    }

    public function test_local_bootstrap_never_promotes_an_existing_user(): void
    {
        $user = User::factory()->create();
        $this->artisan('nova:superadmin', ['email' => $user->email])->assertFailed();
        $this->assertFalse($user->fresh()->is_super_admin);
    }

    public function test_default_seeding_never_creates_a_known_login(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }
}
