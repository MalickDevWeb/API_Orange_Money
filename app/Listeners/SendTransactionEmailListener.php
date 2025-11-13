<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class SendTransactionEmailListener
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        try {
            // Envoyer email à l'émetteur
            if ($transaction->compteEmetteur && $transaction->compteEmetteur->utilisateur) {
                $this->sendEmailToUser(
                    $transaction->compteEmetteur->utilisateur,
                    $transaction,
                    'sender'
                );
            }

            // Envoyer email au récepteur (sauf pour les retraits)
            if ($transaction->compteRecepteur &&
                $transaction->compteRecepteur->utilisateur &&
                $transaction->type !== 'retrait') {
                $this->sendEmailToUser(
                    $transaction->compteRecepteur->utilisateur,
                    $transaction,
                    'receiver'
                );
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi d\'email de transaction: ' . $e->getMessage());
        }
    }

    protected function sendEmailToUser($user, $transaction, $role): void
    {
        if (!$user->email) {
            return;
        }

        $subject = $this->getEmailSubject($transaction, $role);
        $message = $this->getEmailMessage($transaction, $role);

        $htmlContent = View::make('emails.transaction-notification', [
            'transaction' => $transaction,
            'message' => $message,
            'role' => $role
        ])->render();

        $this->brevoService->sendMail($user->email, $subject, $htmlContent);
    }

    protected function getEmailSubject($transaction, $role): string
    {
        $typeLabels = [
            'depot' => 'Dépôt',
            'retrait' => 'Retrait',
            'transfert' => 'Transfert',
            'paiement' => 'Paiement',
            'achat_virtuel' => 'Achat virtuel'
        ];

        $type = $typeLabels[$transaction->type] ?? 'Transaction';

        if ($role === 'sender') {
            return "Confirmation de {$type} - {$transaction->reference}";
        } else {
            return "Notification de {$type} - {$transaction->reference}";
        }
    }

    protected function getEmailMessage($transaction, $role): string
    {
        $typeLabels = [
            'depot' => 'dépôt',
            'retrait' => 'retrait',
            'transfert' => 'transfert',
            'paiement' => 'paiement',
            'achat_virtuel' => 'achat virtuel'
        ];

        $type = $typeLabels[$transaction->type] ?? 'transaction';

        if ($role === 'sender') {
            return "Votre {$type} a été effectué avec succès.";
        } else {
            return "Vous avez reçu un {$type}.";
        }
    }

}
