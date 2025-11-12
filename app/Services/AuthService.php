<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Interfaces\Auth\AuthInterfaceRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Events\UserLoggedIn;


use App\Interfaces\Auth\AuthInterfaceService;

class AuthService implements AuthInterfaceService
{

        protected  AuthInterfaceRepository $authRepo;
        public function __construct(AuthInterfaceRepository $authRepo) {
               $this->authRepo = $authRepo;
        }


   public function register(array $data): User
   {
       try {
           $errors = [];

           // Vérifier l'unicité de l'email
           if (User::where('email', $data['email'])->exists()) {
               $errors[] = 'L\'email est déjà utilisé.';
           }

           // Vérifier l'unicité du téléphone
           if (User::where('telephone', $data['telephone'])->exists()) {
               $errors[] = 'Le numéro de téléphone est déjà utilisé.';
           }

           // Si des erreurs, les combiner
           if (!empty($errors)) {
               throw new Exception(implode(' ', $errors));
           }

           // Hash du mot de passe avant enregistrement
           $data['password'] = Hash::make($data['password']);

           // Set default statut based on type
           if (!isset($data['statut'])) {
               $data['statut'] = (in_array($data['type'], ['commercant', 'fournisseur'])) ? 'en_attente' : 'actif';
           }

           return $this->authRepo->register($data);
       } catch (Exception $e) {
           Log::error('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage(), [
               'trace' => $e->getTraceAsString(),
               'data' => $data,
           ]);
           throw $e; // Relancer l'exception pour une gestion uniforme
       }
   }

    public function login(array $credentials): array
     {
         try {
             $user = $this->authRepo->login($credentials);

             // Créer OTP
             $otp = \App\Models\OtpCode::createForUser(
                 $user->id,
                 $user->telephone,
                 'login'
             );

             // Envoyer OTP par SMS (principal)
             $twilioService = app(\App\Services\TwilioService::class);
             $smsSent = $twilioService->sendOtp('+221' . $user->telephone, $otp->code);

             $emailSent = false;
             if (!$smsSent) {
                 // Fallback to email if SMS fails
                 $brevoService = app(\App\Interfaces\Notifications\EmailServiceInterface::class);
                 $userName = $user->nom . ' ' . $user->prenom;
                 $emailSent = $brevoService->sendEmailOtp($user->email, $otp->code, $userName);
             }

             $otpSent = $smsSent || $emailSent;
             $message = $otpSent ? 'OTP envoyé' : 'Erreur envoi OTP - vérifiez les logs';

             $response = [
                 'user' => $user,
                 'email' => $user->email,
                 'phone_number' => $user->telephone,
                 'message' => $message,
                 'otp_sent' => $otpSent,
                 'otp_code' => app()->environment('local') ? $otp->code : null // Include OTP for testing in local environment
             ];

             return $response;
         } catch (Exception $e) {
             Log::error('Erreur lors de la connexion de l\'utilisateur : ' . $e->getMessage(), [
                 'trace' => $e->getTraceAsString(),
                 'credentials' => $credentials,
             ]);
             throw $e;
         }
     }

    public function verifyOtp(array $data): array
    {
        try {
            $otpCode = \App\Models\OtpCode::findValidCode(
                $data['otp_code'],
                $data['telephone'],
                'login'
            );

            if (!$otpCode) {
                throw new Exception('Code OTP invalide ou expiré');
            }

            if ($otpCode->hasExceededMaxAttempts(3)) {
                throw new Exception('Trop de tentatives, veuillez réessayer plus tard');
            }

            // Incrémenter les tentatives
            $otpCode->incrementAttempts();

            // Marquer comme utilisé
            $otpCode->markAsUsed();

            // Vérifier si l'utilisateur commerçant ou fournisseur est approuvé
            if (in_array($otpCode->user->type, ['commercant', 'fournisseur']) && $otpCode->user->statut !== 'actif') {
                $typeLabel = $otpCode->user->type === 'commercant' ? 'commerçant' : 'fournisseur';
                throw new Exception("Votre compte $typeLabel est en attente d'approbation par l'administrateur.");
            }

            // Générer le token
            $token = $otpCode->user->createToken('API Token');

            // Fire the event after successful login
            event(new UserLoggedIn($otpCode->user));

            return [
                'user' => $otpCode->user,
                'token' => $token
            ];
        } catch (Exception $e) {
            Log::error('Erreur lors de la vérification OTP : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    public function sendLogoutOtp(): array
    {
        try {
            $user = $this->authRepo->user();

            // Créer OTP pour logout
            $otp = \App\Models\OtpCode::createForUser(
                $user->id,
                $user->telephone,
                'logout'
            );

            // Envoyer OTP par SMS (principal)
            $twilioService = app(\App\Services\TwilioService::class);
            $smsSent = $twilioService->sendOtp('+221' . $user->telephone, $otp->code);

            $emailSent = false;
            if (!$smsSent) {
                // Fallback to email if SMS fails
                $brevoService = app(\App\Interfaces\Notifications\EmailServiceInterface::class);
                $userName = $user->nom . ' ' . $user->prenom;
                $emailSent = $brevoService->sendEmailOtp($user->email, $otp->code, $userName);
            }

            $otpSent = $smsSent || $emailSent;
            $message = $otpSent ? 'OTP de déconnexion envoyé' : 'Erreur envoi OTP - vérifiez les logs';

            $response = [
                'message' => $message,
                'otp_sent' => $otpSent,
                'otp_code' => app()->environment('local') ? $otp->code : null // Include OTP for testing in local environment
            ];

            return $response;
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'envoi OTP logout : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function verifyLogoutOtp(array $data): array
    {
        try {
            $user = $this->authRepo->user();

            $otpCode = \App\Models\OtpCode::where('user_id', $user->id)
                ->where('code', $data['otp_code'])
                ->where('type', 'logout')
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->first();

            if (!$otpCode) {
                throw new Exception('Code OTP invalide ou expiré');
            }

            if ($otpCode->hasExceededMaxAttempts(3)) {
                throw new Exception('Trop de tentatives, veuillez réessayer plus tard');
            }

            // Incrémenter les tentatives
            $otpCode->incrementAttempts();

            // Marquer comme utilisé
            $otpCode->markAsUsed();

            // Déconnecter
            $this->authRepo->logout();

            return [
                'message' => 'Déconnexion réussie'
            ];
        } catch (Exception $e) {
            Log::error('Erreur lors de la vérification OTP logout : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    public function logout(): void
    {
        try {
            $this->authRepo->logout();
        } catch (Exception $e) {
            Log::error('Erreur lors de la déconnexion de l\'utilisateur : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

     public function user(): User
     {
         try {
             return $this->authRepo->user();
         } catch (Exception $e) {
             Log::error('Erreur lors de la récupération de l\'utilisateur : ' . $e->getMessage(), [
                 'trace' => $e->getTraceAsString(),
             ]);
             throw $e;
         }
     }

}
