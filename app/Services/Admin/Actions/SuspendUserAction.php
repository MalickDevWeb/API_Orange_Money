<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Enums\UserStatus;

class SuspendUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        if ($user->statut === UserStatus::SUSPENDU->value) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('suspend', 'Utilisateur déjà suspendu');
        }

        $user->update(['statut' => UserStatus::SUSPENDU->value]);

        return [
            'user' => $user,
            'message' => 'Utilisateur suspendu',
            'action' => 'suspend'
        ];
    }

    public function getActionName(): string
    {
        return 'suspend';
    }

    public function isValidForUserType(string $userType): bool
    {
        return true; // All user types can be suspended
    }
}
