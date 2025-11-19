<?php

namespace App\Interfaces\Services;

interface EmailNotificationServiceInterface
{
    public function sendAccountCreationNotification(array $accountData): bool;
    public function sendNewAccountNotification(array $userData, array $accountData): bool;
    public function sendAccountModificationNotification(array $userData, array $accountData, array $changes): bool;
    public function sendAccountSwitchNotification(array $userData, ?array $oldAccountData, array $newAccountData): bool;
    public function sendAccountDeletionNotification(array $userData, array $accountData, string $reason): bool;
    public function sendAccountRestorationNotification(array $userData, array $accountData): bool;
}
