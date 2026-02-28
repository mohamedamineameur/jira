<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitationService)
    {
    }

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $perPage = (int) $request->integer('per_page', 15);
        $invitations = $this->invitationService->paginate($organization, $perPage);

        return response()->json($invitations);
    }

    public function store(StoreInvitationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $invitation = $this->invitationService->create($organization, $request->validated());

        return response()->json([
            'data' => $invitation,
        ], 201);
    }

    public function destroy(Organization $organization, Invitation $invitation): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        if ($invitation->organization_id !== $organization->id) {
            abort(404);
        }

        $deletedInvitation = $this->invitationService->delete($invitation);

        return response()->json([
            'message' => 'Invitation deleted successfully.',
            'data' => $deletedInvitation,
        ]);
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $acceptedInvitation = $this->invitationService->accept($user, $request->validated()['token']);
        if (! $acceptedInvitation) {
            return response()->json([
                'message' => 'Invitation is invalid or expired.',
            ], 422);
        }

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'data' => $acceptedInvitation,
        ]);
    }
}
