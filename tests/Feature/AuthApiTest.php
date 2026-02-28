<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'bad-login@example.com',
            'password_hash' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bad-login@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_fails_when_account_is_inactive(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password_hash' => 'Password123!',
            'is_active' => false,
            'is_deleted' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Account is disabled.');
    }

    public function test_user_can_login_and_receive_session_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password_hash' => 'Password123!',
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertCookie('session_token');

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
        ]);
    }

    public function test_me_requires_valid_session_cookie(): void
    {
        $response = $this->withCookie('session_token', 'invalid-token')
            ->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_access_me_with_session_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
            'password_hash' => 'Password123!',
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('data.email', 'me@example.com');
    }

    public function test_user_can_logout_and_revoke_current_session(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password_hash' => 'Password123!',
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $sessionSecret = 'manual-secret-for-logout-endpoint';
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($sessionSecret),
            'ip' => '127.0.0.1',
            'agent' => 'PHPUnit',
            'last_used_at' => now(),
        ]);
        $logoutResponse = $this->actingAs($user)->postJson('/api/logout');

        $logoutResponse->assertOk();
        $this->assertSoftDeleted('user_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }
}
