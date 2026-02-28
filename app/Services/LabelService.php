<?php

namespace App\Services;

use App\Actions\CreateLabelAction;
use App\Actions\DeleteLabelAction;
use App\Actions\UpdateLabelAction;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LabelService
{
    public function __construct(
        private readonly CreateLabelAction $createLabelAction,
        private readonly UpdateLabelAction $updateLabelAction,
        private readonly DeleteLabelAction $deleteLabelAction,
    ) {
    }

    public function paginate(Project $project, int $perPage = 15): LengthAwarePaginator
    {
        return Label::query()
            ->with('project')
            ->where('project_id', $project->id)
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Project $project, array $data): Label
    {
        return $this->createLabelAction->execute([
            ...$data,
            'project_id' => $project->id,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Label $label, array $data): Label
    {
        return $this->updateLabelAction->execute($label, $data);
    }

    public function delete(Label $label): Label
    {
        return $this->deleteLabelAction->execute($label);
    }
}
