<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class AdminPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $this->canManageAdmins($authUser);
    }

    public function view(User $authUser, Admin $admin): bool
    {
        return $this->canManageAdmins($authUser);
    }

    public function create(User $authUser): bool
    {
        return $this->canManageAdmins($authUser);
    }

    public function update(User $authUser, Admin $admin): bool
    {
        return $this->canManageAdmins($authUser);
    }

    public function delete(User $authUser, Admin $admin): bool
    {
        return $this->canManageAdmins($authUser);
    }

    private function canManageAdmins(User $authUser): bool
    {
        return ! $authUser->is_deleted && $authUser->isAdmin();
    }
}
