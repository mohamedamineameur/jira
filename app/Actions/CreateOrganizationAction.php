<?php

namespace App\Actions;

use App\Models\Organization;

class CreateOrganizationAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): Organization
    {
        return Organization::query()->create($data)->refresh();
    }
}
