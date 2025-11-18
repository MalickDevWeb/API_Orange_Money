<?php

namespace App\Services;

use App\Interfaces\Services\AdminNotificationServiceInterface;
use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\View;

class AdminNotificationService implements AdminNotificationServiceInterface
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    /**
     * Send user notification email
     */
    public function sendUserNotification(\App\Models\User $user, string $type, ?string $motif = null): void
    {
        if (!$user->email) {
            return;
        }

        $subject = $this->getUserNotificationSubject($type, $user);
        $message = $this->getUserNotificationMessage($type, $user, $motif);

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => null,
            'message' => $message,
            'role' => $this->getUserNotificationRole($type)
        ])->render();

        try {
            $this->brevoService->sendMail($user->email, $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info("Email de {$type} envoyé", [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email {$type}", [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send balance request notification email
     */
    public function sendBalanceRequestNotification(\App\Models\User $supplier, string $type, ?float $amount = null, ?string $motif = null): void
    {
        if (!$supplier->email) {
            return;
        }

        if ($type === 'approved') {
            $subject = "Demande de solde approuvée - {$supplier->nom} {$supplier->prenom}";
            $message = "Félicitations ! Votre demande de solde de " . number_format($amount, 0, ',', ' ') . " XOF a été approuvée par l'administrateur. Le montant a été ajouté à votre compte.";
        } else {
            $subject = "Demande de solde rejetée - {$supplier->nom} {$supplier->prenom}";
            $message = "Nous regrettons de vous informer que votre demande de solde a été rejetée par l'administrateur. Motif : {$motif}. Vous pouvez contacter le support pour plus d'informations.";
        }

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => null,
            'message' => $message,
            'role' => 'balance_request_' . $type
        ])->render();

        try {
            $this->brevoService->sendMail($supplier->email, $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info("Email de {$type} de demande de solde envoyé", [
                'supplier_id' => $supplier->id,
                'email' => $supplier->email
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email {$type} demande de solde", [
                'supplier_id' => $supplier->id,
                'email' => $supplier->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send admin confirmation email
     */
    public function sendAdminConfirmation(string $action, \App\Models\User $targetUser, ?string $motif = null): void
    {
        $admin = auth()->user();
        if (!$admin || !$admin->email) {
            return;
        }

        $actionLabels = [
            'approve' => 'approbation',
            'reject' => 'rejet',
            'suspend' => 'suspension',
            'unsuspend' => 'réactivation',
            'ban' => 'bannissement',
            'unban' => 'débannissement',
            'delete' => 'suppression',
            'deposit' => 'dépôt',
            'approve_balance_request' => 'approbation de demande de solde',
            'reject_balance_request' => 'rejet de demande de solde'
        ];

        $subject = ucfirst($actionLabels[$action] ?? $action) . " d'utilisateur - {$targetUser->nom} {$targetUser->prenom}";
        $message = "Vous avez effectué l'action '{$actionLabels[$action]}' sur l'utilisateur {$targetUser->nom} {$targetUser->prenom} ({$targetUser->telephone}).";
        if ($motif) {
            $message .= " Motif : {$motif}.";
        }

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => null,
            'message' => $message,
            'role' => 'admin_confirmation'
        ])->render();

        try {
            $this->brevoService->sendMail($admin->email, $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info("Email de confirmation {$action} envoyé à l'admin", [
                'admin_id' => $admin->id,
                'user_id' => $targetUser->id
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email confirmation admin", [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user notification subject
     */
    private function getUserNotificationSubject(string $type, \App\Models\User $user): string
    {
        return $type === 'approval'
            ? "Inscription approuvée - {$user->nom} {$user->prenom}"
            : "Inscription rejetée - {$user->nom} {$user->prenom}";
    }

    /**
     * Get user notification message
     */
    private function getUserNotificationMessage(string $type, \App\Models\User $user, ?string $motif): string
    {
        if ($type === 'approval') {
            return "Félicitations ! Votre inscription en tant que {$user->type} a été approuvée par l'administrateur. Vous pouvez maintenant accéder à toutes les fonctionnalités de l'application.";
        }

        return "Nous regrettons de vous informer que votre inscription en tant que {$user->type} a été rejetée par l'administrateur. Motif : {$motif}. Vous pouvez contacter le support pour plus d'informations.";
    }

    /**
     * Get user notification role
     */
    private function getUserNotificationRole(string $type): string
    {
        return $type === 'approval' ? 'user_approval' : 'user_rejection';
    }
}
