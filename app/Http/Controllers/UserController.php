<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserByAdminRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $users = $this->userService->paginate($perPage);

        return response()->json($users);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'data' => $user,
            'state' => $this->userService->state($user)->value,
            'is_admin' => $user->isAdmin(),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'data' => $user,
            'state' => $this->userService->state($user)->value,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user,
            'state' => $this->userService->state($user)->value,
        ]);
    }

    public function updateProfile(UpdateUserProfileRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateProfile($user, $request->validated());

        return response()->json([
            'data' => $updatedUser,
            'state' => $this->userService->state($updatedUser)->value,
        ]);
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updatePassword($user, $request->validated()['password']);

        return response()->json([
            'data' => $updatedUser,
            'state' => $this->userService->state($updatedUser)->value,
        ]);
    }

    public function updateByAdmin(UpdateUserByAdminRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateByAdmin($user, $request->validated());

        return response()->json([
            'data' => $updatedUser,
            'state' => $this->userService->state($updatedUser)->value,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $deletedUser = $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
            'data' => $deletedUser,
            'state' => $this->userService->state($deletedUser)->value,
        ]);
    }
}
