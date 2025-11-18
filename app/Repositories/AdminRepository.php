<?php

namespace App\Repositories;

use App\Interfaces\Repositories\AdminRepositoryInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Models\AdminAction;
use App\Models\BalanceRequest;
use App\Models\GlobalFee;
use App\Enums\UserType;
use App\Enums\UserStatus;
use App\Enums\BalanceRequestStatus;
use Illuminate\Support\Facades\DB;

class AdminRepository implements AdminRepositoryInterface
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Find user by telephone
     */
    public function findUserByTelephone(string $telephone): ?User
    {
        return $this->userRepository->findByTelephone($telephone);
    }

    /**
     * Get pending users for approval
     */
    public function getPendingUsers()
    {
        // This method needs to be implemented with a custom query since UserRepository doesn't have this specific method
        return User::whereIn('type', [UserType::COMMERCANT->value, UserType::FOURNISSEUR->value])
                  ->where('statut', UserStatus::EN_ATTENTE->value)
                  ->get();
    }

    /**
     * Update user status
     */
    public function updateUserStatus(string $userId, string $status): bool
    {
        return User::where('id', $userId)->update(['statut' => $status]) > 0;
    }

    /**
     * Update user transfer rights
     */
    public function updateUserTransferRights(string $userId, array $rights): bool
    {
        return User::where('id', $userId)->update($rights) > 0;
    }

    /**
     * Ban user
     */
    public function banUser(string $userId): bool
    {
        return User::where('id', $userId)->update(['statut' => UserStatus::BANNI->value]) > 0;
    }

    /**
     * Unban user
     */
    public function unbanUser(string $userId): bool
    {
        return User::where('id', $userId)->update(['statut' => UserStatus::ACTIF->value]) > 0;
    }

    /**
     * Set user tax
     */
    public function setUserTax(string $userId, float $taxPercentage): bool
    {
        return User::where('id', $userId)->update(['tax_percentage' => $taxPercentage]) > 0;
    }

    /**
     * Get pending balance requests
     */
    public function getPendingBalanceRequests()
    {
        return BalanceRequest::with('supplier')
                            ->where('statut', BalanceRequestStatus::EN_ATTENTE->value)
                            ->get();
    }

    /**
     * Find balance request by supplier telephone
     */
    public function findPendingBalanceRequestByTelephone(string $telephone): ?BalanceRequest
    {
        return BalanceRequest::whereHas('supplier', function($q) use ($telephone) {
            $q->where('telephone', $telephone);
        })->where('statut', BalanceRequestStatus::EN_ATTENTE->value)->first();
    }

    /**
     * Update balance request status
     */
    public function updateBalanceRequestStatus(int $requestId, string $status, ?string $motifRejet = null, ?string $adminId = null): bool
    {
        $data = [
            'statut' => $status,
            'traitee_at' => now()
        ];

        if ($adminId) {
            $data['admin_id'] = $adminId;
        }

        if ($motifRejet) {
            $data['motif_rejet'] = $motifRejet;
        }

        return BalanceRequest::where('id', $requestId)->update($data) > 0;
    }

    /**
     * Get admin actions with filters
     */
    public function getAdminActions(array $filters = [], int $perPage = 15)
    {
        $query = AdminAction::with(['admin', 'targetUser']);

        if (!empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (!empty($filters['target_user_id'])) {
            $query->where('target_user_id', $filters['target_user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Log admin action
     */
    public function logAdminAction(string $adminId, string $actionType, ?string $targetUserId = null, array $details = []): bool
    {
        return AdminAction::create([
            'admin_id' => $adminId,
            'action_type' => $actionType,
            'target_user_id' => $targetUserId,
            'details' => $details,
        ]) instanceof AdminAction;
    }

    /**
     * Get daily statistics
     */
    public function getDailyStatistics(): array
    {
        $today = now()->toDateString();

        return [
            'transfers' => DB::table('transactions')
                ->where('type', 'transfert')
                ->where('statut', 'reussie')
                ->whereDate('date_transaction', $today)
                ->count(),

            'deposits' => DB::table('transactions')
                ->where('type', 'depot')
                ->where('statut', 'reussie')
                ->whereDate('date_transaction', $today)
                ->count(),

            'withdrawals' => DB::table('transactions')
                ->where('type', 'retrait')
                ->where('statut', 'reussie')
                ->whereDate('date_transaction', $today)
                ->count(),

            'merchant_payments' => DB::table('transactions')
                ->where('type', 'paiement')
                ->where('statut', 'reussie')
                ->whereDate('date_transaction', $today)
                ->count(),
        ];
    }

    /**
     * Update global fees
     */
    public function updateGlobalFees(float $transactionFee, float $merchantPercentage): bool
    {
        // Assuming there's a global_fees table with a single record
        return GlobalFee::updateOrCreate(
            ['id' => 1], // Assuming single record
            [
                'transaction_fee' => $transactionFee,
                'merchant_percentage' => $merchantPercentage,
            ]
        ) instanceof GlobalFee;
    }

    /**
     * Get all comptes with filters
     */
    public function getAllComptes(array $filters = [], int $perPage = 15)
    {
        $query = \App\Models\Compte::query();

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['numero_compte'])) {
            $query->where('numero_compte', 'like', '%' . $filters['numero_compte'] . '%');
        }

        if (!empty($filters['titulaire'])) {
            $query->where('titulaire', 'like', '%' . $filters['titulaire'] . '%');
        }

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('numero_compte', 'like', '%' . $searchTerm . '%')
                  ->orWhere('titulaire', 'like', '%' . $searchTerm . '%')
                  ->orWhere('code_marchand', 'like', '%' . $searchTerm . '%');
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        // Only allow sorting by specific fields for security
        $allowedSortFields = ['created_at', 'updated_at', 'numero_compte', 'statut'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Create compte for user
     */
    public function createCompteForUser(string $userId, array $data): ?\App\Models\Compte
    {
        $user = $this->userRepository->findById((int)$userId);
        if (!$user) {
            return null;
        }

        // Check if account name is unique for this user
        $userAccounts = $this->userRepository->getUserAccounts((int)$userId);
        $existingAccount = $userAccounts->where('nom_compte', $data['nom_compte'])->first();
        if ($existingAccount) {
            return null; // Or throw exception
        }

        // Generate account number
        $data['numero_compte'] = 'CMPT-' . strtoupper(uniqid());
        $data['client_id'] = $userId;
        $data['type_compte'] = 'courant';
        $data['devise'] = 'XOF';
        $data['statut'] = 'inactif';
        $data['titulaire'] = $user->nom . ' ' . $user->prenom;
        $data['utilisateur_id'] = $userId;

        return \App\Models\Compte::create($data);
    }
}
