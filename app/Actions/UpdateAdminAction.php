<?php

namespace App\Actions;

use App\Models\Admin;

class UpdateAdminAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Admin $admin, array $data): Admin
    {
        $admin->fill($data);
        $admin->save();

        return $admin->refresh();
    }
}
