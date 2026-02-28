<?php

namespace App\Actions;

use App\Models\Invitation;

class UpdateInvitationAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Invitation $invitation, array $data): Invitation
    {
        $invitation->fill($data);
        $invitation->save();

        return $invitation->refresh();
    }
}
