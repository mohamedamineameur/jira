<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/admins');

        $response->assertUnauthorized();
    }

    public function test_non_admin_user_cannot_manage_admins(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/admins');

        $response->assertForbidden();
    }

    public function test_admin_can_create_admin(): void
    {
        $adminUser = User::factory()->create();
        Admin::query()->create([
            'user_id' => $adminUser->id,
            'is_active' => true,
        ]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($adminUser)->postJson('/api/admins', [
            'user_id' => $targetUser->id,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $targetUser->id)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('admins', [
            'user_id' => $targetUser->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_admin_active_state(): void
    {
        $adminUser = User::factory()->create();
        Admin::query()->create([
            'user_id' => $adminUser->id,
            'is_active' => true,
        ]);
        $targetUser = User::factory()->create();
        $targetAdmin = Admin::query()->create([
            'user_id' => $targetUser->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)->patchJson("/api/admins/{$targetAdmin->id}", [
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $targetAdmin->id)
            ->assertJsonPath('data.is_active', false);
    }
}
