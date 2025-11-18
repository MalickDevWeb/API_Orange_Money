<?php

namespace App\Services\Notifications;

use App\Interfaces\Services\NotificationChannelInterface;
use App\Interfaces\Notifications\BrevoServiceInterface;

class EmailChannel implements NotificationChannelInterface
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    /**
     * Send email notification
     */
    public function send(string $destination, string $message): bool
    {
        try {
            // For OTP emails, we need to format the message properly
            $subject = 'Code de vérification';
            $result = $this->brevoService->sendMail($destination, $subject, $message);

            // Check if the email was sent successfully
            // Brevo returns an array with 'messageId' on success
            return isset($result['messageId']) || isset($result['id']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email: ' . $e->getMessage(), [
                'destination' => $destination,
                'channel' => 'email'
            ]);
            return false;
        }
    }

    /**
     * Check if email channel is available
     */
    public function isAvailable(): bool
    {
        // Check if Brevo/Mail configuration is available
        return config('mail.default') && config('services.brevo.api_key');
    }

    /**
     * Get channel name
     */
    public function getName(): string
    {
        return 'email';
    }
}
