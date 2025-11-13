<?php

namespace App\Services;

use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\Http;

class BrevoService implements BrevoServiceInterface
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.brevo.com/v3/smtp/email';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key') ?? env('BREVO_API_KEY');
    }

    public function sendMail(string $to, string $subject, string $htmlContent): array
    {
        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, [
            'sender' => [
                'name'  => config('services.brevo.sender_name'),
                'email' => config('services.brevo.sender_email'),
            ],
            'to' => [
                ['email' => $to],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        return $response->json();
    }

    /**
     * Créer et envoyer un email de campagne (simplifié)
     */
    public function sendCampaignEmail(array $campaignData): array
    {
        // Pour les tests, utilisons simplement sendMail avec du contenu de campagne
        $to = $campaignData['to'] ?? 'test@example.com';
        $subject = $campaignData['subject'] ?? 'Campagne Email';
        $htmlContent = $campaignData['htmlContent'] ?? '<h1>Campagne</h1><p>Contenu de campagne</p>';

        return $this->sendMail($to, $subject, $htmlContent);
    }

    /**
     * Envoyer une campagne immédiatement
     */
    public function sendCampaign(int $campaignId): array
    {
        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://api.brevo.com/v3/emailCampaigns/{$campaignId}/sendNow");

        return $response->json();
    }

    /**
     * Envoyer un email de notification de transaction
     */
    public function sendTransactionNotification(string $email, array $transactionData): array
    {
        $subject = $this->buildTransactionSubject($transactionData);
        $htmlContent = $this->buildTransactionHtmlContent($transactionData);

        return $this->sendMail($email, $subject, $htmlContent);
    }

    /**
     * Envoyer un email OTP
     */
    public function sendOtpEmail(string $email, string $otp): array
    {
        $subject = "Votre code de vérification Orange Money";
        $htmlContent = $this->buildOtpHtmlContent($otp);

        return $this->sendMail($email, $subject, $htmlContent);
    }

    /**
     * Construire le sujet de l'email de transaction
     */
    private function buildTransactionSubject(array $data): string
    {
        $type = $data['type'] ?? 'transaction';
        $reference = $data['reference'] ?? '';

        $typeLabels = [
            'depot' => 'Dépôt',
            'retrait' => 'Retrait',
            'transfert' => 'Transfert',
            'paiement' => 'Paiement',
            'achat_virtuel' => 'Achat virtuel'
        ];

        $typeLabel = $typeLabels[$type] ?? 'Transaction';

        return "Confirmation de {$typeLabel} - {$reference}";
    }

    /**
     * Construire le contenu HTML de l'email de transaction
     */
    private function buildTransactionHtmlContent(array $data): string
    {
        $type = $data['type'] ?? 'transaction';
        $montant = number_format($data['montant'] ?? 0, 0, ',', ' ');
        $reference = $data['reference'] ?? '';
        $date = $data['date_transaction'] ?? now()->format('d/m/Y H:i');

        $typeLabels = [
            'depot' => 'dépôt',
            'retrait' => 'retrait',
            'transfert' => 'transfert',
            'paiement' => 'paiement',
            'achat_virtuel' => 'achat virtuel'
        ];

        $typeLabel = $typeLabels[$type] ?? 'transaction';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Notification de Transaction - Orange Money</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FF6B35, #F7931E); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .transaction-details { background: white; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #FF6B35; }
                .amount { font-size: 24px; font-weight: bold; color: #FF6B35; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔔 Notification de Transaction</h1>
                    <p>Orange Money - Confirmation de {$typeLabel}</p>
                </div>
                <div class='content'>
                    <div class='transaction-details'>
                        <h2>Détails de la transaction</h2>
                        <p><strong>Type :</strong> " . ucfirst($typeLabel) . "</p>
                        <p><strong>Montant :</strong> <span class='amount'>{$montant} FCFA</span></p>
                        <p><strong>Référence :</strong> {$reference}</p>
                        <p><strong>Date :</strong> {$date}</p>
                    </div>
                    <p>Votre {$typeLabel} a été effectué avec succès.</p>
                    <p>Si vous n'êtes pas à l'origine de cette transaction, veuillez contacter immédiatement notre service client.</p>
                </div>
                <div class='footer'>
                    <p>Cette notification a été envoyée automatiquement par Orange Money.</p>
                    <p>© 2024 Orange Money - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Construire le contenu HTML de l'email OTP
     */
    private function buildOtpHtmlContent(string $otp): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Code de vérification - Orange Money</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FF6B35, #F7931E); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; text-align: center; }
                .otp-code { font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 4px; background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #FF6B35; }
                .warning { color: #d9534f; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Code de vérification</h1>
                    <p>Orange Money - Authentification</p>
                </div>
                <div class='content'>
                    <h2>Votre code de vérification</h2>
                    <div class='otp-code'>{$otp}</div>
                    <p>Ce code expire dans <strong>5 minutes</strong>.</p>
                    <p class='warning'>Ne partagez jamais ce code avec qui que ce soit.</p>
                    <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                </div>
                <div class='footer'>
                    <p>Cette notification a été envoyée automatiquement par Orange Money.</p>
                    <p>© 2024 Orange Money - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Envoyer un email simple (compatibilité EmailServiceInterface)
     */
    public function sendEmail(string $to, string $subject, string $content): bool
    {
        $result = $this->sendMail($to, $subject, $content);
        return isset($result['messageId']);
    }

    /**
     * Envoyer un email avec pièce jointe (compatibilité EmailServiceInterface)
     */
    public function sendEmailWithAttachment(string $to, string $subject, string $content, ?string $attachmentPath = null): bool
    {
        // Pour l'instant, on ignore la pièce jointe et on envoie l'email simple
        // TODO: Implémenter l'envoi avec pièce jointe via Brevo API
        return $this->sendEmail($to, $subject, $content);
    }

    /**
     * Envoyer un email OTP (compatibilité EmailServiceInterface)
     */
    public function sendEmailOtp(string $to, string $otpCode, string $userName): bool
    {
        $subject = "Votre code de vérification Orange Money";
        $htmlContent = $this->buildOtpHtmlContentWithName($otpCode, $userName);

        $result = $this->sendMail($to, $subject, $htmlContent);
        return isset($result['messageId']);
    }

    /**
     * Construire le contenu HTML de l'email OTP avec nom d'utilisateur
     */
    private function buildOtpHtmlContentWithName(string $otp, string $userName): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Code de vérification - Orange Money</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FF6B35, #F7931E); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; text-align: center; }
                .otp-code { font-size: 32px; font-weight: bold; color: #FF6B35; letter-spacing: 4px; background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #FF6B35; }
                .warning { color: #d9534f; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Code de vérification</h1>
                    <p>Orange Money - Authentification</p>
                </div>
                <div class='content'>
                    <h2>Bonjour {$userName}</h2>
                    <p>Votre code de vérification</p>
                    <div class='otp-code'>{$otp}</div>
                    <p>Ce code expire dans <strong>5 minutes</strong>.</p>
                    <p class='warning'>Ne partagez jamais ce code avec qui que ce soit.</p>
                    <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                </div>
                <div class='footer'>
                    <p>Cette notification a été envoyée automatiquement par Orange Money.</p>
                    <p>© 2024 Orange Money - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
