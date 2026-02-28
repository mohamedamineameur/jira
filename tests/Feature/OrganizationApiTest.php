<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_organization_routes(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'ACME',
            'slug' => 'acme',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->getJson('/api/organizations')->assertUnauthorized();
        $this->postJson('/api/organizations', [
            'name' => 'Globex',
            'slug' => 'globex',
            'owner_id' => $owner->id,
        ])->assertUnauthorized();
        $this->getJson("/api/organizations/{$organization->id}")->assertUnauthorized();
        $this->patchJson("/api/organizations/{$organization->id}", [
            'name' => 'ACME Updated',
        ])->assertUnauthorized();
        $this->deleteJson("/api/organizations/{$organization->id}")->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_organization(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->postJson('/api/organizations', [
            'name' => 'New Org',
            'slug' => 'new-org',
            'owner_id' => $owner->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Org')
            ->assertJsonPath('data.slug', 'new-org')
            ->assertJsonPath('data.owner_id', $owner->id)
            ->assertJsonPath('data.plan', 'free');

        $this->assertDatabaseHas('organizations', [
            'slug' => 'new-org',
            'owner_id' => $owner->id,
            'is_deleted' => false,
        ]);
    }

    public function test_organization_creation_validates_required_and_unique_fields(): void
    {
        $owner = User::factory()->create();
        Organization::query()->create([
            'name' => 'Existing',
            'slug' => 'existing-slug',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $response = $this->actingAs($owner)->postJson('/api/organizations', [
            'name' => '',
            'slug' => 'existing-slug',
            'owner_id' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'owner_id']);
    }

    public function test_owner_can_list_show_and_update_organization(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Owner Org',
            'slug' => 'owner-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id);

        $this->actingAs($owner)->patchJson("/api/organizations/{$organization->id}", [
            'name' => 'Owner Org Updated',
            'plan' => 'pro',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Owner Org Updated')
            ->assertJsonPath('data.plan', 'pro');
    }

    public function test_non_owner_non_admin_cannot_manage_organization(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Private Org',
            'slug' => 'private-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($otherUser)->getJson("/api/organizations/{$organization->id}")
            ->assertForbidden();
        $this->actingAs($otherUser)->patchJson("/api/organizations/{$organization->id}", [
            'name' => 'Hack',
        ])->assertForbidden();
        $this->actingAs($otherUser)->deleteJson("/api/organizations/{$organization->id}")
            ->assertForbidden();
    }

    public function test_admin_can_manage_organization_owned_by_another_user(): void
    {
        $adminUser = User::factory()->create();
        Admin::query()->create([
            'user_id' => $adminUser->id,
            'is_active' => true,
        ]);
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Managed Org',
            'slug' => 'managed-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($adminUser)->getJson("/api/organizations/{$organization->id}")
            ->assertOk();
        $this->actingAs($adminUser)->patchJson("/api/organizations/{$organization->id}", [
            'plan' => 'enterprise',
        ])->assertOk()
            ->assertJsonPath('data.plan', 'enterprise');
    }

    public function test_owner_can_update_organization_plan_with_dedicated_route(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Plan Org',
            'slug' => 'plan-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->patchJson("/api/organizations/{$organization->id}/plan", [
            'plan' => 'pro',
        ])->assertOk()
            ->assertJsonPath('data.plan', 'pro');
    }

    public function test_organization_plan_route_validates_allowed_values(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Plan Validation Org',
            'slug' => 'plan-validation-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->patchJson("/api/organizations/{$organization->id}/plan", [
            'plan' => 'gold',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);
    }

    public function test_non_owner_non_admin_cannot_update_organization_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Plan Locked Org',
            'slug' => 'plan-locked-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($otherUser)->patchJson("/api/organizations/{$organization->id}/plan", [
            'plan' => 'enterprise',
        ])->assertForbidden();
    }

    public function test_delete_marks_organization_as_deleted_and_hidden_from_index(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->deleteJson("/api/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'is_deleted' => true,
        ]);

        $indexResponse = $this->actingAs($owner)->getJson('/api/organizations');
        $indexResponse->assertOk();
        $this->assertCount(0, $indexResponse->json('data'));
    }
}
