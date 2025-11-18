<?php

namespace App\Services;

use App\Interfaces\Services\TransactionEmailServiceInterface;
use App\Interfaces\Notifications\BrevoServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\View;

class TransactionEmailService implements TransactionEmailServiceInterface
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    /**
     * Send transaction success emails
     */
    public function sendSuccessEmails(int $senderId, int $receiverId, $transaction, string $type): void
    {
        try {
            $sender = User::find($senderId);
            $receiver = User::find($receiverId);

            // Email to receiver
            if ($receiver && $receiver->email) {
                $newBalance = $receiver->comptes->first()->solde ?? 0;
                $subject = "Transaction reçue - {$transaction->reference}";
                $message = "Vous avez reçu une transaction de {$transaction->montant_signe} FCFA. Nouveau solde : {$newBalance} FCFA.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'receiver'
                ])->render();
                $this->brevoService->sendMail($receiver->email, $subject, $htmlContent);
            }

            // Email to sender
            if ($sender && $sender->email) {
                $currentBalance = $sender->comptes->first()->solde ?? 0;
                $subject = "Transaction effectuée - {$transaction->reference}";
                $message = "Votre transaction de {$transaction->montant_signe} FCFA a été effectuée avec succès. Solde actuel : {$currentBalance} FCFA.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'sender'
                ])->render();
                $this->brevoService->sendMail($sender->email, $subject, $htmlContent);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi d\'emails de succès: ' . $e->getMessage());
        }
    }

    /**
     * Send transaction error emails
     */
    public function sendErrorEmails(int $senderId, int $receiverId, float $amount, string $error): void
    {
        try {
            $sender = User::find($senderId);
            $receiver = User::find($receiverId);

            // Email to sender
            if ($sender && $sender->email) {
                $subject = "Échec de transaction";
                $message = "Votre tentative de transaction de {$amount} FCFA a échoué : {$error}.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'error'
                ])->render();
                $this->brevoService->sendMail($sender->email, $subject, $htmlContent);
            }

            // Email to receiver
            if ($receiver && $receiver->email && $receiverId !== $senderId) {
                $subject = "Notification d'échec de transaction";
                $message = "Une transaction de {$amount} FCFA initiée vers vous a échoué : {$error}.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'error'
                ])->render();
                $this->brevoService->sendMail($receiver->email, $subject, $htmlContent);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi d\'emails d\'erreur: ' . $e->getMessage());
        }
    }

    /**
     * Send withdrawal confirmation emails
     */
    public function sendWithdrawalConfirmationEmails($transaction, int $supplierId): void
    {
        try {
            $supplier = User::find($supplierId);

            if ($transaction->compteEmetteur && $transaction->compteEmetteur->utilisateur) {
                $client = $transaction->compteEmetteur->utilisateur;
                if ($client->email) {
                    $soldeActuel = $transaction->compteEmetteur->solde;
                    $subject = "Confirmation de retrait - {$transaction->reference}";
                    $message = "Votre retrait de {$transaction->montant_signe} FCFA a été confirmé avec succès. Solde actuel : {$soldeActuel} FCFA.";
                    $htmlContent = View::make('emails.transaction-notification', [
                        'transaction' => $transaction,
                        'message' => $message,
                        'role' => 'sender'
                    ])->render();
                    $this->brevoService->sendMail($client->email, $subject, $htmlContent);
                }
            }

            if ($supplier && $supplier->email) {
                $subject = "Confirmation de retrait effectué - {$transaction->reference}";
                $message = "Le retrait de {$transaction->montant_signe} FCFA a été confirmé avec succès.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => $transaction,
                    'message' => $message,
                    'role' => 'supplier'
                ])->render();
                $this->brevoService->sendMail($supplier->email, $subject, $htmlContent);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi d\'emails de confirmation: ' . $e->getMessage());
        }
    }
}
