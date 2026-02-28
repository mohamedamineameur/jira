<?php

namespace App\Actions;

use App\Models\Admin;

class CreateAdminAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): Admin
    {
        return Admin::query()->create($data);
    }
}
