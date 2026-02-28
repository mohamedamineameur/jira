<?php

namespace App\Actions;

use App\Models\OrganizationMember;

class DeleteOrganizationMemberAction
{
    public function execute(OrganizationMember $member): OrganizationMember
    {
        $member->is_deleted = true;
        $member->deleted_at = now();
        $member->save();

        return $member->refresh();
    }
}
