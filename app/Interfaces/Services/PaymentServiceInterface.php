<?php

namespace App\Interfaces\Services;

use App\Models\Transaction;

interface PaymentServiceInterface
{
    /**
     * Create a payment transaction
     */
    public function createPayment(array $data, int $userId): Transaction;
}
