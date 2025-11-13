<?php

namespace App\Services;

use App\Interfaces\Services\OtpServiceInterface;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Mail;

class OtpService implements OtpServiceInterface
{
    /**
     * Générer un code OTP à 6 chiffres et l’envoyer par mail
     */
    public function sendOtp(string $userEmail): int
    {
        // Génère un code OTP à 6 chiffres
        $otp = random_int(100000, 999999);

        // Stocker le code dans la base de données
        // Puisque le modèle OtpCode utilise phone_number, on utilise l'email comme identifiant
        // Supposons que user_id est l'email pour simplifier, ou trouver l'utilisateur par email
        // Ici, on utilise une approche simplifiée
        OtpCode::create([
            'user_id' => 'temp', // À ajuster selon le contexte
            'code' => $otp,
            'phone_number' => $userEmail, // Utiliser email comme phone_number temporairement
            'expires_at' => now()->addMinutes(30),
            'type' => 'email_verification',
        ]);

        // Envoi du mail
        Mail::raw("Votre code de vérification est : {$otp}", function ($message) use ($userEmail) {
            $message->to($userEmail)
                    ->subject('Votre code de vérification');
        });

        return $otp;
    }
}
