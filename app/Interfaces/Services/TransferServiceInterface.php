<?php

namespace App\Interfaces\Services;

use App\Models\Transaction;

interface TransferServiceInterface
{
    /**
     * Create a transfer transaction
     */
    public function createTransfer(array $data, int $userId): Transaction;
}
