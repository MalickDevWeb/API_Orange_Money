<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Enums\UserStatus;

class RejectUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        if (!($user->isCommercant() || $user->isFournisseur())) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('reject', 'Type d\'utilisateur non rejetable');
        }

        if (empty($data['motif_rejet'])) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('reject', 'Motif de rejet requis');
        }

        $user->update(['statut' => UserStatus::INACTIF->value]);

        return [
            'user' => $user,
            'message' => 'Utilisateur rejeté',
            'action' => 'reject',
            'motif_rejet' => $data['motif_rejet']
        ];
    }

    public function getActionName(): string
    {
        return 'reject';
    }

    public function isValidForUserType(string $userType): bool
    {
        return in_array($userType, ['commercant', 'fournisseur']);
    }
}
