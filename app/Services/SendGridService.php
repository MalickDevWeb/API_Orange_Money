<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/*
class SendGridService
{
    /**
     * Envoyer un email OTP
     */
    public function sendEmailOtp(string $to, string $otpCode, string $userName): bool
    {
        try {
            $subject = 'Votre code de vérification Orange Money';
            $htmlContent = "
            <h1>Bonjour {$userName}</h1>
            <p>Votre code de vérification Orange Money est : <strong>{$otpCode}</strong></p>
            <p>Ce code expire dans 5 minutes.</p>
            <p>Cordialement,<br>L'équipe Orange Money</p>
            ";

            Mail::html($htmlContent, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject);
            });

            Log::info('OTP envoyé par email via SendGrid', [
                'to' => $to,
                'otp' => $otpCode,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email SendGrid', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
*/
