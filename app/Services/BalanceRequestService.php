<?php

namespace App\Services;

use App\Interfaces\Services\BalanceRequestServiceInterface;
use App\Interfaces\Repositories\AdminRepositoryInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BalanceRequestService implements BalanceRequestServiceInterface
{
    protected AdminRepositoryInterface $adminRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        AdminRepositoryInterface $adminRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->adminRepository = $adminRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Execute balance request action
     */
    public function executeAction(string $telephone, string $action, array $data = []): array
    {
        return DB::transaction(function() use ($telephone, $action, $data) {
            $balanceRequest = $this->adminRepository->findPendingBalanceRequestByTelephone($telephone);

            if (!$balanceRequest) {
                throw new \App\Exceptions\Admin\BalanceRequestNotFoundException($telephone);
            }

            $supplier = $balanceRequest->supplier;

            switch ($action) {
                case 'approve':
                    $this->adminRepository->updateBalanceRequestStatus(
                        $balanceRequest->id,
                        'approuvee',
                        null,
                        Auth::id()
                    );

                    // Create deposit transaction
                    if ($supplier && $supplier->comptes->count() > 0) {
                        $compte = $supplier->comptes->first();
                        Transaction::create([
                            'type' => TransactionType::DEPOT->value,
                            'montant' => $balanceRequest->montant,
                            'reference' => 'DEP-APPROVAL-' . strtoupper(uniqid()),
                            'statut' => TransactionStatus::REUSSIE->value,
                            'note' => 'Approbation de demande de solde',
                            'compte_emetteur_id' => null,
                            'compte_recepteur_id' => $compte->id,
                            'date_transaction' => now(),
                        ]);
                    }

                    $message = 'Demande de solde approuvée';
                    break;

                case 'reject':
                    if (empty($data['motif_rejet'])) {
                        throw new \App\Exceptions\Admin\InvalidUserActionException('reject_balance_request', 'Motif de rejet requis');
                    }

                    $this->adminRepository->updateBalanceRequestStatus(
                        $balanceRequest->id,
                        'rejetee',
                        $data['motif_rejet'],
                        Auth::id()
                    );

                    $message = 'Demande de solde rejetée';
                    break;

                default:
                    throw new \App\Exceptions\Admin\InvalidUserActionException($action, 'Action non valide pour les demandes de solde');
            }

            return [
                'balance_request' => $balanceRequest,
                'message' => $message
            ];
        });
    }

    /**
     * Check if balance request exists and is pending
     */
    public function hasPendingRequest(string $telephone): bool
    {
        $balanceRequest = $this->adminRepository->findPendingBalanceRequestByTelephone($telephone);
        return $balanceRequest !== null;
    }
}
