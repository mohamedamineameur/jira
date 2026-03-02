<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationMemberRequest;
use App\Http\Requests\UpdateOrganizationMemberRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    public function __construct(private readonly OrganizationMemberService $organizationMemberService) {}

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('viewMembership', $organization);

        $perPage = (int) $request->integer('per_page', 15);
        $members = $this->organizationMemberService->paginate($organization, $perPage);

        return response()->json($members);
    }

    public function me(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('viewMembership', $organization);

        /** @var User $authUser */
        $authUser = $request->user();

        if ($organization->owner_id === $authUser->id) {
            return response()->json([
                'data' => [
                    'organization_id' => $organization->id,
                    'user_id' => $authUser->id,
                    'role' => 'owner',
                    'joined_at' => null,
                    'is_deleted' => false,
                    'deleted_at' => null,
                ],
            ]);
        }

        $member = $this->organizationMemberService->findByOrganizationAndUser($organization, $authUser);
        if (! $member) {
            abort(404);
        }

        return response()->json([
            'data' => $member->load('user'),
        ]);
    }

    public function store(StoreOrganizationMemberRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $member = $this->organizationMemberService->create($organization, $request->validated());

        return response()->json([
            'data' => $member->load('user'),
        ], 201);
    }

    public function update(
        UpdateOrganizationMemberRequest $request,
        Organization $organization,
        User $user
    ): JsonResponse {
        $this->authorize('manageMembers', $organization);

        $member = $this->organizationMemberService->findByOrganizationAndUser($organization, $user);
        if (! $member) {
            abort(404);
        }

        $updatedMember = $this->organizationMemberService->updateRole($member, $request->validated()['role']);

        return response()->json([
            'data' => $updatedMember->load('user'),
        ]);
    }

    public function destroy(Organization $organization, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $member = $this->organizationMemberService->findByOrganizationAndUser($organization, $user);
        if (! $member) {
            abort(404);
        }

        $deletedMember = $this->organizationMemberService->delete($member);

        return response()->json([
            'message' => 'Organization member removed successfully.',
            'data' => $deletedMember->load('user'),
        ]);
    }
}
