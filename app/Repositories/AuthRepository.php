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
        $user = User::where('telephone', $credentials['telephone'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new Exception('Invalid credentials');
        }

        return $user;
    }

    public function logout(): void
    {
        // Revoke the current access token
        $user = auth('api')->user();
        if ($user) {
            $user->tokens->each(function ($token) {
                $token->delete();
            });
        }
    }

    public function user(): User
    {
        return auth('api')->user();
    }
}
