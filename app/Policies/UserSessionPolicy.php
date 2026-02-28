<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSession;

class UserSessionPolicy
{
    public function viewAny(User $authUser): bool
    {
        return ! $authUser->is_deleted;
    }

    public function view(User $authUser, UserSession $session): bool
    {
        return ! $authUser->is_deleted && $session->user_id === $authUser->id;
    }

    public function create(User $authUser): bool
    {
        return ! $authUser->is_deleted;
    }

    public function delete(User $authUser, UserSession $session): bool
    {
        return ! $authUser->is_deleted && $session->user_id === $authUser->id;
    }
}
