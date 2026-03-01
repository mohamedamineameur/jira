<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_login_sends_otp_without_creating_session_cookie(): void
    {
        Mail::fake();

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
            ->assertJsonPath('message', 'OTP sent successfully.')
            ->assertJsonPath('data.email', 'login@example.com')
            ->assertCookieMissing('session_token');

        $user->refresh();
        $this->assertNotNull($user->otp_hash);
        $this->assertNotNull($user->otp_expires_at);
    }

    public function test_user_can_verify_otp_and_receive_session_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'otp@example.com',
            'password_hash' => 'Password123!',
            'otp_hash' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $response = $this->postJson('/api/login/verify-otp', [
            'email' => 'otp@example.com',
            'otp' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertCookie('session_token');

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
        ]);
    }

    public function test_verify_otp_fails_with_invalid_or_expired_code(): void
    {
        User::factory()->create([
            'email' => 'otp-expired@example.com',
            'password_hash' => 'Password123!',
            'otp_hash' => Hash::make('123456'),
            'otp_expires_at' => now()->subMinute(),
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $response = $this->postJson('/api/login/verify-otp', [
            'email' => 'otp-expired@example.com',
            'otp' => '123456',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid OTP or email.');
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
