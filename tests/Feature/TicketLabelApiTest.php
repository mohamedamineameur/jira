<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Label;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketLabelApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupPath(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'TL Org',
            'slug' => 'tl-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'TL Project',
            'key' => 'TLP',
            'created_by' => $owner->id,
        ]);
        $ticket = Ticket::query()->create([
            'project_id' => $project->id,
            'title' => 'TL Ticket',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
            'reporter_id' => $owner->id,
        ]);
        $label = Label::query()->create([
            'project_id' => $project->id,
            'name' => 'Backend',
            'color' => '#111111',
        ]);

        return [$owner, $organization, $project, $ticket, $label];
    }

    public function test_guest_cannot_access_ticket_label_routes(): void
    {
        [$owner, $organization, $project, $ticket, $label] = $this->setupPath();

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/labels";
        $this->getJson($base)->assertUnauthorized();
        $this->postJson($base, ['label_id' => $label->id])->assertUnauthorized();
        $this->deleteJson("{$base}/{$label->id}")->assertUnauthorized();
    }

    public function test_owner_can_attach_list_and_soft_detach_label(): void
    {
        [$owner, $organization, $project, $ticket, $label] = $this->setupPath();

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/labels";
        $this->actingAs($owner)->postJson($base, [
            'label_id' => $label->id,
        ])->assertCreated()
            ->assertJsonPath('data.label_id', $label->id);

        $this->actingAs($owner)->getJson($base)
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->deleteJson("{$base}/{$label->id}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);
    }

    public function test_non_manager_member_cannot_manage_ticket_labels(): void
    {
        [$owner, $organization, $project, $ticket, $label] = $this->setupPath();
        $member = User::factory()->create();
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/labels";
        $this->actingAs($member)->postJson($base, [
            'label_id' => $label->id,
        ])->assertForbidden();
    }

    public function test_org_admin_or_platform_admin_can_manage_ticket_labels(): void
    {
        [$owner, $organization, $project, $ticket, $label] = $this->setupPath();
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

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/labels";
        $this->actingAs($orgAdmin)->postJson($base, [
            'label_id' => $label->id,
        ])->assertCreated();

        $ticketLabel = TicketLabel::query()
            ->where('ticket_id', $ticket->id)
            ->where('label_id', $label->id)
            ->firstOrFail();
        $ticketLabel->is_deleted = true;
        $ticketLabel->deleted_at = now();
        $ticketLabel->save();

        $this->actingAs($platformAdmin)->postJson($base, [
            'label_id' => $label->id,
        ])->assertCreated()
            ->assertJsonPath('data.is_deleted', false);
    }

    public function test_ticket_label_routes_validate_and_enforce_same_project_label(): void
    {
        [$owner, $organization, $project, $ticket] = $this->setupPath();
        $otherProject = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Other Project',
            'key' => 'OTH',
            'created_by' => $owner->id,
        ]);
        $foreignLabel = Label::query()->create([
            'project_id' => $otherProject->id,
            'name' => 'Foreign',
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/labels";

        $this->actingAs($owner)->postJson($base, [
            'label_id' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['label_id']);

        $this->actingAs($owner)->postJson($base, [
            'label_id' => $foreignLabel->id,
        ])->assertStatus(422);
    }
}
