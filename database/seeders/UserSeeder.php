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
        // Supprime tous les utilisateurs existants
        User::truncate();
        Compte::truncate();

        $password = 'papa1732';

        // Admin: Cotch Wane
        User::factory()->admin()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Cotch',
            'prenom' => 'Wane',
            'telephone' => '770000000',
            'email' => 'pppttt1732@gmail.com',
            'password' => Hash::make($password),
        ]);

        // Client: Papa Client1
        User::factory()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Papa',
            'prenom' => 'Client1',
            'telephone' => '770000001',
            'email' => 'demon001teuw@gmail.com',
            'type' => 'client',
            'password' => Hash::make($password),
        ]);

        // Client: Papa Client2
        User::factory()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Papa',
            'prenom' => 'Client2',
            'telephone' => '770000002',
            'email' => 'demon002teuw@gmail.com',
            'type' => 'client',
            'password' => Hash::make($password),
        ]);

        // Fournisseur: Allasane Fournisseur
        User::factory()->fournisseur()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Allasane',
            'prenom' => 'Fournisseur',
            'telephone' => '770000003',
            'email' => 'demon003teuw@gmail.com',
            'password' => Hash::make($password),
        ]);

        // Marchant: Fallou marchant
        User::factory()->commercant()->create([
            'id' => (string) Str::uuid(),
            'nom' => 'Fallou',
            'prenom' => 'marchant',
            'telephone' => '770000004',
            'email' => 'teuwpapamalick1732@gmail.com',
            'password' => Hash::make($password),
        ]);
    }
}
