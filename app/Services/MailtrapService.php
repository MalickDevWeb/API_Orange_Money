<?php

namespace App\Services;

use App\Interfaces\Notifications\EmailServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailtrapService implements EmailServiceInterface
{
    /**
     * Envoyer un email simple
     */
    public function sendEmail(string $to, string $subject, string $content): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('MAILTRAP_API_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post('https://send.api.mailtrap.io/api/send', [
                'to' => [
                    ['email' => $to]
                ],
                'from' => [
                    'email' => config('mail.from.address'),
                    'name' => config('mail.from.name')
                ],
                'subject' => $subject,
                'html' => $content,
            ]);

            if ($response->successful()) {
                Log::info('Email envoyé via Mailtrap API', [
                    'to' => $to,
                    'subject' => $subject,
                    'response' => $response->json(),
                ]);

                return true;
            } else {
                Log::error('Erreur envoi email Mailtrap API', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi email Mailtrap API', [
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
