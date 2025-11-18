<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;

class DeleteUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        $user->delete(); // Soft delete

        return [
            'user' => $user,
            'message' => 'Utilisateur supprimé',
            'action' => 'delete'
        ];
    }

    public function getActionName(): string
    {
        return 'delete';
    }

    public function isValidForUserType(string $userType): bool
    {
        return true;
    }
}
