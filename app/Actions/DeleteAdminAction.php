<?php

namespace App\Actions;

use App\Models\Admin;

class DeleteAdminAction
{
    public function execute(Admin $admin): void
    {
        $admin->delete();
    }
}
