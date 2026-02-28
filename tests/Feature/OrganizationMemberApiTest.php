<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_organization_member_routes(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Org',
            'slug' => 'org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->getJson("/api/organizations/{$organization->id}/members")->assertUnauthorized();
        $this->postJson("/api/organizations/{$organization->id}/members", [
            'user_id' => $memberUser->id,
            'role' => 'member',
        ])->assertUnauthorized();
        $this->patchJson("/api/organizations/{$organization->id}/members/{$memberUser->id}", [
            'role' => 'admin',
        ])->assertUnauthorized();
        $this->getJson("/api/organizations/{$organization->id}/members/me")->assertUnauthorized();
        $this->deleteJson("/api/organizations/{$organization->id}/members/{$memberUser->id}")
            ->assertUnauthorized();
    }

    public function test_owner_can_get_his_role_with_members_me_route(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Owner Me Org',
            'slug' => 'owner-me-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/members/me")
            ->assertOk()
            ->assertJsonPath('data.user_id', $owner->id)
            ->assertJsonPath('data.role', 'owner');
    }

    public function test_member_can_get_his_role_with_members_me_route(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Member Me Org',
            'slug' => 'member-me-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)->getJson("/api/organizations/{$organization->id}/members/me")
            ->assertOk()
            ->assertJsonPath('data.user_id', $member->id)
            ->assertJsonPath('data.role', 'member');
    }

    public function test_non_member_cannot_access_members_me_route(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'No Access Org',
            'slug' => 'no-access-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($outsider)->getJson("/api/organizations/{$organization->id}/members/me")
            ->assertForbidden();
    }

    public function test_owner_can_add_list_update_and_soft_delete_members(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Org Team',
            'slug' => 'org-team',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/members", [
            'user_id' => $memberUser->id,
            'role' => 'member',
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $memberUser->id)
            ->assertJsonPath('data.role', 'member');

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/members")
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->patchJson("/api/organizations/{$organization->id}/members/{$memberUser->id}", [
            'role' => 'admin',
        ])->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->actingAs($owner)->deleteJson("/api/organizations/{$organization->id}/members/{$memberUser->id}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
            'is_deleted' => true,
        ]);
    }

    public function test_non_manager_member_cannot_manage_organization_members(): void
    {
        $owner = User::factory()->create();
        $normalMember = User::factory()->create();
        $targetUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Locked Org',
            'slug' => 'locked-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $normalMember->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($normalMember)->postJson("/api/organizations/{$organization->id}/members", [
            'user_id' => $targetUser->id,
            'role' => 'member',
        ])->assertForbidden();
    }

    public function test_org_admin_member_can_manage_members(): void
    {
        $owner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $newUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Admin Org',
            'slug' => 'admin-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $orgAdmin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($orgAdmin)->postJson("/api/organizations/{$organization->id}/members", [
            'user_id' => $newUser->id,
            'role' => 'member',
        ])->assertCreated();
    }

    public function test_platform_admin_can_manage_organization_members(): void
    {
        $platformAdmin = User::factory()->create();
        Admin::query()->create([
            'user_id' => $platformAdmin->id,
            'is_active' => true,
        ]);
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Platform Org',
            'slug' => 'platform-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($platformAdmin)->postJson("/api/organizations/{$organization->id}/members", [
            'user_id' => $memberUser->id,
            'role' => 'member',
        ])->assertCreated();
    }

    public function test_member_routes_validate_payload(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Validation Org',
            'slug' => 'validation-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/members", [
            'user_id' => 'invalid',
            'role' => 'super-admin',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'role']);
    }
}
