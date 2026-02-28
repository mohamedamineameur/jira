<?php

namespace App\Actions;

use App\Models\OrganizationMember;

class UpdateOrganizationMemberAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(OrganizationMember $member, array $data): OrganizationMember
    {
        $member->fill($data);
        $member->save();

        return $member->refresh();
    }
}
