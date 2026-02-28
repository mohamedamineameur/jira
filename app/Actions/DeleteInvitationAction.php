<?php

namespace App\Actions;

use App\Models\Invitation;

class DeleteInvitationAction
{
    public function execute(Invitation $invitation): Invitation
    {
        $invitation->is_deleted = true;
        $invitation->deleted_at = now();
        $invitation->save();

        return $invitation->refresh();
    }
}
