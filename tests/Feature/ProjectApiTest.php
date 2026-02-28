<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_project_routes(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Project Org',
            'slug' => 'project-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'P1',
            'description' => 'desc',
            'key' => 'P1',
            'created_by' => $owner->id,
        ]);

        $this->getJson("/api/organizations/{$organization->id}/projects")->assertUnauthorized();
        $this->postJson("/api/organizations/{$organization->id}/projects", [
            'name' => 'P2',
            'key' => 'P2',
        ])->assertUnauthorized();
        $this->getJson("/api/organizations/{$organization->id}/projects/{$project->id}")->assertUnauthorized();
        $this->patchJson("/api/organizations/{$organization->id}/projects/{$project->id}", [
            'name' => 'P3',
        ])->assertUnauthorized();
        $this->deleteJson("/api/organizations/{$organization->id}/projects/{$project->id}")
            ->assertUnauthorized();
    }

    public function test_owner_can_create_list_show_update_and_soft_delete_project(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Owner Project Org',
            'slug' => 'owner-project-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $createResponse = $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/projects", [
            'name' => 'Roadmap',
            'description' => 'Initial project',
            'key' => 'RDM',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Roadmap')
            ->assertJsonPath('data.key', 'RDM')
            ->assertJsonPath('data.created_by', $owner->id);

        $projectId = $createResponse->json('data.id');

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/projects")
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/projects/{$projectId}")
            ->assertOk()
            ->assertJsonPath('data.id', $projectId);

        $this->actingAs($owner)->patchJson("/api/organizations/{$organization->id}/projects/{$projectId}", [
            'name' => 'Roadmap v2',
            'description' => 'Updated',
            'key' => 'RDM2',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Roadmap v2')
            ->assertJsonPath('data.key', 'RDM2');

        $this->actingAs($owner)->deleteJson("/api/organizations/{$organization->id}/projects/{$projectId}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);
    }

    public function test_non_manager_member_cannot_manage_projects(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'No Project Access Org',
            'slug' => 'no-project-access-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)->postJson("/api/organizations/{$organization->id}/projects", [
            'name' => 'Nope',
            'key' => 'NOPE',
        ])->assertForbidden();
    }

    public function test_org_admin_or_platform_admin_can_manage_projects(): void
    {
        $owner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $platformAdmin = User::factory()->create();
        Admin::query()->create([
            'user_id' => $platformAdmin->id,
            'is_active' => true,
        ]);
        $organization = Organization::query()->create([
            'name' => 'Admin Project Org',
            'slug' => 'admin-project-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $orgAdmin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($orgAdmin)->postJson("/api/organizations/{$organization->id}/projects", [
            'name' => 'Org Admin Project',
            'key' => 'OAP',
        ])->assertCreated();

        $this->actingAs($platformAdmin)->postJson("/api/organizations/{$organization->id}/projects", [
            'name' => 'Platform Admin Project',
            'key' => 'PAP',
        ])->assertCreated();
    }

    public function test_project_routes_validate_payload(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Validation Project Org',
            'slug' => 'validation-project-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/projects", [
            'name' => '',
            'key' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'key']);
    }
}
