<?php

namespace App\Interfaces\Services;

interface AuditServiceInterface
{
    public function logAction(string $adminId, string $actionType, ?string $targetUserId = null, ?array $details = null): void;
}
