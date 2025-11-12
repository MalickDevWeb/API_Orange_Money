<?php

namespace App\Interfaces\Notifications;

interface EmailServiceInterface
{
    /**
     * Envoyer un email simple
     *
     * @param string $to        Adresse email du destinataire
     * @param string $subject   Sujet de l'email
     * @param string $content   Contenu HTML de l'email
     * @return bool             True si succès, False sinon
     */
    public function sendEmail(string $to, string $subject, string $content): bool;

    /**
     * Envoyer un email avec pièce jointe (optionnel)
     *
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param string|null $attachmentPath
     * @return bool
     */
    public function sendEmailWithAttachment(string $to, string $subject, string $content, ?string $attachmentPath = null): bool;

    /**
     * Envoyer un email OTP
     *
     * @param string $to        Adresse email du destinataire
     * @param string $otpCode   Code OTP
     * @param string $userName  Nom de l'utilisateur
     * @return bool             True si succès, False sinon
     */
    public function sendEmailOtp(string $to, string $otpCode, string $userName): bool;
}
