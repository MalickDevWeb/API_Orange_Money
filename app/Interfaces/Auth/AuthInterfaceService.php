<?php

namespace App\Interfaces\Auth;

use App\Models\User;

interface AuthInterfaceService
{
    public function register(array $data): User;
    public function login(array $credentials);
    public function logout();
    public function user();
}
