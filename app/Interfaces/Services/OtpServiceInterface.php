<?php

namespace App\Interfaces\Services;

interface OtpServiceInterface
{
    /**
     * Générer un code OTP à 6 chiffres et l’envoyer par mail
     */
    public function sendOtp(string $userEmail): int;
}
