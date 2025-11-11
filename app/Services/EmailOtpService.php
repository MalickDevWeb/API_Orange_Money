<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailOtpService
{
    /**
     * Générer et envoyer un OTP par email avec lien cliquable
     */
    public function sendOtpEmail(User $user): bool
    {
        try {
            // Générer OTP
            $otpCode = $this->generateOtpCode();

            // Stocker OTP dans la base de données
            $user->update([
                'otp_code' => $otpCode,
                'otp_expires_at' => now()->addMinutes(10), // Expire dans 10 minutes
                'otp_attempts' => 0,
                'otp_used' => false,
            ]);

            // Générer le token unique pour le lien
            $token = $this->generateSecureToken($user->id, $otpCode);

            // Envoyer l'email
            $this->sendEmail($user, $otpCode, $token);

            Log::info('OTP envoyé par email', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'OTP par email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Générer un code OTP à 6 chiffres
     */
    private function generateOtpCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Générer un token sécurisé pour le lien
     */
    private function generateSecureToken(string $userId, string $otpCode): string
    {
        return hash('sha256', $userId . $otpCode . config('app.key') . now()->timestamp);
    }

    /**
     * Envoyer l'email avec le lien cliquable
     */
    private function sendEmail(User $user, string $otpCode, string $token): void
    {
        $data = [
            'user' => $user,
            'otp_code' => $otpCode,
            'token' => $token,
            'expires_at' => $user->otp_expires_at,
        ];

        Mail::send('emails.otp-login', $data, function ($message) use ($user) {
            $message->to($user->email, $user->nom . ' ' . $user->prenom)
                    ->subject('Connexion Orange Money - Code de vérification');
        });
    }

    /**
     * Vérifier et utiliser un OTP depuis le lien
     */
    public function verifyOtpFromLink(string $token, string $userId): ?User
    {
        try {
            $user = User::find($userId);

            if (!$user || !$user->otp_code || $user->otp_used) {
                Log::warning('Tentative de vérification OTP invalide', [
                    'user_id' => $userId,
                    'token' => substr($token, 0, 10) . '...',
                ]);
                return null;
            }

            // Vérifier l'expiration
            if ($user->otp_expires_at->isPast()) {
                Log::warning('OTP expiré', ['user_id' => $userId]);
                return null;
            }

            // Vérifier le token
            $expectedToken = $this->generateSecureToken($user->id, $user->otp_code);
            if (!hash_equals($expectedToken, $token)) {
                // Incrémenter les tentatives
                $user->increment('otp_attempts');

                // Bloquer après 3 tentatives
                if ($user->otp_attempts >= 3) {
                    $user->update(['otp_used' => true]);
                    Log::warning('OTP bloqué après trop de tentatives', ['user_id' => $userId]);
                }

                Log::warning('Token OTP invalide', ['user_id' => $userId]);
                return null;
            }

            // Marquer l'OTP comme utilisé
            $user->update([
                'otp_used' => true,
                'otp_attempts' => 0,
            ]);

            Log::info('OTP vérifié avec succès via lien', ['user_id' => $userId]);

            return $user;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification OTP', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Nettoyer les OTP expirés (peut être appelé par un job)
     */
    public function cleanupExpiredOtps(): int
    {
        $count = User::where('otp_expires_at', '<', now())
                    ->where('otp_used', false)
                    ->update([
                        'otp_code' => null,
                        'otp_expires_at' => null,
                        'otp_attempts' => 0,
                        'otp_used' => false,
                    ]);

        Log::info('OTP expirés nettoyés', ['count' => $count]);

        return $count;
    }
}
