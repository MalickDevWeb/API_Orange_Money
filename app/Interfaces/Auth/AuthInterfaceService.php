<?php

namespace App\Interfaces\Auth;

use App\Models\User;

interface AuthInterfaceService
{
    public function register(array $data): User;
    public function login(array $credentials): User;
    public function logout(): void;
    public function user(): User;
}
