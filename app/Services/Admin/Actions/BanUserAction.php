<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Enums\UserStatus;

class BanUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        $user->update(['statut' => UserStatus::BANNI->value]);

        return [
            'user' => $user,
            'message' => 'Utilisateur banni',
            'action' => 'ban'
        ];
    }

    public function getActionName(): string
    {
        return 'ban';
    }

    public function isValidForUserType(string $userType): bool
    {
        return true;
    }
}
