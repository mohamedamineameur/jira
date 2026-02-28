<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class SessionTokenService
{
    public function createToken(string $sessionId, string $sessionSecret): string
    {
        return Crypt::encryptString(json_encode([
            'sid' => $sessionId,
            'sec' => $sessionSecret,
        ]));
    }

    /**
     * @return array{sid: string, sec: string}|null
     */
    public function parseToken(string $token): ?array
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $sessionId = $payload['sid'] ?? null;
        $sessionSecret = $payload['sec'] ?? null;

        if (! is_string($sessionId) || ! is_string($sessionSecret)) {
            return null;
        }

        return [
            'sid' => $sessionId,
            'sec' => $sessionSecret,
        ];
    }
}
