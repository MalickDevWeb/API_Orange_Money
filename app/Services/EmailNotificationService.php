<?php

namespace App\Services;

use App\Interfaces\Services\EmailNotificationServiceInterface;
use App\Interfaces\Notifications\BrevoServiceInterface;
use Illuminate\Support\Facades\View;

class EmailNotificationService implements EmailNotificationServiceInterface
{
    protected BrevoServiceInterface $brevoService;

    public function __construct(BrevoServiceInterface $brevoService)
    {
        $this->brevoService = $brevoService;
    }

    /**
     * Send admin notification for new user registration
     */
    public function sendNewUserNotification(array $userData): void
    {
        try {
            // Find admin email - in a real app, this would be configurable
            $adminEmail = config('app.admin_email', 'admin@example.com');

            $subject = "Nouvelle inscription utilisateur - {$userData['nom']} {$userData['prenom']}";
            $message = "Un nouvel utilisateur s'est inscrit : {$userData['nom']} {$userData['prenom']} ({$userData['telephone']})";

            $htmlContent = View::make('emails.admin-notification', [
                'user' => $userData,
                'message' => $message,
                'type' => 'new_registration'
            ])->render();

            $this->brevoService->sendMail($adminEmail, $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification admin envoyé', [
                'user_telephone' => $userData['telephone'],
                'admin_email' => $adminEmail
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email admin: ' . $e->getMessage(), [
                'user_telephone' => $userData['telephone']
            ]);
        }
    }

    /**
     * Send OTP via email (fallback)
     */
    public function sendOtpEmail(string $email, string $otpCode, string $type): void
    {
        try {
            $subject = $type === 'login_otp' ? 'Code de connexion' : 'Code de déconnexion';
            $message = "Votre code de vérification est : {$otpCode}. Ce code expire dans 5 minutes.";

            $htmlContent = View::make('emails.otp-notification', [
                'otp_code' => $otpCode,
                'type' => $type,
                'message' => $message
            ])->render();

            $this->brevoService->sendMail($email, $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email OTP envoyé', [
                'email' => $email,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email OTP: ' . $e->getMessage(), [
                'email' => $email,
                'type' => $type
            ]);
        }
    }

    /**
     * Send login notification email
     */
    public function sendLoginNotification(array $userData): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Connexion à votre compte";
            $message = "Vous vous êtes connecté à votre compte avec succès.";

            $htmlContent = View::make('emails.login-notification', [
                'user' => $userData,
                'message' => $message,
                'login_time' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification connexion envoyé', [
                'user_email' => $userData['email']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email connexion: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Send registration confirmation email
     */
    public function sendRegistrationConfirmation(array $userData): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Bienvenue sur notre plateforme";
            $message = "Votre compte a été créé avec succès. Bienvenue !";

            $htmlContent = View::make('emails.registration-confirmation', [
                'user' => $userData,
                'message' => $message,
                'registration_date' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email confirmation inscription envoyé', [
                'user_email' => $userData['email']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email confirmation: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Send new account creation notification email
     */
    public function sendNewAccountNotification(array $userData, array $accountData): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Nouveau compte créé - {$accountData['nom_compte']}";
            $message = "Un nouveau compte a été créé avec succès sur votre espace client.";

            $htmlContent = View::make('emails.new-account-notification', [
                'user' => $userData,
                'account' => $accountData,
                'message' => $message,
                'creation_date' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification nouveau compte envoyé', [
                'user_email' => $userData['email'],
                'account_name' => $accountData['nom_compte']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email nouveau compte: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown',
                'account_name' => $accountData['nom_compte'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Send account modification notification email
     */
    public function sendAccountModificationNotification(array $userData, array $accountData, array $changes): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Modification de compte - {$accountData['nom_compte']}";
            $message = "Votre compte a été modifié avec succès.";

            $htmlContent = View::make('emails.account-modification-notification', [
                'user' => $userData,
                'account' => $accountData,
                'changes' => $changes,
                'message' => $message,
                'modification_date' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification modification compte envoyé', [
                'user_email' => $userData['email'],
                'account_name' => $accountData['nom_compte']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email modification compte: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown',
                'account_name' => $accountData['nom_compte'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Send account switch notification email
     */
    public function sendAccountSwitchNotification(array $userData, array $oldAccountData, array $newAccountData): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Changement de compte actif - {$newAccountData['nom_compte']}";
            $message = "Votre compte actif a été changé avec succès.";

            $htmlContent = View::make('emails.account-switch-notification', [
                'user' => $userData,
                'old_account' => $oldAccountData,
                'new_account' => $newAccountData,
                'message' => $message,
                'switch_date' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification changement compte actif envoyé', [
                'user_email' => $userData['email'],
                'old_account' => $oldAccountData['nom_compte'],
                'new_account' => $newAccountData['nom_compte']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email changement compte actif: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Send account deletion notification email
     */
    public function sendAccountDeletionNotification(array $userData, array $accountData, string $reason): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Suppression de compte - {$accountData['nom_compte']}";
            $message = "Votre compte a été supprimé.";

            $htmlContent = View::make('emails.account-deletion-notification', [
                'user' => $userData,
                'account' => $accountData,
                'reason' => $reason,
                'message' => $message,
                'deletion_date' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification suppression compte envoyé', [
                'user_email' => $userData['email'],
                'account_name' => $accountData['nom_compte'],
                'reason' => $reason
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email suppression compte: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown',
                'account_name' => $accountData['nom_compte'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Send account restoration notification email
     */
    public function sendAccountRestorationNotification(array $userData, array $accountData): void
    {
        try {
            if (empty($userData['email'])) {
                return;
            }

            $subject = "Restauration de compte - {$accountData['nom_compte']}";
            $message = "Votre compte a été restauré avec succès.";

            $htmlContent = View::make('emails.account-restoration-notification', [
                'user' => $userData,
                'account' => $accountData,
                'message' => $message,
                'restoration_date' => now()
            ])->render();

            $this->brevoService->sendMail($userData['email'], $subject, $htmlContent);

            \Illuminate\Support\Facades\Log::info('Email notification restauration compte envoyé', [
                'user_email' => $userData['email'],
                'account_name' => $accountData['nom_compte']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi email restauration compte: ' . $e->getMessage(), [
                'user_email' => $userData['email'] ?? 'unknown',
                'account_name' => $accountData['nom_compte'] ?? 'unknown'
            ]);
        }
    }
}
