<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_work_without_exposing_privileges(): void
    {
        $user = User::factory()->create();
        $this->get('/login')->assertOk()->assertSee('Merlin Nova');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/app');
        $this->assertAuthenticatedAs($user);
        $this->get('/app')->assertOk()->assertSee('noch kein Partner');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_logins_are_rate_limited_even_if_the_next_password_is_correct(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_user_has_no_workspace_access(): void
    {
        $this->actingAs(User::factory()->unverified()->create())->get('/app')->assertRedirect('/email/verify');
    }
}
