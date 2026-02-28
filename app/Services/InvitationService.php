<?php

namespace App\Services;

use App\Actions\CreateInvitationAction;
use App\Actions\DeleteInvitationAction;
use App\Actions\UpdateInvitationAction;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(
        private readonly CreateInvitationAction $createInvitationAction,
        private readonly UpdateInvitationAction $updateInvitationAction,
        private readonly DeleteInvitationAction $deleteInvitationAction,
        private readonly OrganizationMemberService $organizationMemberService,
    ) {
    }

    public function paginate(Organization $organization, int $perPage = 15): LengthAwarePaginator
    {
        return Invitation::query()
            ->where('organization_id', $organization->id)
            ->where('is_deleted', false)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Organization $organization, array $data): Invitation
    {
        return $this->createInvitationAction->execute([
            'organization_id' => $organization->id,
            'email' => strtolower((string) $data['email']),
            'role' => $data['role'],
            'token' => $this->uniqueToken(),
            'expires_at' => $data['expires_at'] ?? now()->addDays(7),
        ]);
    }

    public function delete(Invitation $invitation): Invitation
    {
        return $this->deleteInvitationAction->execute($invitation);
    }

    public function accept(User $user, string $token): ?Invitation
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->where('accepted', false)
            ->where('is_deleted', false)
            ->first();

        if (! $invitation) {
            return null;
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return null;
        }

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            return null;
        }

        $organization = Organization::query()->find($invitation->organization_id);
        if (! $organization || $organization->is_deleted) {
            return null;
        }

        $this->organizationMemberService->create($organization, [
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        return $this->updateInvitationAction->execute($invitation, [
            'accepted' => true,
        ]);
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(96);
        } while (Invitation::query()->where('token', $token)->exists());

        return $token;
    }
}
