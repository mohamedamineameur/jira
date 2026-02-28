<?php

namespace App\Services;

use App\Actions\CreateAdminAction;
use App\Actions\DeleteAdminAction;
use App\Actions\UpdateAdminAction;
use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminService
{
    public function __construct(
        private readonly CreateAdminAction $createAdminAction,
        private readonly UpdateAdminAction $updateAdminAction,
        private readonly DeleteAdminAction $deleteAdminAction,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Admin::query()
            ->with('user')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Admin
    {
        return $this->createAdminAction->execute($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Admin $admin, array $data): Admin
    {
        return $this->updateAdminAction->execute($admin, $data);
    }

    public function delete(Admin $admin): void
    {
        $this->deleteAdminAction->execute($admin);
    }
}
