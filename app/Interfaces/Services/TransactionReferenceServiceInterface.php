<?php

namespace App\Interfaces\Services;

interface TransactionReferenceServiceInterface
{
    /**
     * Generate transaction reference
     */
    public function generateReference(string $type): string;
}
