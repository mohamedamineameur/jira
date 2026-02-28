<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSessionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_session_routes(): void
    {
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.8',
            'agent' => 'Guest Browser',
            'last_used_at' => now(),
        ]);

        $this->getJson('/api/sessions')->assertUnauthorized();
        $this->postJson('/api/sessions', [
            'user_id' => $user->id,
            'token' => '1234567890123456',
        ])->assertUnauthorized();
        $this->getJson("/api/sessions/{$session->id}")->assertUnauthorized();
        $this->deleteJson("/api/sessions/{$session->id}")->assertUnauthorized();
    }

    public function test_it_creates_a_backend_session_with_ip_and_agent(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($authUser)->postJson('/api/sessions', [
            'user_id' => $user->id,
            'token' => '1234567890123456',
            'ip' => '127.0.0.1',
            'agent' => 'PHPUnit Agent',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.ip', '127.0.0.1')
            ->assertJsonPath('data.agent', 'PHPUnit Agent')
            ->assertJsonPath('revoked', false)
            ->assertJsonMissingPath('data.token_hash');

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'ip' => '127.0.0.1',
            'agent' => 'PHPUnit Agent',
        ]);
    }

    public function test_it_lists_sessions(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.1',
            'agent' => 'Browser',
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($authUser)->getJson('/api/sessions');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_it_validates_session_creation_payload(): void
    {
        $authUser = User::factory()->create();

        $response = $this->actingAs($authUser)->postJson('/api/sessions', [
            'user_id' => 'not-a-uuid',
            'token' => 'short',
            'ip' => 'invalid-ip',
            'agent' => 123,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'token', 'ip', 'agent']);
    }

    public function test_it_shows_one_session(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.2',
            'agent' => 'Postman',
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($authUser)->getJson("/api/sessions/{$session->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.ip', '10.0.0.2')
            ->assertJsonPath('data.agent', 'Postman')
            ->assertJsonPath('revoked', false)
            ->assertJsonMissingPath('data.token_hash');
    }

    public function test_it_revokes_a_session_with_soft_delete(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.3',
            'agent' => 'Mobile',
            'last_used_at' => now(),
        ]);

        $response = $this->actingAs($authUser)->deleteJson("/api/sessions/{$session->id}");

        $response->assertOk()
            ->assertJsonPath('revoked', true);

        $this->assertSoftDeleted('user_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_revoked_session_is_no_longer_resolvable_by_show_route(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.9',
            'agent' => 'Desktop',
            'last_used_at' => now(),
        ]);

        $this->actingAs($authUser)->deleteJson("/api/sessions/{$session->id}")
            ->assertOk();

        $this->actingAs($authUser)->getJson("/api/sessions/{$session->id}")
            ->assertNotFound();
    }
}
