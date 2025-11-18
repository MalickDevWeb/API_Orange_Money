<?php

namespace App\Interfaces\Services;

use App\Models\Transaction;

interface DepositServiceInterface
{
    /**
     * Create a deposit transaction
     */
    public function createDeposit(array $data, int $userId): Transaction;
}
