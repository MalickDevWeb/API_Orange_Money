<?php

namespace App\Services;

use App\Interfaces\Notifications\SendGridServiceInterface;
use SendGrid;
use SendGrid\Mail\Mail;
use Illuminate\Support\Facades\Log;

class SendGridService implements SendGridServiceInterface
{
    /**
     * Envoyer un email simple
     */
    public function sendEmail(string $to, string $subject, string $content): bool
    {
        try {
            $email = new Mail();
            $email->setFrom(config('mail.from.address'), config('mail.from.name'));
            $email->setSubject($subject);
            $email->addTo($to);
            $email->addContent("text/html", $content);

            $sendgrid = new SendGrid(config('services.sendgrid.api_key'));
            $response = $sendgrid->send($email);

            if ($response->statusCode() == 202) {
                Log::info('Email envoyé via SendGrid API', [
                    'to' => $to,
                    'subject' => $subject,
                ]);
                return true;
            } else {
                Log::error('Erreur envoi email SendGrid API', [
                    'to' => $to,
                    'status' => $response->statusCode(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi email SendGrid API', [
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
        <p>Ce code expire dans 5 minutes.</p>
        <p>Cordialement,<br>L'équipe Orange Money</p>
        ";

        return $this->sendEmail($to, $subject, $htmlContent);
    }
}
