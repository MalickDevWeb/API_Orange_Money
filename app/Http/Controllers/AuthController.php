<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Interfaces\Auth\AuthInterfaceService;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponseTrait;
use App\Enums\ResponseMessage;
use App\Events\OtpRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'authentification des utilisateurs"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="nom", type="string", example="Dupont"),
 *     @OA\Property(property="prenom", type="string", example="Jean"),
 *     @OA\Property(property="telephone", type="string", example="705334611"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
 *     @OA\Property(property="type", type="string", enum={"admin","client","commercant","fournisseur"}, example="client", description="Type d'utilisateur : admin (administrateur), client (utilisateur standard, actif immédiatement), commercant/fournisseur (comptes professionnels, en attente d'approbation)"),
 *     @OA\Property(property="statut", type="string", enum={"actif","inactif","en_attente","suspendu"}, example="actif", description="Statut du compte : actif (compte opérationnel), inactif (compte désactivé), en_attente (en attente d'approbation pour commercants/fournisseurs), suspendu (compte temporairement suspendu)"),
 *     @OA\Property(property="pin", type="string", example="1234", description="Code PIN à 4 chiffres")
 * )
 *
 * @OA\PathItem(
 *     path="/register"
 * )
 * @OA\PathItem(
 *     path="/login"
 * )
 * @OA\PathItem(
 *     path="/logout"
 * )
 * @OA\PathItem(
 *     path="/user"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;
    protected AuthInterfaceService $authService;

    public function __construct(AuthInterfaceService $authService){$this->authService = $authService;}

    /**
     * @OA\Post(
     *     path="/register",
     *     operationId="registerUser",
     *     tags={"Authentification"},
     *     summary="Inscription d'un nouvel utilisateur avec envoi d'email automatique",
     *     description="Crée un nouveau compte utilisateur et envoie automatiquement un email de confirmation. Pour les commerçants et fournisseurs, le compte sera en attente d'approbation par un administrateur.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","telephone","email","password","type"},
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="telephone", type="string", example="705334611"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="type", type="string", enum={"admin","client","commercant","fournisseur"}, example="client", description="Type d'utilisateur : client (actif immédiatement), commerçant/fournisseur (en attente d'approbation)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="user",
     *                 @OA\Property(property="nom", type="string", example="Dupont"),
     *                 @OA\Property(property="prenom", type="string", example="Jean"),
     *                 @OA\Property(property="telephone", type="string", example="705334611"),
     *                 @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *                 @OA\Property(property="type", type="string", enum={"admin","client","commercant","fournisseur"}, example="client"),
     *                 @OA\Property(property="statut", type="string", enum={"actif","inactif","en_attente"}, example="actif"),
     *                 @OA\Property(property="pin", type="string", example="1234")
     *             ),
     *             @OA\Property(property="message", type="string", example="Utilisateur enregistré avec succès"),
     *             @OA\Property(property="redirect_url", type="string", example="https://api-orange-money-pmt.onrender.com/api/login?telephone=705334611")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function register(RegisterRequest $request)
    {
        /** @var RegisterRequest $request */
        try {
            $user = $this->authService->register($request->validated());
            $redirectUrl = url('/api/login?telephone=' . urlencode($user->telephone));

            // Envoyer un email de notification à l'admin
            $admin = \App\Models\User::where('type', 'admin')->first();
            if ($admin && $admin->email) {
                $subject = "Nouvelle inscription - {$user->nom} {$user->prenom}";
                $message = "Un nouvel utilisateur s'est inscrit : {$user->nom} {$user->prenom} ({$user->telephone}, {$user->email}). Type : {$user->type}. Statut : {$user->statut}.";
                $htmlContent = View::make('emails.transaction-notification', [
                    'transaction' => null,
                    'message' => $message,
                    'role' => 'admin_notification'
                ])->render();

                try {
                    \Illuminate\Support\Facades\Mail::html($htmlContent, function ($mail) use ($admin, $subject) {
                        $mail->to($admin->email)
                             ->subject($subject);
                    });
                    \Illuminate\Support\Facades\Log::info('Email de notification d\'inscription envoyé à l\'admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'new_user_id' => $user->id
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erreur envoi email notification admin', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $this->respondCreated(new UserResource($user), ResponseMessage::USER_REGISTERED->value, $redirectUrl);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/sendOTP",
     *     operationId="sendOtp",
     *     tags={"Authentification"},
     *     summary="Connexion : Vérification téléphone et envoi OTP",
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", example="705334611", description="Numéro de téléphone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="OTP envoyé"),
     *             @OA\Property(property="requires_otp", type="boolean", example=true),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="705334611"),
     *             @OA\Property(property="otp_sent", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Numéro de téléphone non trouvé"),
     *     @OA\Response(response=500, description="Erreur d'envoi d'SMS")
     * )
     */
    public function login(LoginRequest $request)
    {
        /** @var LoginRequest $request */
        try {
            $result = $this->authService->login($request->validated());

            // OTP envoyé
            $responseData = [
                'requires_otp' => true,
                'email' => $result['email'],
                'phone_number' => $result['phone_number'],
                'otp_sent' => $result['otp_sent'] ?? false
                // OTP code is sent via email/SMS only for security - never returned in response
            ];

            return $this->successResponse($responseData, $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/logout/otp",
     *     operationId="sendLogoutOtp",
     *     tags={"Authentification"},
     *     summary="Envoyer OTP pour déconnexion",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="OTP de déconnexion envoyé"),
     *             @OA\Property(property="otp_sent", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function sendLogoutOtp()
    {
        try {
            $result = $this->authService->sendLogoutOtp();
            $responseData = [
                'otp_sent' => $result['otp_sent'] ?? false
                // OTP code is sent via email/SMS only for security - never returned in response
            ];

            return $this->successResponse($responseData, $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     operationId="verifyOtp",
     *     tags={"Authentification"},
     *     summary="Vérifier l'OTP et déconnecter l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"otp_code"},
     *             @OA\Property(property="otp_code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Code OTP invalide"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function verifyLogoutOtp(\Illuminate\Http\Request $request)
    {
        /** @var \Illuminate\Http\Request $request */
        try {
            $data = $request->validate([
                'otp_code' => 'required|string|size:6',
            ]);
            $result = $this->authService->verifyLogoutOtp($data);
            return $this->successResponse($result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/login/otp",
     *     operationId="login",
     *     tags={"Authentification"},
     *     summary="Login",
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","otp_code"},
     *             @OA\Property(property="telephone", type="string", example="705334611"),
     *             @OA\Property(property="otp_code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié, connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Code OTP invalide ou expiré"),
     *     @OA\Response(response=429, description="Trop de tentatives")
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        /** @var VerifyOtpRequest $request */
        try {
            $result = $this->authService->verifyOtp($request->validated());
            $message = $result['message'] ?? ResponseMessage::LOGIN_SUCCESS->value;
            $extra = [];
            if (isset($result['generated_pin'])) {
                $extra['generated_pin'] = $result['generated_pin'];
            }
            return $this->respondWithToken(
                $result['token'],
                $message,
                $result['user'],
                $extra
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }


    /**
     * @OA\Get(
     *     path="/user",
     *     operationId="getCurrentUser",
     *     tags={"Authentification"},
     *     summary="Récupérer les informations de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
         description="Informations utilisateur récupérées",
         *         @OA\JsonContent(
         *             @OA\Property(property="status", type="string", example="success"),
         *             @OA\Property(property="message", type="string", example="Utilisateur récupéré avec succès"),
         *             @OA\Property(property="data", ref="#/components/schemas/User")
         *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function user()
    {
        try {
            $user = $this->authService->user();
            return $this->successResponse(new UserResource($user), ResponseMessage::USER_RETRIEVED->value);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function sendOtp(\Illuminate\Http\Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Déclencher l’événement
        event(new OtpRequested($request->email));

        return response()->json([
            'message' => 'Le code de vérification a été envoyé à votre email.'
        ]);
    }

    public function verifyOtpEmail(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $otp = DB::table('otp_codes')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Code invalide ou expiré'], 400);
        }

        return response()->json(['message' => 'Code vérifié avec succès ✅']);
    }

}

