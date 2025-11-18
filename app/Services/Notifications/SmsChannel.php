<?php

namespace App\Services\Notifications;

use App\Interfaces\Services\NotificationChannelInterface;
use App\Interfaces\Notifications\TwilioServiceInterface;

class SmsChannel implements NotificationChannelInterface
{
    protected TwilioServiceInterface $twilioService;

    public function __construct(TwilioServiceInterface $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Send SMS notification
     */
    public function send(string $destination, string $message): bool
    {
        try {
            return $this->twilioService->sendSms($destination, $message);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi SMS: ' . $e->getMessage(), [
                'destination' => $destination,
                'channel' => 'sms'
            ]);
            return false;
        }
    }

    /**
     * Check if SMS channel is available
     */
    public function isAvailable(): bool
    {
        // Check if Twilio credentials are configured
        return config('services.twilio.sid') &&
               config('services.twilio.token') &&
               config('services.twilio.from');
    }

    /**
     * Get channel name
     */
    public function getName(): string
    {
        return 'sms';
    }
}
