<?php

namespace App\Services;

use App\Interfaces\Services\TransactionPermissionServiceInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;

class TransactionPermissionService implements TransactionPermissionServiceInterface
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Validate transaction permissions
     */
    public function validatePermissions(int $userId, string $transactionType, ?int $receiverId = null): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user || $user->isBanned()) {
            return false;
        }

        switch ($transactionType) {
            case 'transfert':
                return $user->canTransfer() && $user->canTransferToClient();
            case 'paiement':
                return $user->canTransfer() && $user->canPayMerchant();
            case 'retrait':
                return $user->canTransfer();
            case 'depot':
                return true; // Admin/fournisseur can make deposits
            default:
                return false;
        }
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(int $userId, float $amount, string $transactionType = 'transfer'): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return false;
        }

        $userAccounts = $this->userRepository->getUserAccounts($user->id);
        $compte = $userAccounts->first();

        if (!$compte) {
            return false;
        }

        // Calculate total amount including fees and taxes
        $taxeUtilisateur = $user->tax_percentage > 0 ? $amount * ($user->tax_percentage / 100) : 0;
        $frais = $transactionType === 'paiement' ? $amount * 0.005 : 0; // 0.5% for payments
        $montantTotal = $amount + $frais + $taxeUtilisateur;

        return $compte->solde >= $montantTotal;
    }
}
