<?php

namespace App\Services;

use Twilio\Rest\Client;
use App\Interfaces\Notifications\TwilioServiceInterface;
use Illuminate\Support\Facades\Log;

class TwilioService implements TwilioServiceInterface
{
    protected Client $client;
    protected string $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->fromNumber = config('services.twilio.from');
    }

    /**
     * Envoyer un SMS
     */
    public function sendSms(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message
            ]);

            Log::info('SMS envoyé avec succès', [
                'to' => $to,
                'message' => $message
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS', [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoyer un OTP par SMS
     */
    public function sendOtp(string $phoneNumber, string $otp): bool
    {
        $message = "Votre code de vérification Orange Money est : {$otp}. Ce code expire dans 5 minutes.";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Envoyer une notification de transaction
     */
    public function sendTransactionNotification(string $phoneNumber, array $transactionData): bool
    {
        $message = $this->buildTransactionMessage($transactionData);

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Construire le message de notification de transaction
     */
    private function buildTransactionMessage(array $data): string
    {
        $type = $data['type'] ?? 'transaction';
        $montant = number_format($data['montant'] ?? 0, 0, ',', ' ');
        $reference = $data['reference'] ?? '';

        $messages = [
            'depot' => "Dépôt de {$montant} FCFA effectué avec succès. Référence: {$reference}",
            'retrait' => "Retrait de {$montant} FCFA effectué avec succès. Référence: {$reference}",
            'transfert' => "Transfert de {$montant} FCFA effectué avec succès. Référence: {$reference}",
            'paiement' => "Paiement de {$montant} FCFA effectué avec succès. Référence: {$reference}",
        ];

        return $messages[$type] ?? "Transaction de {$montant} FCFA effectuée. Référence: {$reference}";
    }

    /**
     * Vérifier le statut d'un message
     */
    public function checkMessageStatus(string $messageSid): ?string
    {
        try {
            $message = $this->client->messages($messageSid)->fetch();
            return $message->status;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut du message', [
                'messageSid' => $messageSid,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Obtenir le solde du compte Twilio
     */
    public function getBalance(): ?float
    {
        try {
            $balance = $this->client->balance->fetch();
            return (float) $balance->balance;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du solde Twilio', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
