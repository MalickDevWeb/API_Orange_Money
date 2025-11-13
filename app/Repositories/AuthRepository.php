<?php

namespace App\Repositories;

use App\Models\User;
use App\Interfaces\Auth\AuthInterfaceRepository;
use App\Interfaces\Auth\AuthInterfaceService;
use Exception;
use Illuminate\Support\Facades\Hash;


class AuthRepository implements AuthInterfaceRepository
{
    public function register(array $data): User
    {
       return User::create($data);
    }

    public function login(array $credentials): User
     {
         // Vérifier si c'est un email ou un téléphone
         $field = filter_var($credentials['telephone'], FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

         $user = User::where($field, $credentials['telephone'])->first();

         if (!$user) {
             throw new Exception($field === 'email' ? 'Email non trouvé' : 'Numéro de téléphone non trouvé');
         }

         return $user;
     }

    public function logout(): void
    {
        // Revoke the current access token
        $user = auth('sanctum')->user();
        if ($user) {
            $user->tokens->each(function ($token) {
                $token->delete();
            });
        }
    }

    public function user(): User
    {
        return auth('sanctum')->user();
    }
}
