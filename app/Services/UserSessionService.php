<?php

namespace App\Services;

use App\Actions\CreateUserSessionAction;
use App\Actions\RevokeUserSessionAction;
use App\Models\UserSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserSessionService
{
    public function __construct(
        private readonly CreateUserSessionAction $createUserSessionAction,
        private readonly RevokeUserSessionAction $revokeUserSessionAction,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return UserSession::query()
            ->with('user')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UserSession
    {
        return $this->createUserSessionAction->execute($data);
    }

    public function revoke(UserSession $session): UserSession
    {
        return $this->revokeUserSessionAction->execute($session);
    }
}
