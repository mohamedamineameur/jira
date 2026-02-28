<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return ! $authUser->is_deleted;
    }

    public function view(User $authUser, User $user): bool
    {
        return ! $authUser->is_deleted && ! $user->is_deleted;
    }

    public function create(User $authUser): bool
    {
        return ! $authUser->is_deleted;
    }

    public function update(User $authUser, User $user): bool
    {
        return ! $authUser->is_deleted && ! $user->is_deleted;
    }

    public function delete(User $authUser, User $user): bool
    {
        return ! $authUser->is_deleted && ! $user->is_deleted;
    }
}
