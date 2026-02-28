<?php

namespace App\Services;

use App\Actions\CreateUserAction;
use App\Actions\DeleteUserAction;
use App\Actions\UpdateUserAction;
use App\Enums\UserAccountState;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly DeleteUserAction $deleteUserAction,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return $this->createUserAction->execute($data);
    }

    public function update(User $user, array $data): User
    {
        return $this->updateUserAction->execute($user, $data);
    }

    public function delete(User $user): User
    {
        return $this->deleteUserAction->execute($user);
    }

    public function state(User $user): UserAccountState
    {
        if ($user->is_deleted) {
            return UserAccountState::DELETED;
        }

        return $user->is_active
            ? UserAccountState::ACTIVE
            : UserAccountState::INACTIVE;
    }
}
