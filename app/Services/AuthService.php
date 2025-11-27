<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Interfaces\Auth\AuthInterfaceRepository;
use App\Dtos\UserResponseDto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Enums\ResponseMessage;
use App\Enums\MessagesErreursRequests;


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
               $errors[] = MessagesErreursRequests::EMAIL_EXISTS->value;
           }

           // Vérifier l'unicité du téléphone
           if (User::where('telephone', $data['telephone'])->exists()) {
               $errors[] = MessagesErreursRequests::TELEPHONE_EXISTS->value;
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

           $user = $this->authRepo->register($data);

           // Fire the registration event to send email
           event(new UserRegistered($user));

           return $user;
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

             // Vérifier si l'utilisateur commerçant ou fournisseur est approuvé
             if (($user->isCommercant() || $user->isFournisseur()) && !$user->isActif()) {
                 $message = $user->isCommercant()
                     ? MessagesErreursRequests::ACCOUNT_PENDING_APPROVAL_COMMERCHANT->value
                     : MessagesErreursRequests::ACCOUNT_PENDING_APPROVAL_SUPPLIER->value;
                 throw new Exception($message);
             }

             // Envoyer OTP
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
                 $brevoService = app(\App\Interfaces\Notifications\BrevoServiceInterface::class);
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
                 'otp_sent' => $otpSent
                 // OTP code is sent via email/SMS only for security - never returned in response
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
                throw new Exception(MessagesErreursRequests::OTP_INVALID->value);
            }

            if ($otpCode->hasExceededMaxAttempts(3)) {
                throw new Exception(MessagesErreursRequests::OTP_EXCEEDED_ATTEMPTS->value);
            }

            // Incrémenter les tentatives
            $otpCode->incrementAttempts();

            // Marquer comme utilisé
            $otpCode->markAsUsed();

            // Vérifier si l'utilisateur commerçant ou fournisseur est approuvé
            if (($otpCode->user->isCommercant() || $otpCode->user->isFournisseur()) && !$otpCode->user->isActif()) {
                $message = $otpCode->user->isCommercant()
                    ? MessagesErreursRequests::ACCOUNT_PENDING_APPROVAL_COMMERCHANT->value
                    : MessagesErreursRequests::ACCOUNT_PENDING_APPROVAL_SUPPLIER->value;
                throw new Exception($message);
            }

            // Générer le token
            $token = $otpCode->user->createToken('API Token');

            // CORRECTION: Fix Sanctum token format for PostgreSQL
            $fixedToken = $this->fixSanctumToken($token);

            // Fire the event after successful login
            event(new UserLoggedIn($otpCode->user));

            $response = [
                'user' => $otpCode->user,
                'token' => $fixedToken
            ];

            return $response;
        } catch (Exception $e) {
            Log::error('Erreur lors de la vérification OTP : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Fix Sanctum token format for PostgreSQL compatibility
     * Converts "|value" format to "id|value" format
     */
    private function fixSanctumToken($tokenResult)
    {
        try {
            // Get the token ID from database
            $dbToken = DB::table('personal_access_tokens')->latest('id')->first();
            if ($dbToken && $tokenResult) {
                $tokenValue = explode('|', $tokenResult->plainTextToken)[1] ?? '';
                return $dbToken->id . '|' . $tokenValue;
            }
            return $tokenResult->plainTextToken ?? '';
        } catch (\Exception $e) {
            Log::error('Error fixing Sanctum token: ' . $e->getMessage());
            return $tokenResult->plainTextToken ?? '';
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
                $brevoService = app(\App\Interfaces\Notifications\BrevoServiceInterface::class);
                $userName = $user->nom . ' ' . $user->prenom;
                $emailSent = $brevoService->sendEmailOtp($user->email, $otp->code, $userName);
            }

            $otpSent = $smsSent || $emailSent;
            $message = $otpSent ? 'OTP de déconnexion envoyé' : 'Erreur envoi OTP - vérifiez les logs';

            $response = [
                'message' => $message,
                'otp_sent' => $otpSent
                // OTP code is sent via email/SMS only for security - never returned in response
            ];

            return $response;
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'envoi OTP logout : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return array
     */
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
                throw new Exception(MessagesErreursRequests::OTP_INVALID->value);
            }

            if ($otpCode->hasExceededMaxAttempts(3)) {
                throw new Exception(MessagesErreursRequests::OTP_EXCEEDED_ATTEMPTS->value);
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

    //  public function createUserResponseDto(User $user): UserResponseDto
    //  {
    //      return UserResponseDto::fromUser($user);
    //  }

}
