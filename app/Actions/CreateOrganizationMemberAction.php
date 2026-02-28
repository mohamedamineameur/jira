<?php

namespace App\Actions;

use App\Models\OrganizationMember;

class CreateOrganizationMemberAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): OrganizationMember
    {
        return OrganizationMember::query()->create($data)->refresh();
    }
}
