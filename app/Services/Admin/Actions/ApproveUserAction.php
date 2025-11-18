<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Enums\UserStatus;

class ApproveUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        if (!($user->isCommercant() || $user->isFournisseur())) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('approve', 'Type d\'utilisateur non approuvable');
        }

        if ($user->statut === UserStatus::ACTIF->value) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('approve', 'Utilisateur déjà approuvé');
        }

        $user->update(['statut' => UserStatus::ACTIF->value]);

        return [
            'user' => $user,
            'message' => 'Utilisateur approuvé avec succès',
            'action' => 'approve'
        ];
    }

    public function getActionName(): string
    {
        return 'approve';
    }

    public function isValidForUserType(string $userType): bool
    {
        return in_array($userType, ['commercant', 'fournisseur']);
    }
}
