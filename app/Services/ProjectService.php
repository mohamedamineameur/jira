<?php

namespace App\Services;

use App\Actions\CreateProjectAction;
use App\Actions\DeleteProjectAction;
use App\Actions\UpdateProjectAction;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        private readonly CreateProjectAction $createProjectAction,
        private readonly UpdateProjectAction $updateProjectAction,
        private readonly DeleteProjectAction $deleteProjectAction,
    ) {}

    public function paginate(Organization $organization, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->with(['organization', 'creator'])
            ->where('organization_id', $organization->id)
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, string $createdBy): Project
    {
        return $this->createProjectAction->execute([
            ...$data,
            'organization_id' => $organization->id,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        return $this->updateProjectAction->execute($project, $data);
    }

    public function delete(Project $project): Project
    {
        return $this->deleteProjectAction->execute($project);
    }
}
