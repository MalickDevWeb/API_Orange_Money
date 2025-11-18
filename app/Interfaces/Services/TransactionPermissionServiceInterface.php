<?php

namespace App\Interfaces\Services;

interface TransactionPermissionServiceInterface
{
    /**
     * Validate transaction permissions
     */
    public function validatePermissions(int $userId, string $transactionType, ?int $receiverId = null): bool;

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(int $userId, float $amount, string $transactionType = 'transfer'): bool;
}
