<?php

namespace App\Listeners;

use App\Events\OtpRequested;
use App\Services\BrevoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendOtpEmailListener
{
    protected BrevoService $brevoService;

    public function __construct(BrevoService $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    /**
     * Handle the event.
     */
    public function handle(OtpRequested $event)
    {
        $email = $event->email;

        // Générer un code OTP aléatoire à 6 chiffres
        $otp = random_int(100000, 999999);

        // Enregistrer dans une table otp_codes
        DB::table('otp_codes')->updateOrInsert(
            ['email' => $email],
            [
                'id' => (string) Str::uuid(),
                'user_id' => 'email-otp', // Temporary for email OTP
                'phone_number' => $email, // Use email as phone_number for now
                'code' => $otp,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Envoi du mail via Brevo API
        $subject = 'Votre code de vérification';
        $htmlContent = "<h1>Code de vérification</h1><p>Votre code de vérification est : <strong>{$otp}</strong></p><p>Ce code expire dans 10 minutes.</p>";

        $this->brevoService->sendMail($email, $subject, $htmlContent);
    }
}
