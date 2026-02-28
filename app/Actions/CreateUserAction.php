<?php

namespace App\Actions;

use App\Models\User;

class CreateUserAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): User
    {
        if (isset($data['password'])) {
            $data['password_hash'] = $data['password'];
            unset($data['password']);
        }

        return User::query()->create($data);
    }
}
