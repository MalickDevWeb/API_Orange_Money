<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Enums\UserStatus;

class UnbanUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        $user->update(['statut' => UserStatus::ACTIF->value]);

        return [
            'user' => $user,
            'message' => 'Utilisateur débanni',
            'action' => 'unban'
        ];
    }

    public function getActionName(): string
    {
        return 'unban';
    }

    public function isValidForUserType(string $userType): bool
    {
        return true;
    }
}
