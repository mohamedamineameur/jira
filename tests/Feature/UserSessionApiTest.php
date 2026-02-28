<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSessionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_backend_session_with_ip_and_agent(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/sessions', [
            'user_id' => $user->id,
            'token' => '1234567890123456',
            'ip' => '127.0.0.1',
            'agent' => 'PHPUnit Agent',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.ip', '127.0.0.1')
            ->assertJsonPath('data.agent', 'PHPUnit Agent')
            ->assertJsonPath('revoked', false);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'ip' => '127.0.0.1',
            'agent' => 'PHPUnit Agent',
        ]);
    }

    public function test_it_lists_sessions(): void
    {
        $user = User::factory()->create();
        UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.1',
            'agent' => 'Browser',
            'last_used_at' => now(),
        ]);

        $response = $this->getJson('/api/sessions');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_it_shows_one_session(): void
    {
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.2',
            'agent' => 'Postman',
            'last_used_at' => now(),
        ]);

        $response = $this->getJson("/api/sessions/{$session->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.ip', '10.0.0.2')
            ->assertJsonPath('data.agent', 'Postman')
            ->assertJsonPath('revoked', false);
    }

    public function test_it_revokes_a_session_with_soft_delete(): void
    {
        $user = User::factory()->create();
        $session = UserSession::query()->create([
            'user_id' => $user->id,
            'token_hash' => password_hash('1234567890123456', PASSWORD_BCRYPT),
            'ip' => '10.0.0.3',
            'agent' => 'Mobile',
            'last_used_at' => now(),
        ]);

        $response = $this->deleteJson("/api/sessions/{$session->id}");

        $response->assertOk()
            ->assertJsonPath('revoked', true);

        $this->assertSoftDeleted('user_sessions', [
            'id' => $session->id,
        ]);
    }
}
