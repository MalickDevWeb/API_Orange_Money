<?php

namespace App\Listeners;

use App\Events\NotificationTestRequested;
use App\Interfaces\Notifications\BrevoServiceInterface;
use App\Interfaces\Notifications\TwilioServiceInterface;
use Illuminate\Support\Facades\Log;

class HandleNotificationTest
{
    protected BrevoServiceInterface $brevoService;
    protected TwilioServiceInterface $twilioService;

    /**
     * Create the event listener.
     */
    public function __construct(BrevoServiceInterface $brevoService, TwilioServiceInterface $twilioService)
    {
        $this->brevoService = $brevoService;
        $this->twilioService = $twilioService;
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationTestRequested $event): void
    {
        try {
            switch ($event->type) {
                case 'transaction_email':
                    $this->handleTransactionEmailTest($event->data);
                    break;

                case 'otp':
                    $this->handleOtpTest($event->data);
                    break;

                default:
                    Log::warning('Unknown notification test type', [
                        'type' => $event->type,
                        'data' => $event->data
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling notification test', [
                'type' => $event->type,
                'error' => $e->getMessage(),
                'data' => $event->data
            ]);
        }
    }

    /**
     * Handle transaction email test
     */
    protected function handleTransactionEmailTest(array $data): void
    {
        $email = $data['email'] ?? 'test@example.com';

        // Create mock transaction data
        $transactionData = [
            'type' => 'transfert',
            'montant' => 50000,
            'reference' => 'TEST-' . strtoupper(uniqid()),
            'date_transaction' => now()->format('Y-m-d H:i:s'),
            'compte_emetteur' => 'Test Emetteur',
            'compte_recepteur' => 'Test Recepteur'
        ];

        $result = $this->brevoService->sendTransactionNotification($email, $transactionData);

        if ($result) {
            Log::info('Transaction email test sent via event', [
                'email' => $email,
                'transaction_data' => $transactionData
            ]);
        } else {
            Log::error('Transaction email test failed via event', [
                'email' => $email,
                'transaction_data' => $transactionData
            ]);
        }
    }

    /**
     * Handle OTP test
     */
    protected function handleOtpTest(array $data): void
    {
        $email = $data['email'] ?? 'test@example.com';
        $phone = $data['phone'] ?? '771234567';
        $otp = $data['otp'] ?? str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Test SMS
        $smsResult = $this->twilioService->sendOtp('+221' . $phone, $otp);

        // Test Email
        $emailResult = $this->brevoService->sendOtpEmail($email, $otp);

        Log::info('OTP test sent via event', [
            'email' => $email,
            'phone' => $phone,
            'otp' => $otp,
            'sms_result' => $smsResult,
            'email_result' => $emailResult
        ]);
    }
}
