<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupPath(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Comment Org',
            'slug' => 'comment-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Comment Project',
            'key' => 'CMP',
            'created_by' => $owner->id,
        ]);
        $ticket = Ticket::query()->create([
            'project_id' => $project->id,
            'title' => 'Comment Ticket',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
            'reporter_id' => $owner->id,
        ]);

        return [$owner, $organization, $project, $ticket];
    }

    public function test_guest_cannot_access_comment_routes(): void
    {
        [$owner, $organization, $project, $ticket] = $this->setupPath();
        $comment = Comment::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $owner->id,
            'content' => 'Hello',
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/comments";

        $this->getJson($base)->assertUnauthorized();
        $this->postJson($base, ['content' => 'x'])->assertUnauthorized();
        $this->getJson("{$base}/{$comment->id}")->assertUnauthorized();
        $this->patchJson("{$base}/{$comment->id}", ['content' => 'u'])->assertUnauthorized();
        $this->deleteJson("{$base}/{$comment->id}")->assertUnauthorized();
    }

    public function test_owner_can_crud_comments_with_soft_delete(): void
    {
        [$owner, $organization, $project, $ticket] = $this->setupPath();
        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/comments";

        $createResponse = $this->actingAs($owner)->postJson($base, [
            'content' => 'First comment',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.content', 'First comment')
            ->assertJsonPath('data.author_id', $owner->id);

        $commentId = $createResponse->json('data.id');

        $this->actingAs($owner)->getJson($base)
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->getJson("{$base}/{$commentId}")
            ->assertOk()
            ->assertJsonPath('data.id', $commentId);

        $this->actingAs($owner)->patchJson("{$base}/{$commentId}", [
            'content' => 'Edited comment',
        ])->assertOk()
            ->assertJsonPath('data.content', 'Edited comment');

        $this->actingAs($owner)->deleteJson("{$base}/{$commentId}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);
    }

    public function test_non_manager_member_cannot_manage_comments(): void
    {
        [$owner, $organization, $project, $ticket] = $this->setupPath();
        $member = User::factory()->create();
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/comments";
        $this->actingAs($member)->postJson($base, [
            'content' => 'Nope',
        ])->assertForbidden();
    }

    public function test_org_admin_or_platform_admin_can_manage_comments(): void
    {
        [$owner, $organization, $project, $ticket] = $this->setupPath();
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

        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/comments";
        $this->actingAs($orgAdmin)->postJson($base, [
            'content' => 'Org admin comment',
        ])->assertCreated();
        $this->actingAs($platformAdmin)->postJson($base, [
            'content' => 'Platform admin comment',
        ])->assertCreated();
    }

    public function test_comment_routes_validate_payload(): void
    {
        [$owner, $organization, $project, $ticket] = $this->setupPath();
        $base = "/api/organizations/{$organization->id}/projects/{$project->id}/tickets/{$ticket->id}/comments";

        $this->actingAs($owner)->postJson($base, [
            'content' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }
}
