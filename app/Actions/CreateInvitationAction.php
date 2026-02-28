<?php

namespace App\Actions;

use App\Models\Invitation;

class CreateInvitationAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Invitation
    {
        return Invitation::query()->create($data)->refresh();
    }
}
