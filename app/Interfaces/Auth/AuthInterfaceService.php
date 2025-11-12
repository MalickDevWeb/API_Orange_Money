<?php

namespace App\Interfaces\Auth;

use App\Models\User;

interface AuthInterfaceService
{
    public function register(array $data): User;
    public function login(array $credentials): array;
    public function verifyOtp(array $data): array;
    public function sendLogoutOtp(): array;
    public function verifyLogoutOtp(array $data): array;
    public function logout(): void;
    public function user(): User;
}
