<?php

namespace App\DTOs;

use Illuminate\Support\Facades\Hash;

class UserDTO
{
    public static function fromArray(array $data): array
    {
        return [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => $data['type'],
        ];
    }
}
