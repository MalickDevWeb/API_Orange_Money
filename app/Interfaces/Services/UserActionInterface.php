<?php

namespace App\Interfaces\Services;

interface UserActionInterface
{
    /**
     * Execute the user action
     */
    public function execute(\App\Models\User $user, array $data = []): array;

    /**
     * Get action name
     */
    public function getActionName(): string;

    /**
     * Check if action is valid for user type
     */
    public function isValidForUserType(string $userType): bool;
}
