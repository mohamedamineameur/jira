<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_invitation_routes(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Org Invite',
            'slug' => 'org-invite',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->getJson("/api/organizations/{$organization->id}/invitations")->assertUnauthorized();
        $this->postJson("/api/organizations/{$organization->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ])->assertUnauthorized();
        $this->postJson('/api/invitations/accept', [
            'token' => 'xxx',
        ])->assertUnauthorized();
    }

    public function test_owner_can_create_and_list_and_delete_invitations(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Owner Invite Org',
            'slug' => 'owner-invite-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $createResponse = $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.email', 'member@example.com')
            ->assertJsonPath('data.role', 'member')
            ->assertJsonPath('data.accepted', false);

        $invitationId = $createResponse->json('data.id');

        $this->actingAs($owner)->getJson("/api/organizations/{$organization->id}/invitations")
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($owner)->deleteJson("/api/organizations/{$organization->id}/invitations/{$invitationId}")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', true);
    }

    public function test_non_manager_cannot_manage_invitations(): void
    {
        $owner = User::factory()->create();
        $normalMember = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Private Invite Org',
            'slug' => 'private-invite-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $normalMember->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($normalMember)->postJson("/api/organizations/{$organization->id}/invitations", [
            'email' => 'x@example.com',
            'role' => 'member',
        ])->assertForbidden();
    }

    public function test_org_admin_or_platform_admin_can_manage_invitations(): void
    {
        $owner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $platformAdmin = User::factory()->create();
        Admin::query()->create([
            'user_id' => $platformAdmin->id,
            'is_active' => true,
        ]);

        $organization = Organization::query()->create([
            'name' => 'Admin Invite Org',
            'slug' => 'admin-invite-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $orgAdmin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($orgAdmin)->postJson("/api/organizations/{$organization->id}/invitations", [
            'email' => 'member1@example.com',
            'role' => 'member',
        ])->assertCreated();

        $this->actingAs($platformAdmin)->postJson("/api/organizations/{$organization->id}/invitations", [
            'email' => 'member2@example.com',
            'role' => 'member',
        ])->assertCreated();
    }

    public function test_invitation_routes_validate_payload(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Validation Invite Org',
            'slug' => 'validation-invite-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        $this->actingAs($owner)->postJson("/api/organizations/{$organization->id}/invitations", [
            'email' => 'not-an-email',
            'role' => 'owner',
            'expires_at' => 'bad-date',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'role', 'expires_at']);
    }

    public function test_user_can_accept_valid_invitation_and_become_member(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create([
            'email' => 'invited@example.com',
        ]);
        $organization = Organization::query()->create([
            'name' => 'Accept Org',
            'slug' => 'accept-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);
        $invitation = Invitation::query()->create([
            'organization_id' => $organization->id,
            'email' => 'invited@example.com',
            'role' => 'member',
            'token' => 'valid-token-1',
            'expires_at' => now()->addDay(),
            'accepted' => false,
        ]);

        $response = $this->actingAs($invitedUser)->postJson('/api/invitations/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.accepted', true);

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $invitedUser->id,
            'role' => 'member',
            'is_deleted' => false,
        ]);
    }

    public function test_invitation_accept_fails_for_invalid_token_or_email_or_expired(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $organization = Organization::query()->create([
            'name' => 'Invalid Org',
            'slug' => 'invalid-org',
            'owner_id' => $owner->id,
            'plan' => 'free',
        ]);

        Invitation::query()->create([
            'organization_id' => $organization->id,
            'email' => 'other@example.com',
            'role' => 'member',
            'token' => 'wrong-email-token',
            'expires_at' => now()->addDay(),
            'accepted' => false,
        ]);
        Invitation::query()->create([
            'organization_id' => $organization->id,
            'email' => 'user@example.com',
            'role' => 'member',
            'token' => 'expired-token',
            'expires_at' => now()->subMinute(),
            'accepted' => false,
        ]);

        $this->actingAs($user)->postJson('/api/invitations/accept', [
            'token' => 'unknown-token',
        ])->assertUnprocessable();

        $this->actingAs($user)->postJson('/api/invitations/accept', [
            'token' => 'wrong-email-token',
        ])->assertUnprocessable();

        $this->actingAs($user)->postJson('/api/invitations/accept', [
            'token' => 'expired-token',
        ])->assertUnprocessable();
    }
}
