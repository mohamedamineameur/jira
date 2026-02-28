<?php

namespace App\Actions;

use App\Models\UserSession;
use Illuminate\Support\Facades\Hash;

class CreateUserSessionAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): UserSession
    {
        return UserSession::query()->create([
            'user_id' => $data['user_id'],
            'token_hash' => Hash::make($data['token']),
            'ip' => $data['ip'] ?? null,
            'agent' => $data['agent'] ?? null,
            'last_used_at' => now(),
        ]);
    }
}
