<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_ticket_routes(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ticket Org',
            'slug' => 'ticket-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Backlog',
            'key' => 'BLG',
            'created_by' => $owner->id,
        ]);
        $ticket = Ticket::query()->create([
            'project_id' => $project->id,
            'title' => 'T1',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
            'reporter_id' => $owner->id,
        ]);

        $this->getJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets")->assertUnauthorized();
        $this->postJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets", [
            'title' => 'T2',
            'status' => 'todo',
            'priority' => 'high',
            'type' => 'bug',
        ])->assertUnauthorized();
        $this->getJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}")
            ->assertUnauthorized();
        $this->patchJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}", [
            'status' => 'done',
        ])->assertUnauthorized();
        $this->deleteJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}")
            ->assertUnauthorized();
    }

    public function test_owner_can_create_list_show_update_and_soft_delete_tickets(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Owner Ticket Org',
            'slug' => 'owner-ticket-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Kanban',
            'key' => 'KAN',
            'created_by' => $owner->id,
        ]);

        $createResponse = $this->actingAs($owner)->postJson(
            "/api/organizations/{$organization->id}/projects/{$project->id}/tickets",
            [
                'title' => 'Build API',
                'description' => 'Implement endpoint',
                'status' => 'todo',
                'priority' => 'high',
                'type' => 'story',
                'assignee_id' => $assignee->id,
            ]
        );

        $createResponse->assertCreated()
            ->assertJsonPath('data.title', 'Build API')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.type', 'story')
            ->assertJsonPath('data.reporter_id', $owner->id);

        $ticketId = $createResponse->json('data.id');

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets")
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticketId}")
            ->assertOk()
            ->assertJsonPath('data.id', $ticketId);

        $this->actingAs($owner)->patchJson(
            "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticketId}",
            [
                'status' => 'in_progress',
                'priority' => 'critical',
                'type' => 'bug',
            ]
        )->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.priority', 'critical')
            ->assertJsonPath('data.type', 'bug');

        $this->actingAs($owner)->deleteJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticketId}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);
    }

    public function test_non_manager_member_cannot_manage_tickets(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'No Ticket Access Org',
            'slug' => 'no-ticket-access-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Backlog',
            'key' => 'BKL',
            'created_by' => $owner->id,
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)->postJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets", [
            'title' => 'Nope',
            'status' => 'todo',
            'priority' => 'low',
            'type' => 'task',
        ])->assertForbidden();
    }

    public function test_org_admin_or_platform_admin_can_manage_tickets(): void
    {
        $owner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $platformAdmin = User::factory()->create();
        Admin::query()->create([
            'user_id' => $platformAdmin->id,
            'is_active' => true,
        ]);
        $organization = Organization::query()->create([
            'name' => 'Admin Ticket Org',
            'slug' => 'admin-ticket-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Sprint',
            'key' => 'SPR',
            'created_by' => $owner->id,
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $orgAdmin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($orgAdmin)->postJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets", [
            'title' => 'Org admin ticket',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
        ])->assertCreated();

        $this->actingAs($platformAdmin)->postJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets", [
            'title' => 'Platform admin ticket',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
        ])->assertCreated();
    }

    public function test_ticket_routes_validate_payload(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Validation Ticket Org',
            'slug' => 'validation-ticket-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Validation Project',
            'key' => 'VLD',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/projects/{$project->id}/tickets", [
            'title' => '',
            'status' => 'started',
            'priority' => 'urgent',
            'type' => 'epic',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'priority', 'type']);
    }
}
