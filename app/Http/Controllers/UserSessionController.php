<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserSessionRequest;
use App\Models\UserSession;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSessionController extends Controller
{
    public function __construct(private readonly UserSessionService $userSessionService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $sessions = $this->userSessionService->paginate($perPage);

        return response()->json($sessions);
    }

    public function store(StoreUserSessionRequest $request): JsonResponse
    {
        $session = $this->userSessionService->create($request->validated());

        return response()->json([
            'data' => $session,
            'revoked' => $session->deleted_at !== null,
        ], 201);
    }

    public function show(UserSession $session): JsonResponse
    {
        return response()->json([
            'data' => $session->load('user'),
            'revoked' => $session->deleted_at !== null,
        ]);
    }

    public function destroy(UserSession $session): JsonResponse
    {
        $revokedSession = $this->userSessionService->revoke($session);

        return response()->json([
            'message' => 'Session revoked successfully.',
            'data' => $revokedSession,
            'revoked' => true,
        ]);
    }
}
