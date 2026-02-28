<?php

namespace App\Actions;

use App\Models\User;

class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password_hash'] = $data['password'];
            unset($data['password']);
        }
        unset($data['password_confirmation']);

        $user->fill($data);
        $user->save();

        return $user->refresh();
    }
}
