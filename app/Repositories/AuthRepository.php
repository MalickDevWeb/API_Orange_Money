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

    public function login(array $credentials)
    {
        
    }

    public function logout()
    {
        // Logic for user logout
    }

    public function user()
    {
        // Logic to get the authenticated user
    }
}
