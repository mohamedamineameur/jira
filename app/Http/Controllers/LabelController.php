<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Models\Label;
use App\Models\Organization;
use App\Models\Project;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function __construct(private readonly LabelService $labelService) {}

    public function index(Request $request, Organization $organization, Project $project): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidProjectPath($organization, $project)) {
            abort(404);
        }

        $perPage = (int) $request->integer('per_page', 15);
        $labels = $this->labelService->paginate($project, $perPage);

        return response()->json($labels);
    }

    public function store(StoreLabelRequest $request, Organization $organization, Project $project): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidProjectPath($organization, $project)) {
            abort(404);
        }

        $label = $this->labelService->create($project, $request->validated());

        return response()->json([
            'data' => $label->load('project'),
        ], 201);
    }

    public function show(Organization $organization, Project $project, Label $label): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidLabelPath($organization, $project, $label)) {
            abort(404);
        }

        return response()->json([
            'data' => $label->load('project'),
        ]);
    }

    public function update(
        UpdateLabelRequest $request,
        Organization $organization,
        Project $project,
        Label $label
    ): JsonResponse {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidLabelPath($organization, $project, $label)) {
            abort(404);
        }

        $updatedLabel = $this->labelService->update($label, $request->validated());

        return response()->json([
            'data' => $updatedLabel->load('project'),
        ]);
    }

    public function destroy(Organization $organization, Project $project, Label $label): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if (! $this->isValidLabelPath($organization, $project, $label)) {
            abort(404);
        }

        $deletedLabel = $this->labelService->delete($label);

        return response()->json([
            'message' => 'Label deleted successfully.',
            'data' => $deletedLabel,
        ]);
    }

    private function isValidProjectPath(Organization $organization, Project $project): bool
    {
        return $project->organization_id === $organization->id && ! $project->is_deleted;
    }

    private function isValidLabelPath(Organization $organization, Project $project, Label $label): bool
    {
        return $this->isValidProjectPath($organization, $project)
            && $label->project_id === $project->id
            && ! $label->is_deleted;
    }
}
