<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
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

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->update($user, $request->validated());

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
