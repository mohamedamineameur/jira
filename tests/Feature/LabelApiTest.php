<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Label;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupPath(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Label Org',
            'slug' => 'label-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Label Project',
            'key' => 'LBL',
            'created_by' => $owner->id,
        ]);

        return [$owner, $organization, $project];
    }

    public function test_guest_cannot_access_label_routes(): void
    {
        [$owner, $organization, $project] = $this->setupPath();
        $label = Label::query()->create([
            'project_id' => $project->id,
            'name' => 'Backend',
            'color' => '#111111',
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/labels";

        $this->getJson($base)->assertUnauthorized();
        $this->postJson($base, ['name' => 'Frontend'])->assertUnauthorized();
        $this->getJson("{$base}/{$label->id}")->assertUnauthorized();
        $this->patchJson("{$base}/{$label->id}", ['name' => 'UI'])->assertUnauthorized();
        $this->deleteJson("{$base}/{$label->id}")->assertUnauthorized();
    }

    public function test_owner_can_crud_labels_with_soft_delete(): void
    {
        [$owner, $organization, $project] = $this->setupPath();
        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/labels";

        $createResponse = $this->actingAs($owner)->postJson($base, [
            'name' => 'Backend',
            'color' => '#ff0000',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Backend')
            ->assertJsonPath('data.color', '#ff0000');

        $labelId = $createResponse->json('data.id');

        $this->actingAs($owner)->getJson($base)
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->getJson("{$base}/{$labelId}")
            ->assertOk()
            ->assertJsonPath('data.id', $labelId);

        $this->actingAs($owner)->patchJson("{$base}/{$labelId}", [
            'name' => 'Frontend',
            'color' => '#00ff00',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Frontend')
            ->assertJsonPath('data.color', '#00ff00');

        $this->actingAs($owner)->deleteJson("{$base}/{$labelId}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);
    }

    public function test_non_manager_member_cannot_manage_labels(): void
    {
        [$owner, $organization, $project] = $this->setupPath();
        $member = User::factory()->create();
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/labels";
        $this->actingAs($member)->postJson($base, [
            'name' => 'Nope',
        ])->assertForbidden();
    }

    public function test_org_admin_or_platform_admin_can_manage_labels(): void
    {
        [$owner, $organization, $project] = $this->setupPath();
        $orgAdmin = User::factory()->create();
        $platformAdmin = User::factory()->create();
        Admin::query()->create([
            'user_id' => $platformAdmin->id,
            'is_active' => true,
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $orgAdmin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/labels";
        $this->actingAs($orgAdmin)->postJson($base, [
            'name' => 'Org Admin Label',
        ])->assertCreated();
        $this->actingAs($platformAdmin)->postJson($base, [
            'name' => 'Platform Admin Label',
        ])->assertCreated();
    }

    public function test_label_routes_validate_payload(): void
    {
        [$owner, $organization, $project] = $this->setupPath();
        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/labels";

        $this->actingAs($owner)->postJson($base, [
            'name' => '',
            'color' => str_repeat('a', 60),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'color']);
    }
}
