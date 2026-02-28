<?php

namespace App\Services;

use App\Actions\CreateOrganizationAction;
use App\Actions\DeleteOrganizationAction;
use App\Actions\UpdateOrganizationAction;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganizationService
{
    public function __construct(
        private readonly CreateOrganizationAction $createOrganizationAction,
        private readonly UpdateOrganizationAction $updateOrganizationAction,
        private readonly DeleteOrganizationAction $deleteOrganizationAction,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Organization::query()
            ->with('owner')
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        return $this->createOrganizationAction->execute($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        return $this->updateOrganizationAction->execute($organization, $data);
    }

    public function updatePlan(Organization $organization, string $plan): Organization
    {
        return $this->updateOrganizationAction->execute($organization, [
            'plan' => $plan,
        ]);
    }

    public function delete(Organization $organization): Organization
    {
        return $this->deleteOrganizationAction->execute($organization);
    }
}
