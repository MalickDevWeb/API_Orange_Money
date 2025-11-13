<?php

namespace App\Services;

use App\Interfaces\Services\FeeServiceInterface;
use App\Models\GlobalFee;

class FeeService implements FeeServiceInterface
{
    public function updateGlobalFees(float $transactionFee, float $merchantPercentage): bool
    {
        $fee = GlobalFee::first();
        if (!$fee) {
            GlobalFee::create([
                'transaction_fee' => $transactionFee,
                'merchant_percentage' => $merchantPercentage,
            ]);
        } else {
            $fee->update([
                'transaction_fee' => $transactionFee,
                'merchant_percentage' => $merchantPercentage,
            ]);
        }
        return true;
    }
}
