<?php

namespace App\Interfaces\Services;

interface UserActionServiceInterface
{
    /**
     * Execute user action (approve, reject, suspend, etc.)
     */
    public function executeAction(string $telephone, string $action, array $data = []): array;

    /**
     * Check if action is valid for user type
     */
    public function isValidActionForUser(string $action, string $userType): bool;
}
