<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Enums\UserStatus;

class UnsuspendUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        if ($user->statut !== UserStatus::SUSPENDU->value) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('unsuspend', 'Utilisateur n\'est pas suspendu');
        }

        $user->update(['statut' => UserStatus::ACTIF->value]);

        return [
            'user' => $user,
            'message' => 'Utilisateur réactivé',
            'action' => 'unsuspend'
        ];
    }

    public function getActionName(): string
    {
        return 'unsuspend';
    }

    public function isValidForUserType(string $userType): bool
    {
        return true;
    }
}
