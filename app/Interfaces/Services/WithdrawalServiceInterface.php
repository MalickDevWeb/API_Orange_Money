<?php

namespace App\Interfaces\Services;

interface WithdrawalServiceInterface
{
    /**
     * Create a withdrawal transaction
     */
    public function createWithdrawal(array $data, int $userId): array;
}
