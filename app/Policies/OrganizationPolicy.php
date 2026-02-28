<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $authUser): bool
    {
        return ! $authUser->is_deleted && $authUser->is_active;
    }

    public function view(User $authUser, Organization $organization): bool
    {
        return $this->canManage($authUser, $organization);
    }

    public function create(User $authUser): bool
    {
        return ! $authUser->is_deleted && $authUser->is_active;
    }

    public function update(User $authUser, Organization $organization): bool
    {
        return $this->canManage($authUser, $organization);
    }

    public function delete(User $authUser, Organization $organization): bool
    {
        return $this->canManage($authUser, $organization);
    }

    public function manageMembers(User $authUser, Organization $organization): bool
    {
        if ($organization->is_deleted) {
            return false;
        }

        if ($authUser->is_deleted || ! $authUser->is_active) {
            return false;
        }

        if ($authUser->isAdmin() || $organization->owner_id === $authUser->id) {
            return true;
        }

        $membership = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->first();

        if (! $membership) {
            return false;
        }

        return in_array($membership->role, ['owner', 'admin'], true);
    }

    public function viewMembership(User $authUser, Organization $organization): bool
    {
        if ($organization->is_deleted) {
            return false;
        }

        if ($authUser->is_deleted || ! $authUser->is_active) {
            return false;
        }

        if ($organization->owner_id === $authUser->id) {
            return true;
        }

        return OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->exists();
    }

    private function canManage(User $authUser, Organization $organization): bool
    {
        if ($organization->is_deleted) {
            return false;
        }

        if ($authUser->is_deleted || ! $authUser->is_active) {
            return false;
        }

        return $authUser->isAdmin() || $organization->owner_id === $authUser->id;
    }
}
