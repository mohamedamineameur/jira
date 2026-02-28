<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_with_required_fields_only(): void
    {
        $payload = [
            'name' => 'Amine',
            'email' => 'amine@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Amine')
            ->assertJsonPath('data.email', 'amine@example.com')
            ->assertJsonPath('state', 'active');

        $this->assertDatabaseHas('users', [
            'email' => 'amine@example.com',
            'is_active' => true,
            'is_deleted' => false,
        ]);
    }

    public function test_it_updates_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->patchJson("/api/users/{$user->id}/profile", [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_it_updates_user_password(): void
    {
        $user = User::factory()->create();

        $response = $this->patchJson("/api/users/{$user->id}/password", [
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue(password_verify('AnotherPass123!', $user->password_hash));
    }

    public function test_it_updates_user_by_admin_with_is_active_only(): void
    {
        $adminUser = User::factory()->create();
        Admin::query()->create([
            'user_id' => $adminUser->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)->patchJson("/api/users/{$user->id}/admin", [
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('state', 'inactive');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_non_admin_cannot_update_user_by_admin_route(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $normalUser = User::factory()->create();

        $response = $this->actingAs($normalUser)->patchJson("/api/users/{$user->id}/admin", [
            'is_active' => false,
        ]);

        $response->assertForbidden();
    }

    public function test_it_soft_deletes_user_with_flags(): void
    {
        $user = User::factory()->create([
            'is_deleted' => false,
            'deleted_at' => null,
        ]);

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.is_deleted', true)
            ->assertJsonPath('state', 'deleted');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_deleted' => true,
        ]);
    }
}
