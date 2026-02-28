<?php

namespace App\Actions;

use App\Models\User;

class DeleteUserAction
{
    public function execute(User $user): User
    {
        $user->is_deleted = true;
        $user->deleted_at = now();
        $user->save();

        return $user->refresh();
    }
}
