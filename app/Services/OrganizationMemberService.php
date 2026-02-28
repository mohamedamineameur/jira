<?php

namespace App\Services;

use App\Actions\CreateOrganizationMemberAction;
use App\Actions\DeleteOrganizationMemberAction;
use App\Actions\UpdateOrganizationMemberAction;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganizationMemberService
{
    public function __construct(
        private readonly CreateOrganizationMemberAction $createOrganizationMemberAction,
        private readonly UpdateOrganizationMemberAction $updateOrganizationMemberAction,
        private readonly DeleteOrganizationMemberAction $deleteOrganizationMemberAction,
    ) {
    }

    public function paginate(Organization $organization, int $perPage = 15): LengthAwarePaginator
    {
        return OrganizationMember::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->where('is_deleted', false)
            ->orderByDesc('joined_at')
            ->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Organization $organization, array $data): OrganizationMember
    {
        $existing = $this->findByOrganizationAndUser($organization, $data['user_id'], withDeleted: true);

        if ($existing) {
            return $this->updateOrganizationMemberAction->execute($existing, [
                'role' => $data['role'],
                'is_deleted' => false,
                'deleted_at' => null,
                'joined_at' => now(),
            ]);
        }

        return $this->createOrganizationMemberAction->execute([
            'organization_id' => $organization->id,
            'user_id' => $data['user_id'],
            'role' => $data['role'],
            'joined_at' => now(),
        ]);
    }

    public function updateRole(OrganizationMember $member, string $role): OrganizationMember
    {
        return $this->updateOrganizationMemberAction->execute($member, [
            'role' => $role,
        ]);
    }

    public function delete(OrganizationMember $member): OrganizationMember
    {
        return $this->deleteOrganizationMemberAction->execute($member);
    }

    public function findByOrganizationAndUser(
        Organization $organization,
        User|string $user,
        bool $withDeleted = false
    ): ?OrganizationMember {
        $userId = $user instanceof User ? $user->id : $user;

        $query = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $userId);

        if (! $withDeleted) {
            $query->where('is_deleted', false);
        }

        return $query->first();
    }
}
