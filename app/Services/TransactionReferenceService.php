<?php

namespace App\Services;

use App\Interfaces\Services\TransactionReferenceServiceInterface;
use Illuminate\Support\Str;

class TransactionReferenceService implements TransactionReferenceServiceInterface
{
    /**
     * Generate transaction reference
     */
    public function generateReference(string $type): string
    {
        $prefix = match($type) {
            'depot' => 'DEP',
            'retrait' => 'RET',
            'transfert' => 'TRF',
            'paiement' => 'PAY',
            'achat_virtuel' => 'ACHAT',
            default => 'TXN'
        };

        return $prefix . '-' . strtoupper(Str::random(8));
    }
}
