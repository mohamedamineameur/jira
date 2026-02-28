<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationPlanRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizationService)
    {
        $this->authorizeResource(Organization::class, 'organization');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $organizations = $this->organizationService->paginate($perPage);

        return response()->json($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->create($request->validated());

        return response()->json([
            'data' => $organization->load('owner'),
        ], 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        if ($organization->is_deleted) {
            abort(404);
        }

        return response()->json([
            'data' => $organization->load('owner'),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $updatedOrganization = $this->organizationService->update($organization, $request->validated());

        return response()->json([
            'data' => $updatedOrganization->load('owner'),
        ]);
    }

    public function updatePlan(UpdateOrganizationPlanRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $updatedOrganization = $this->organizationService->updatePlan(
            $organization,
            $request->validated()['plan'],
        );

        return response()->json([
            'data' => $updatedOrganization->load('owner'),
        ]);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $deletedOrganization = $this->organizationService->delete($organization);

        return response()->json([
            'message' => 'Organization deleted successfully.',
            'data' => $deletedOrganization,
        ]);
    }
}
