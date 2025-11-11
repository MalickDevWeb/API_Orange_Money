<?php

namespace App\Interfaces\Notifications;

interface TwilioServiceInterface
{
    /**
     * Envoyer un SMS
     */
    public function sendSms(string $to, string $message): bool;

    /**
     * Envoyer un OTP par SMS
     */
    public function sendOtp(string $phoneNumber, string $otp): bool;

    /**
     * Envoyer une notification de transaction
     */
    public function sendTransactionNotification(string $phoneNumber, array $transactionData): bool;

    /**
     * Vérifier le statut d'un message
     */
    public function checkMessageStatus(string $messageSid): ?string;

    /**
     * Obtenir le solde du compte Twilio
     */
    public function getBalance(): ?float;
}
