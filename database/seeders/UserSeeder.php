<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Compte;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {


        // Supprime tous les utilisateurs existants pour éviter les doublons
        User::truncate();
        $password = 'papa1732';

        // Admin
        $admin = User::factory()->admin()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Papa',
            'prenom' => 'Admin',
            'telephone' => '770000001',
            'email' => 'papaadmin@gmail.com',
            'password' => Hash::make($password),
        ]);

        Compte::factory()->create([
            'id' => (string) Str::uuid(),
            'numero_compte' => 'CMPT-ADMIN-001',
            'titulaire' => $admin->nom . ' ' . $admin->prenom,
            'code_marchand' => null,
            'statut' => 'actif',
            'utilisateur_id' => $admin->id,
        ]);

        // Client
        $client = User::factory()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Papa',
            'prenom' => 'Client',
            'telephone' => '770000002',
            'email' => 'papaclient@gmail.com',
            'type' => 'client',
            'password' => Hash::make($password),
        ]);

        Compte::factory()->create([
            'id' => (string) Str::uuid(),
            'numero_compte' => 'CMPT-CLIENT-001',
            'titulaire' => $client->nom . ' ' . $client->prenom,
            'code_marchand' => null,
            'statut' => 'actif',
            'utilisateur_id' => $client->id,
        ]);

        // Commerçant
        $commercant = User::factory()->commercant()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Papa',
            'prenom' => 'Commercant',
            'telephone' => '770000003',
            'email' => 'papacommercant@gmail.com',
            'password' => Hash::make($password),
        ]);

        Compte::factory()->marchand()->create([
            'id' => (string) Str::uuid(),
            'numero_compte' => 'CMPT-MRC-001',
            'titulaire' => $commercant->nom . ' ' . $commercant->prenom,
            'statut' => 'actif',
            'utilisateur_id' => $commercant->id,
        ]);
    }
}
