<?php

namespace App\Interfaces\Services;

interface AuditServiceInterface
{
    public function logAction(int $adminId, string $actionType, ?int $targetUserId = null, ?array $details = null): void;
}
