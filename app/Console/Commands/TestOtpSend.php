<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\OtpCode;
use App\Events\NotificationTestRequested;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class TestOtpSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:otp-send {--email= : Email address for testing} {--phone= : Phone number for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OTP sending via SMS and Email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing OTP sending system...');

        // Get test parameters
        $email = $this->option('email') ?: 'malickteuw.devweb@gmail.com';
        $phone = $this->option('phone') ?: '771719013';

        $this->info("📧 Test Email: {$email}");
        $this->info("📱 Test Phone: {$phone}");

        try {
            // Find or create test user
            $user = User::where('telephone', $phone)->first();

            if (!$user) {
                $this->warn("⚠️  User with phone {$phone} not found. Creating test user...");

                $user = User::create([
                    'nom' => 'Test',
                    'prenom' => 'User',
                    'telephone' => $phone,
                    'email' => $email,
                    'type' => 'client',
                    'statut' => 'actif',
                    'password' => bcrypt('password123'),
                ]);

                $this->info("✅ Test user created with ID: {$user->id}");
            } else {
                $this->info("✅ Using existing user: {$user->nom} {$user->prenom}");
            }

            // Create OTP code
            $otp = OtpCode::createForUser($user->id, $user->telephone, 'login');
            $this->info("🔢 OTP Code created: {$otp->code}");
            $this->info("🆔 OTP ID: {$otp->id}");

            // Test SMS sending
            $this->info("📤 Testing SMS OTP sending...");
            $twilioService = app(\App\Interfaces\Notifications\TwilioServiceInterface::class);
            $smsResult = $twilioService->sendOtp('+221' . $user->telephone, $otp->code);

            if ($smsResult) {
                $this->info("✅ SMS sent successfully to +221{$user->telephone}");
                Log::info('Test SMS OTP sent successfully', [
                    'user_id' => $user->id,
                    'phone' => '+221' . $user->telephone,
                    'otp_id' => $otp->id,
                ]);
            } else {
                $this->error("❌ SMS sending failed");
                Log::error('Test SMS OTP sending failed', [
                    'user_id' => $user->id,
                    'phone' => '+221' . $user->telephone,
                    'otp_id' => $otp->id,
                ]);
            }

            // Test Email sending
            $this->info("📧 Testing Email OTP sending...");
            $brevoService = app(\App\Interfaces\Notifications\BrevoServiceInterface::class);
            $userName = $user->nom . ' ' . $user->prenom;
            $emailResult = $brevoService->sendEmailOtp($user->email, $otp->code, $userName);

            if ($emailResult) {
                $this->info("✅ Email sent successfully to {$user->email}");
                Log::info('Test Email OTP sent successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'otp_id' => $otp->id,
                ]);
            } else {
                $this->error("❌ Email sending failed");
                Log::error('Test Email OTP sending failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'otp_id' => $otp->id,
                ]);
            }

            // Summary
            $this->info("\n📊 Test Summary:");
            $this->info("SMS Result: " . ($smsResult ? '✅ SUCCESS' : '❌ FAILED'));
            $this->info("Email Result: " . ($emailResult ? '✅ SUCCESS' : '❌ FAILED'));

            // Fire event for additional testing via listeners
            $this->info('🎯 Firing NotificationTestRequested event...');
            Event::dispatch(new NotificationTestRequested('otp', [
                'email' => $user->email,
                'phone' => $user->telephone,
                'otp' => $otp->code,
                'user_id' => $user->id,
                'otp_id' => $otp->id
            ]));

            $this->info('✅ Event fired successfully. Listeners will handle additional processing.');

            if ($smsResult && $emailResult) {
                $this->info("🎉 All OTP tests passed!");
            } else {
                $this->warn("⚠️  Some OTP tests failed. Check logs for details.");
            }

        } catch (\Exception $e) {
            $this->error("💥 Test failed with error: {$e->getMessage()}");
            Log::error('OTP test command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        return 0;
    }
}

