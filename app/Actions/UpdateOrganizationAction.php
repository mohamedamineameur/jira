<?php

namespace App\Actions;

use App\Models\Organization;

class UpdateOrganizationAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Organization $organization, array $data): Organization
    {
        $organization->fill($data);
        $organization->save();

        return $organization->refresh();
    }
}
