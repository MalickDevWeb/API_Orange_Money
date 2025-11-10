<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
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

    

}
