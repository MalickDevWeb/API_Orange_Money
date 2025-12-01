<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveTestSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'papa1732';

        $this->command->info('Création de 5 utilisateurs seulement...');

        // === LES 5 UTILISATEURS ===
        $users = [
            [
                'nom' => 'Cotch',
                'prenom' => 'Wane',
                'telephone' => '770000000',
                'email' => 'pppttt1732@gmail.com',
                'type' => 'admin',
            ],
            [
                'nom' => 'Papa',
                'prenom' => 'Client1',
                'telephone' => '770000001',
                'email' => 'demon001teuw@gmail.com',
                'type' => 'client',
            ],
            [
                'nom' => 'Papa',
                'prenom' => 'Client2',
                'telephone' => '770000002',
                'email' => 'demon002teuw@gmail.com',
                'type' => 'client',
            ],
            [
                'nom' => 'Fallou',
                'prenom' => 'marchant',
                'telephone' => '770000004',
                'email' => 'teuwpapamalick1732@gmail.com',
                'type' => 'commercant',
            ],
            [
                'nom' => 'Allasane',
                'prenom' => 'Fournisseur',
                'telephone' => '770000003',
                'email' => 'demon003teuw@gmail.com',
                'type' => 'fournisseur',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['telephone' => $userData['telephone']],
                array_merge($userData, [
                    'password' => Hash::make($password),
                    'statut' => 'actif',
                ])
            );
            $this->command->info("Utilisateur créé: {$user->nom} {$user->prenom} ({$user->telephone}) - {$user->type}");
        }

        // === TRANSACTIONS DE TEST ===
        $this->command->info('Création des transactions de test...');

        // Récupérer quelques comptes pour créer des transactions
        $client1 = User::where('telephone', '770000001')->first();
        $client2 = User::where('telephone', '770000002')->first();
        $commercant1 = User::where('telephone', '770000004')->first();

        if ($client1 && $client2 && $commercant1) {
            $compteClient1 = $client1->comptes->first();
            $compteClient2 = $client2->comptes->first();
            $compteCommercant1 = $commercant1->comptes->first();

            if ($compteClient1 && $compteClient2 && $compteCommercant1) {
                // Transfert entre clients
                Transaction::create([
                    'type' => 'transfert',
                    'montant' => 500000,
                    'reference' => 'TRF-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => $compteClient1->id,
                    'compte_recepteur_id' => $compteClient2->id,
                    'date_transaction' => now(),
                ]);

                // Paiement à un commerçant
                Transaction::create([
                    'type' => 'paiement',
                    'montant' => 500000,
                    'reference' => 'PAY-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => $compteClient1->id,
                    'compte_recepteur_id' => $compteCommercant1->id,
                    'date_transaction' => now(),
                ]);

                // Dépôt
                Transaction::create([
                    'type' => 'depot',
                    'montant' => 500000,
                    'reference' => 'DEP-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => null,
                    'compte_recepteur_id' => $compteClient1->id,
                    'date_transaction' => now(),
                ]);

                // Retrait
                Transaction::create([
                    'type' => 'retrait',
                    'montant' => 500000,
                    'reference' => 'RET-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => $compteClient1->id,
                    'compte_recepteur_id' => null,
                    'date_transaction' => now(),
                ]);

                $this->command->info('Transactions de test créées avec succès');
            }
        }

        // === RÉSUMÉ ===
        $this->command->info('=== RÉSUMÉ ===');
        $this->command->info('Utilisateurs créés: ' . User::count());
        $this->command->info('Mot de passe pour tous: ' . $password);

        $this->command->info('');
        $this->command->info('=== LISTE DES UTILISATEURS ===');
        User::all()->each(function($user) {
            $this->command->info("  - {$user->nom} {$user->prenom}: {$user->telephone} ({$user->type})");
        });
    }
}

