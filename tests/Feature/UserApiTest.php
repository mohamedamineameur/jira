<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_user_with_required_fields_only(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Amine',
            'email' => 'amine@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Amine')
            ->assertJsonPath('data.email', 'amine@example.com')
            ->assertJsonPath('state', 'active');

        $this->assertDatabaseHas('users', [
            'email' => 'amine@example.com',
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $createdUser = User::query()->where('email', 'amine@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertNotNull($createdUser?->token_hash);
        $this->assertNotNull($createdUser?->email_verification_expires_at);
    }

    public function test_user_creation_validates_payload(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $response = $this->postJson('/api/users', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);
    }

    public function test_guest_cannot_access_protected_user_routes(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson("/api/users/{$user->id}")->assertUnauthorized();
        $this->patchJson("/api/users/{$user->id}/profile", [
            'name' => 'Nope',
        ])->assertUnauthorized();
        $this->patchJson("/api/users/{$user->id}/password", [
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ])->assertUnauthorized();
        $this->patchJson("/api/users/{$user->id}/admin", [
            'is_active' => false,
        ])->assertUnauthorized();
        $this->deleteJson("/api/users/{$user->id}")->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_and_show_users(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($authUser)->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($authUser)->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($authUser)->patchJson("/api/users/{$user->id}/profile", [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_profile_update_validates_unique_email(): void
    {
        $authUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->actingAs($authUser)->patchJson("/api/users/{$targetUser->id}/profile", [
            'email' => $existingUser->email,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($authUser)->patchJson("/api/users/{$user->id}/password", [
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue(password_verify('AnotherPass123!', $user->password_hash));
    }

    public function test_password_update_requires_confirmation(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($authUser)->patchJson("/api/users/{$user->id}/password", [
            'password' => 'AnotherPass123!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_can_update_user_by_admin_route_with_is_active_only(): void
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
        $normalUser = User::factory()->create();
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($normalUser)->patchJson("/api/users/{$user->id}/admin", [
            'is_active' => false,
        ]);

        $response->assertForbidden();
    }

    public function test_it_soft_deletes_user_with_flags(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create([
            'is_deleted' => false,
            'deleted_at' => null,
        ]);

        $response = $this->actingAs($authUser)->deleteJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.is_deleted', true)
            ->assertJsonPath('state', 'deleted');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_deleted' => true,
        ]);
    }
}
