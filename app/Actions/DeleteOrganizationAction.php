<?php

namespace App\Actions;

use App\Models\Organization;

class DeleteOrganizationAction
{
    public function execute(Organization $organization): Organization
    {
        $organization->is_deleted = true;
        $organization->deleted_at = now();
        $organization->save();

        return $organization->refresh();
    }
}
