<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private readonly AdminService $adminService)
    {
        $this->authorizeResource(Admin::class, 'admin');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $admins = $this->adminService->paginate($perPage);

        return response()->json($admins);
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->adminService->create($request->validated());

        return response()->json([
            'data' => $admin->load('user'),
        ], 201);
    }

    public function show(Admin $admin): JsonResponse
    {
        return response()->json([
            'data' => $admin->load('user'),
        ]);
    }

    public function update(UpdateAdminRequest $request, Admin $admin): JsonResponse
    {
        $updatedAdmin = $this->adminService->update($admin, $request->validated());

        return response()->json([
            'data' => $updatedAdmin->load('user'),
        ]);
    }

    public function destroy(Admin $admin): JsonResponse
    {
        $this->adminService->delete($admin);

        return response()->json([
            'message' => 'Admin deleted successfully.',
        ]);
    }
}
