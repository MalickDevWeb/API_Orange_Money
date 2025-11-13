<?php

namespace App\Interfaces\Services;

interface FeeServiceInterface
{
    public function updateGlobalFees(float $transactionFee, float $merchantPercentage): bool;
}
