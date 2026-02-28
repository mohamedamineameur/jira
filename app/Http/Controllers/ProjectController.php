<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        $perPage = (int) $request->integer('per_page', 15);
        $projects = $this->projectService->paginate($organization, $perPage);

        return response()->json($projects);
    }

    public function store(StoreProjectRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        /** @var User $authUser */
        $authUser = $request->user();

        $project = $this->projectService->create($organization, $request->validated(), $authUser->id);

        return response()->json([
            'data' => $project->load(['organization', 'creator']),
        ], 201);
    }

    public function show(Organization $organization, Project $project): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if ($project->organization_id !== $organization->id || $project->is_deleted) {
            abort(404);
        }

        return response()->json([
            'data' => $project->load(['organization', 'creator']),
        ]);
    }

    public function update(UpdateProjectRequest $request, Organization $organization, Project $project): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if ($project->organization_id !== $organization->id || $project->is_deleted) {
            abort(404);
        }

        $updatedProject = $this->projectService->update($project, $request->validated());

        return response()->json([
            'data' => $updatedProject->load(['organization', 'creator']),
        ]);
    }

    public function destroy(Organization $organization, Project $project): JsonResponse
    {
        $this->authorize('manageProjects', $organization);

        if ($project->organization_id !== $organization->id || $project->is_deleted) {
            abort(404);
        }

        $deletedProject = $this->projectService->delete($project);

        return response()->json([
            'message' => 'Project deleted successfully.',
            'data' => $deletedProject,
        ]);
    }
}
