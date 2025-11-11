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
            // Créer un code OTP dans la table otp_codes
            $otp = \App\Models\OtpCode::createForUser(
                $event->user->id,
                $event->user->telephone,
                'login'
            );

            // Envoyer l'OTP par SMS
            $phoneNumber = '+221' . $event->user->telephone; // Format international pour le Sénégal
            $smsSent = $this->twilioService->sendOtp($phoneNumber, $otp->code);

            if ($smsSent) {
                Log::info('OTP envoyé par SMS après connexion', [
                    'user_id' => $event->user->id,
                    'phone' => $phoneNumber,
                    'otp_id' => $otp->id,
                ]);
            } else {
                Log::error('Échec envoi OTP par SMS', [
                    'user_id' => $event->user->id,
                    'phone' => $phoneNumber,
                    'otp_id' => $otp->id,
                ]);
            }

            // OTP par email désactivé - seulement SMS
            /*
            // Envoyer l'OTP par email
            $userName = $event->user->nom . ' ' . $event->user->prenom;
            $emailSent = $this->sendGridService->sendEmailOtp($event->user->email, $otp->code, $userName);

            if ($emailSent) {
                Log::info('OTP envoyé par email après connexion', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'otp_id' => $otp->id,
                ]);
            } else {
                Log::error('Échec envoi OTP par email', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'otp_id' => $otp->id,
                ]);
            }
            */

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'OTP', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
