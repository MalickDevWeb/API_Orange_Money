<?php

namespace App\Interfaces\Services;

interface AdminNotificationServiceInterface
{
    /**
     * Send user notification email
     */
    public function sendUserNotification(\App\Models\User $user, string $type, ?string $motif = null): void;

    /**
     * Send balance request notification email
     */
    public function sendBalanceRequestNotification(\App\Models\User $supplier, string $type, ?float $amount = null, ?string $motif = null): void;

    /**
     * Send admin confirmation email
     */
    public function sendAdminConfirmation(string $action, \App\Models\User $targetUser, ?string $motif = null): void;
}
