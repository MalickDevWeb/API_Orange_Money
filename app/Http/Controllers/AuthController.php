<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\Auth\AuthInterfaceService;
use App\Traits\ApiResponseTrait;

class AuthController extends Controller
{
  use ApiResponseTrait;
    protected AuthInterfaceService $authService;

    public function __construct(AuthInterfaceService $authService){$this->authService = $authService;}

    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());
            return $this->respondCreated($user, 'Utilisateur enregistré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $user = $this->authService->login($request->validated());
            $token = $user->createToken('API Token')->plainTextToken;
            return $this->respondWithToken($token, 'Connexion réussie', $user);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function logout()
    {
        try {
            $this->authService->logout();
            return $this->successResponse('Déconnexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

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
