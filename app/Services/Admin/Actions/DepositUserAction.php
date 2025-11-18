<?php

namespace App\Services\Admin\Actions;

use App\Interfaces\Services\UserActionInterface;
use App\Models\User;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Enums\UserType;

class DepositUserAction implements UserActionInterface
{
    public function execute(User $user, array $data = []): array
    {
        if ($user->type !== UserType::CLIENT->value) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('deposit', 'Seuls les clients peuvent recevoir des dépôts');
        }

        if (empty($data['montant'])) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('deposit', 'Montant requis pour effectuer un dépôt');
        }

        $compte = $user->comptes->first();
        if (!$compte) {
            throw new \App\Exceptions\Admin\InvalidUserActionException('deposit', 'Aucun compte trouvé pour ce client');
        }

        // Create deposit transaction
        Transaction::create([
            'type' => TransactionType::DEPOT->value,
            'montant' => $data['montant'],
            'reference' => 'DEP-ADMIN-' . strtoupper(uniqid()),
            'statut' => TransactionStatus::REUSSIE->value,
            'note' => $data['note'] ?? 'Dépôt effectué par l\'admin',
            'compte_emetteur_id' => null,
            'compte_recepteur_id' => $compte->id,
            'date_transaction' => now(),
        ]);

        return [
            'user' => $user,
            'message' => 'Dépôt effectué avec succès',
            'action' => 'deposit',
            'montant' => $data['montant']
        ];
    }

    public function getActionName(): string
    {
        return 'deposit';
    }

    public function isValidForUserType(string $userType): bool
    {
        return $userType === 'client';
    }
}
