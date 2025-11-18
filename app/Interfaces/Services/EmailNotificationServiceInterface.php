<?php

namespace App\Interfaces\Services;

interface EmailNotificationServiceInterface
{
    /**
     * Send admin notification for new user registration
     */
    public function sendNewUserNotification(array $userData): void;

    /**
     * Send OTP via email (fallback)
     */
    public function sendOtpEmail(string $email, string $otpCode, string $type): void;

    /**
     * Send login notification email
     */
    public function sendLoginNotification(array $userData): void;

    /**
     * Send registration confirmation email
     */
    public function sendRegistrationConfirmation(array $userData): void;
}
