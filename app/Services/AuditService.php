<?php

namespace App\Services;

use App\Interfaces\Services\AuditServiceInterface;
use App\Models\AdminAction;

class AuditService implements AuditServiceInterface
{
    public function logAction(string $adminId, string $actionType, ?string $targetUserId = null, ?array $details = null): void
    {
        AdminAction::create([
            'admin_id' => $adminId,
            'action_type' => $actionType,
            'target_user_id' => $targetUserId,
            'details' => $details,
        ]);
    }
}
