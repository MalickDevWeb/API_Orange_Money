<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\Auth\AuthInterfaceService;
use App\Traits\ApiResponseTrait;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'authentification des utilisateurs"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="uuid-user"),
 *     @OA\Property(property="nom", type="string", example="Dupont"),
 *     @OA\Property(property="prenom", type="string", example="Jean"),
 *     @OA\Property(property="telephone", type="string", example="770000001"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
 *     @OA\Property(property="type", type="string", enum={"admin","client","commercant"}, example="client"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\PathItem(
 *     path="/api/register"
 * )
 * @OA\PathItem(
 *     path="/api/login"
 * )
 * @OA\PathItem(
 *     path="/api/logout"
 * )
 * @OA\PathItem(
 *     path="/api/user"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;
    protected AuthInterfaceService $authService;

    public function __construct(AuthInterfaceService $authService){$this->authService = $authService;}

    /**
     * @OA\Post(
     *     path="/api/register",
     *     operationId="registerUser",
     *     tags={"Authentification"},
     *     summary="Inscription d'un nouvel utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","telephone","email","password","type"},
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="telephone", type="string", example="770000001"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="type", type="string", enum={"admin","client","commercant"}, example="client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string", example="Utilisateur enregistré avec succès")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());
            return $this->respondCreated($user, 'Utilisateur enregistré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     operationId="loginUser",
     *     tags={"Authentification"},
     *     summary="Première étape : Vérification du numéro de téléphone et envoi OTP par email",
     * @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"telephone"},
      *             @OA\Property(property="telephone", type="string", example="770000001", description="Numéro de téléphone ou email")
      *         )
      *     ),
     * @OA\Response(
      *         response=200,
      *         description="Email OTP envoyé, procéder à la vérification",
      *         @OA\JsonContent(
      *             @OA\Property(property="status", type="string", example="success"),
      *             @OA\Property(property="message", type="string", example="Email de vérification envoyé"),
      *             @OA\Property(property="requires_otp", type="boolean", example=true),
      *             @OA\Property(property="email", type="string", example="user@example.com"),
      *             @OA\Property(property="phone_number", type="string", example="770000001")
      *         )
      *     ),
     *     @OA\Response(response=401, description="Numéro de téléphone non trouvé"),
     *     @OA\Response(response=500, description="Erreur d'envoi d'email")
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());
            return $this->successResponse(
                [
                    'requires_otp' => true,
                    'email' => $result['email'],
                    'phone_number' => $result['user']->telephone
                ],
                'Email de vérification envoyé'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     operationId="logoutUser",
     *     tags={"Authentification"},
     *     summary="Déconnexion utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function logout()
    {
        try {
            $this->authService->logout();
            return $this->successResponse('Déconnexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login/otp",
     *     operationId="verifyOtp",
     *     tags={"Authentification"},
     *     summary="Deuxième étape : Vérification du code OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","otp_code"},
     *             @OA\Property(property="telephone", type="string", example="770000001"),
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
        try {
            $result = $this->authService->verifyOtp($request->validated());
            return $this->respondWithToken(
                $result['token'],
                'Connexion réussie',
                $result['user']
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/verify/{userId}/{token}",
     *     operationId="verifyOtpLink",
     *     tags={"Authentification"},
     *     summary="Vérification OTP via lien email (auto-submit)",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="ID de l'utilisateur"
     *     ),
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Token de vérification sécurisé"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié, redirection avec token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Lien invalide ou expiré"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function verifyOtpLink(string $userId, string $token)
    {
        try {
            $emailOtpService = app(\App\Services\EmailOtpService::class);
            $user = $emailOtpService->verifyOtpFromLink($token, $userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lien de vérification invalide ou expiré'
                ], 400);
            }

            // Générer le token JWT
            $jwtToken = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie via email',
                'access_token' => $jwtToken,
                'token_type' => 'Bearer',
                'user' => new \App\Http\Resources\UserAuthResource($user)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification du lien'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user",
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
            return $this->successResponse('Utilisateur récupéré avec succès', $user);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

}
