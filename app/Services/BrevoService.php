<?php

namespace App\Services;

use App\Interfaces\Notifications\EmailServiceInterface;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Configuration;
use Illuminate\Support\Facades\Log;

class BrevoService implements EmailServiceInterface
{
    /**
     * Envoyer un email simple
     */
    public function sendEmail(string $to, string $subject, string $content): bool
    {
        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
            $apiInstance = new TransactionalEmailsApi(null, $config);

            $sendSmtpEmail = new SendSmtpEmail([
                'to' => [['email' => $to]],
                'sender' => ['email' => config('mail.from.address'), 'name' => config('mail.from.name')],
                'subject' => $subject,
                'htmlContent' => $content,
            ]);

            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);

            Log::info('Email envoyé via Brevo API', [
                'to' => $to,
                'subject' => $subject,
                'result' => $result,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email Brevo API', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envoyer un email avec pièce jointe
     */
    public function sendEmailWithAttachment(string $to, string $subject, string $content, ?string $attachmentPath = null): bool
    {
        // Implémentation simplifiée sans pièce jointe pour l'instant
        return $this->sendEmail($to, $subject, $content);
    }

    /**
     * Envoyer un email OTP
     */
    public function sendEmailOtp(string $to, string $otpCode, string $userName): bool
    {
        $subject = 'Votre code de vérification Orange Money';
        $htmlContent = "
        <h1>Bonjour {$userName}</h1>
        <p>Votre code de vérification Orange Money est : <strong>{$otpCode}</strong></p>
        <p>Ce code expire dans 30 minutes.</p>
        <p>Cordialement,<br>L'équipe Orange Money</p>
        ";

        return $this->sendEmail($to, $subject, $htmlContent);
    }
}
