<?php

namespace App\Interfaces\Services;

interface UnifiedTransactionServiceInterface
{
    /**
     * Create a unified transaction (auto-detects type)
     */
    public function createUnifiedTransaction(array $data, int $userId): array;
}
