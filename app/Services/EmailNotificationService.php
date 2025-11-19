<?php

namespace App\Services;

use App\Interfaces\Services\EmailNotificationServiceInterface;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewAccountNotification;

class EmailNotificationService implements EmailNotificationServiceInterface
{
    public function sendAccountCreationNotification(array $accountData): bool
    {
        try {
            Mail::to($accountData['email'])->send(new NewAccountNotification($accountData));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendNewAccountNotification(array $userData, array $accountData): bool
    {
        try {
            Mail::to($userData['email'])->send(new NewAccountNotification($userData, $accountData));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendAccountModificationNotification(array $userData, array $accountData, array $changes): bool
    {
        try {
            // TODO: Créer une classe Mailable pour les modifications de compte
            // Mail::to($userData['email'])->send(new AccountModificationNotification($userData, $accountData, $changes));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendAccountSwitchNotification(array $userData, ?array $oldAccountData, array $newAccountData): bool
    {
        try {
            // TODO: Créer une classe Mailable pour le changement de compte actif
            // Mail::to($userData['email'])->send(new AccountSwitchNotification($userData, $oldAccountData, $newAccountData));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendAccountDeletionNotification(array $userData, array $accountData, string $reason): bool
    {
        try {
            // TODO: Créer une classe Mailable pour la suppression de compte
            // Mail::to($userData['email'])->send(new AccountDeletionNotification($userData, $accountData, $reason));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendAccountRestorationNotification(array $userData, array $accountData): bool
    {
        try {
            // TODO: Créer une classe Mailable pour la restauration de compte
            // Mail::to($userData['email'])->send(new AccountRestorationNotification($userData, $accountData));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
