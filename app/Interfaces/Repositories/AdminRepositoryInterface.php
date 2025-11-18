<?php

namespace App\Interfaces\Repositories;

interface AdminRepositoryInterface
{
    /**
     * Find user by telephone
     */
    public function findUserByTelephone(string $telephone): ?\App\Models\User;

    /**
     * Get pending users for approval
     */
    public function getPendingUsers();

    /**
     * Update user status
     */
    public function updateUserStatus(string $userId, string $status): bool;

    /**
     * Update user transfer rights
     */
    public function updateUserTransferRights(string $userId, array $rights): bool;

    /**
     * Ban user
     */
    public function banUser(string $userId): bool;

    /**
     * Unban user
     */
    public function unbanUser(string $userId): bool;

    /**
     * Set user tax
     */
    public function setUserTax(string $userId, float $taxPercentage): bool;

    /**
     * Get pending balance requests
     */
    public function getPendingBalanceRequests();

    /**
     * Find balance request by supplier telephone
     */
    public function findPendingBalanceRequestByTelephone(string $telephone): ?\App\Models\BalanceRequest;

    /**
     * Update balance request status
     */
    public function updateBalanceRequestStatus(int $requestId, string $status, ?string $motifRejet = null, ?string $adminId = null): bool;

    /**
     * Get admin actions with filters
     */
    public function getAdminActions(array $filters = [], int $perPage = 15);

    /**
     * Log admin action
     */
    public function logAdminAction(string $adminId, string $actionType, ?string $targetUserId = null, array $details = []): bool;

    /**
     * Get daily statistics
     */
    public function getDailyStatistics(): array;

    /**
     * Update global fees
     */
    public function updateGlobalFees(float $transactionFee, float $merchantPercentage): bool;

    /**
     * Get all comptes with filters
     */
    public function getAllComptes(array $filters = [], int $perPage = 15);

    /**
     * Create compte for user
     */
    public function createCompteForUser(string $userId, array $data): ?\App\Models\Compte;
}
