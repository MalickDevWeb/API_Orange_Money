<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Interfaces\Notifications\TwilioServiceInterface;
use Illuminate\Support\Facades\Log;

class SendOtpSmsListener
{
    protected TwilioServiceInterface $twilioService;

    /**
     * Create the event listener.
     */
    public function __construct(TwilioServiceInterface $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        try {
            // Générer un code OTP à 6 chiffres
            $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Stocker l'OTP dans la base de données
            $event->user->update([
                'otp_code' => $otpCode,
                'otp_expires_at' => now()->addMinutes(5), // Expire dans 5 minutes
                'otp_attempts' => 0,
                'otp_used' => false,
            ]);

            // Envoyer l'OTP par SMS
            $phoneNumber = '+221' . $event->user->telephone; // Format international pour le Sénégal
            $message = "Votre code de vérification Orange Money est : {$otpCode}. Ce code expire dans 5 minutes.";

            $sent = $this->twilioService->sendSms($phoneNumber, $message);

            if ($sent) {
                Log::info('OTP envoyé par SMS après connexion', [
                    'user_id' => $event->user->id,
                    'phone' => $phoneNumber,
                ]);
            } else {
                Log::error('Échec envoi OTP par SMS', [
                    'user_id' => $event->user->id,
                    'phone' => $phoneNumber,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'OTP par SMS', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
