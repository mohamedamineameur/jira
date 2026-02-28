<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_audit_logs(): void
    {
        $this->getJson('/api/audit-logs')->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_audit_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/audit-logs')->assertForbidden();
    }

    public function test_admin_can_list_and_show_audit_logs(): void
    {
        $adminUser = User::factory()->create();
        Admin::query()->create([
            'user_id' => $adminUser->id,
            'is_active' => true,
        ]);

        $log = AuditLog::query()->create([
            'entity_type' => 'organization',
            'entity_id' => (string) fake()->uuid(),
            'action' => 'post',
            'performed_by' => $adminUser->id,
            'before' => ['a' => 'b'],
            'after' => ['x' => 'y'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($adminUser)->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);

        $this->actingAs($adminUser)->getJson("/api/audit-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.entity_type', 'organization');
    }

    public function test_interceptor_logs_relevant_mutation_actions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/organizations', [
            'name' => 'Audit Org',
            'slug' => 'audit-org',
            'owner_id' => $user->id,
            'plan' => 'free',
        ]);

        $response->assertCreated();
        $organizationId = $response->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'organization',
            'entity_id' => $organizationId,
            'action' => 'post',
            'performed_by' => $user->id,
            'is_deleted' => false,
        ]);
    }
}
