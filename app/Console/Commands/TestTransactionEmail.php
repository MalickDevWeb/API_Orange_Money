<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Compte;
use App\Events\NotificationTestRequested;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class TestTransactionEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:transaction-email {--email= : Email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test transaction email sending via Brevo API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing transaction email sending...');

        // Get test email from option or ask for it
        $testEmail = $this->option('email');
        if (!$testEmail) {
            $testEmail = $this->ask('Enter test email address');
        }

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return 1;
        }

        // Create test users and accounts if they don't exist
        $this->createTestData();

        // Create a test transaction
        $transaction = $this->createTestTransaction();

        if (!$transaction) {
            $this->error('Failed to create test transaction.');
            return 1;
        }

        $this->info("Test transaction created with ID: {$transaction->id}");
        $this->info("Reference: {$transaction->reference}");

        // Manually trigger email sending for testing
        $this->sendTestEmails($transaction, $testEmail);

        // Fire event for additional testing via listeners
        $this->info('🎯 Firing NotificationTestRequested event...');
        Event::dispatch(new NotificationTestRequested('transaction_email', [
            'email' => $testEmail,
            'transaction_id' => $transaction->id,
            'reference' => $transaction->reference
        ]));

        $this->info('✅ Event fired successfully. Listeners will handle additional processing.');
        $this->info('Test completed. Check your email and logs for results.');

        return 0;
    }

    protected function createTestData()
    {
        // Create test sender user
        $sender = User::firstOrCreate(
            ['telephone' => '771234567'],
            [
                'nom' => 'Test',
                'prenom' => 'Sender',
                'email' => 'sender@test.com',
                'type' => 'client',
                'statut' => 'actif',
                'password' => bcrypt('password')
            ]
        );

        // Create sender account
        Compte::firstOrCreate(
            ['utilisateur_id' => $sender->id],
            [
                'numero_compte' => 'TEST001',
                'titulaire' => 'Test Sender',
                'code_marchand' => null,
                'statut' => 'actif'
            ]
        );

        // Create test receiver user
        $receiver = User::firstOrCreate(
            ['telephone' => '772345678'],
            [
                'nom' => 'Test',
                'prenom' => 'Receiver',
                'email' => 'receiver@test.com',
                'type' => 'client',
                'statut' => 'actif',
                'password' => bcrypt('password')
            ]
        );

        // Create receiver account
        Compte::firstOrCreate(
            ['utilisateur_id' => $receiver->id],
            [
                'numero_compte' => 'TEST002',
                'titulaire' => 'Test Receiver',
                'code_marchand' => null,
                'statut' => 'actif'
            ]
        );

        $this->info('Test data created successfully.');
    }

    protected function createTestTransaction()
    {
        $sender = User::where('telephone', '771234567')->first();
        $receiver = User::where('telephone', '772345678')->first();

        if (!$sender || !$receiver) {
            return null;
        }

        $senderAccount = $sender->comptes->first();
        $receiverAccount = $receiver->comptes->first();

        if (!$senderAccount || !$receiverAccount) {
            return null;
        }

        return Transaction::create([
            'type' => 'transfert',
            'montant' => 50000,
            'reference' => 'TEST-' . strtoupper(uniqid()),
            'statut' => 'reussie',
            'note' => 'Transaction de test pour email',
            'compte_emetteur_id' => $senderAccount->id,
            'compte_recepteur_id' => $receiverAccount->id,
            'date_transaction' => now(),
        ]);
    }

    protected function sendTestEmails(Transaction $transaction, string $testEmail)
    {
        $this->info("📧 Testing email templates for transaction {$transaction->reference}");

        // Test sender email template
        $this->info("📤 Sender Email Template:");
        $subject = "Test - Confirmation de Transfert - {$transaction->reference}";
        $htmlContent = view('emails.transaction-notification', [
            'transaction' => $transaction,
            'message' => 'Votre transfert de test a été effectué avec succès.',
            'role' => 'sender'
        ])->render();

        $this->line("Subject: {$subject}");
        $this->line("To: {$testEmail}");
        $this->comment("HTML Content Preview (first 200 chars): " . substr(strip_tags($htmlContent), 0, 200) . "...");

        // Test receiver email template
        $this->info("📥 Receiver Email Template:");
        $subject = "Test - Notification de Transfert - {$transaction->reference}";
        $htmlContent = view('emails.transaction-notification', [
            'transaction' => $transaction,
            'message' => 'Vous avez reçu un transfert de test.',
            'role' => 'receiver'
        ])->render();

        $this->line("Subject: {$subject}");
        $this->line("To: {$testEmail}");
        $this->comment("HTML Content Preview (first 200 chars): " . substr(strip_tags($htmlContent), 0, 200) . "...");

        // Try to send actual email if API key seems valid
        $brevoService = app(\App\Services\BrevoService::class);
        $apiKey = config('services.brevo.api_key');

        if (empty($apiKey) || strlen($apiKey) < 50) {
            $this->warn("⚠️  Brevo API key not configured or seems invalid. Skipping actual email send.");
            $this->info("💡 To test actual email sending, configure BREVO_API_KEY in your .env file");
            return;
        }

        $this->info("🚀 Attempting to send actual emails...");

        // Send email to test address as sender
        $result = $brevoService->sendMail($testEmail, $subject, $htmlContent);

        if (isset($result['messageId'])) {
            $this->info("✅ Email sent successfully to {$testEmail}");
            $this->info("Message ID: {$result['messageId']}");
        } else {
            $this->error("❌ Failed to send email: " . json_encode($result));
            $this->warn("💡 This might be due to invalid API key or Brevo service issues");
        }
    }
}
