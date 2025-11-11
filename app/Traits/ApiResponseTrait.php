<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ApiResponseTrait
{
    // Réponse succès générique
    public function successResponse($data, string $message = 'Opération réussie', int $status = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    // Réponse erreur générique
    public function errorResponse(string $message = 'Erreur', int $status = 500, $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    // Réponse validation
    public function validationErrorResponse($errors, string $message = 'Données invalides', int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    // Réponse création ressource spécifique (UserResource)
    public function respondCreated($model, string $message = 'Ressource créée avec succès', $redirectUrl = null): JsonResponse
    {
        try {
            if (!$model) {
                return $this->errorResponse('Impossible de créer la ressource.', 500);
            }

            $response = [
                'user' => new \App\Http\Resources\UserResource($model),
                'message' => $message
            ];

            if ($redirectUrl) {
                $response['redirect_url'] = $redirectUrl;
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération de la réponse JSON : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Erreur interne du serveur.', 500);
        }
    }

    // Réponse avec token
    public function respondWithToken($token, string $message = 'Authentification réussie', $user = null): JsonResponse
    {
        try {
            $response = [
                'status' => 'success',
                'message' => $message,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];

            if ($user) {
                $response['user'] = new \App\Http\Resources\UserAuthResource($user);
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération de la réponse avec token : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Erreur interne du serveur.', 500);
        }
    }
}
