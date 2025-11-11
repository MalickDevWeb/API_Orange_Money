<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Interfaces\Auth\AuthInterfaceRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;


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

             // Envoyer OTP par SMS
             $twilioService = app(\App\Services\TwilioService::class);
             $smsSent = $twilioService->sendOtp('+221' . $user->telephone, $otp->code);

             $message = $smsSent ? 'OTP envoyé par SMS' : 'Erreur envoi SMS - vérifiez les logs';

             return [
                 'user' => $user,
                 'email' => $user->email,
                 'phone_number' => $user->telephone,
                 'message' => $message,
                 'otp_sent' => $smsSent
             ];
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

            // Générer le token
            $tokenModel = $otpCode->user->createToken('API Token');
            $token = $tokenModel->accessToken;

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
