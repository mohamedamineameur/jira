<?php

namespace App\Actions;

use App\Models\UserSession;

class RevokeUserSessionAction
{
    public function execute(UserSession $session): UserSession
    {
        $session->delete();

        return $session->fresh(['user']) ?? $session;
    }
}
