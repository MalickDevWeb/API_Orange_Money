<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Compte;
use Illuminate\Support\Str;
use App\Utils\GeneratesCompteNumber;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        // Récupère les utilisateurs existants par email
        $users = [
            'papaadmin@gmail.com',
            'papaclient@gmail.com',
            'papacommercant@gmail.com',
        ];

        foreach ($users as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
          
                $numeroCompte = GeneratesCompteNumber::generateNumeroCompte($user->nom, $user->prenom);

                Compte::factory()->create([
                    'id' => (string) Str::uuid(),
                    'numero_compte' => $numeroCompte,
                    'titulaire' => $user->nom . ' ' . $user->prenom,
                    'statut' => 'actif',
                    'utilisateur_id' => $user->id,
                ]);
            }
        }
    }
}
