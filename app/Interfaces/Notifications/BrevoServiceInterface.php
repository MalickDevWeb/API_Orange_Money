<?php

namespace App\Interfaces\Notifications;

interface BrevoServiceInterface
{
    /**
     * Envoyer un email
     */
    public function sendMail(string $to, string $subject, string $htmlContent): array;

    /**
     * Envoyer un email de campagne
     */
    public function sendCampaignEmail(array $campaignData): array;

    /**
     * Envoyer une campagne immédiatement
     */
    public function sendCampaign(int $campaignId): array;

    /**
     * Envoyer un email de notification de transaction
     */
    public function sendTransactionNotification(string $email, array $transactionData): array;

    /**
     * Envoyer un email de notification de transaction avec modèle Transaction
     */
    public function sendTransactionNotificationWithModel(string $email, \App\Models\Transaction $transaction): array;

    /**
     * Envoyer un email OTP
     */
    public function sendOtpEmail(string $email, string $otp): array;

    /**
     * Envoyer un email simple (compatibilité EmailServiceInterface)
     */
    public function sendEmail(string $to, string $subject, string $content): bool;

    /**
     * Envoyer un email avec pièce jointe (compatibilité EmailServiceInterface)
     */
    public function sendEmailWithAttachment(string $to, string $subject, string $content, ?string $attachmentPath = null): bool;

    /**
     * Envoyer un email OTP (compatibilité EmailServiceInterface)
     */
    public function sendEmailOtp(string $to, string $otpCode, string $userName): bool;
}
