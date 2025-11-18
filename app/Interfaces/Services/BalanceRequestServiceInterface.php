<?php

namespace App\Interfaces\Services;

interface BalanceRequestServiceInterface
{
    /**
     * Execute balance request action (approve, reject)
     */
    public function executeAction(string $telephone, string $action, array $data = []): array;

    /**
     * Check if balance request exists and is pending
     */
    public function hasPendingRequest(string $telephone): bool;
}
