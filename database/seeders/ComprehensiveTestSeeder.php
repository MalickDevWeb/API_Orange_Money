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

        // === ADMINISTRATEURS ===
        $this->command->info('Création des administrateurs...');

        $admins = [
            [
                'nom' => 'Cotch',
                'prenom' => 'Wane',
                'telephone' => '770000000',
                'email' => 'pppttt1732@gmail.com',
            ],
            [
                'nom' => 'Admin',
                'prenom' => 'Principal',
                'telephone' => '770000099',
                'email' => 'admin@orange-money.com',
            ],
        ];

        foreach ($admins as $adminData) {
            $admin = User::updateOrCreate(
                ['telephone' => $adminData['telephone']],
                array_merge($adminData, [
                    'type' => 'admin',
                    'password' => Hash::make($password),
                    'statut' => 'actif',
                ])
            );
            $this->command->info("Admin créé: {$admin->nom} {$admin->prenom} ({$admin->telephone})");
        }

        // === CLIENTS ===
        $this->command->info('Création des clients...');

        $clients = [
            [
                'nom' => 'Papa',
                'prenom' => 'Client1',
                'telephone' => '770000001',
                'email' => 'demon001teuw@gmail.com',
            ],
            [
                'nom' => 'Papa',
                'prenom' => 'Client2',
                'telephone' => '770000002',
                'email' => 'demon002teuw@gmail.com',
            ],
            [
                'nom' => 'Mamadou',
                'prenom' => 'Diallo',
                'telephone' => '770000010',
                'email' => 'mamadou.diallo@test.com',
            ],
            [
                'nom' => 'Fatou',
                'prenom' => 'Sow',
                'telephone' => '770000011',
                'email' => 'fatou.sow@test.com',
            ],
            [
                'nom' => 'Ibrahima',
                'prenom' => 'Ba',
                'telephone' => '770000012',
                'email' => 'ibrahima.ba@test.com',
            ],
        ];

        foreach ($clients as $clientData) {
            $client = User::updateOrCreate(
                ['telephone' => $clientData['telephone']],
                array_merge($clientData, [
                    'type' => 'client',
                    'password' => Hash::make($password),
                    'statut' => 'actif',
                ])
            );
            $this->command->info("Client créé: {$client->nom} {$client->prenom} ({$client->telephone})");
        }

        // === COMMERCANTS ===
        $this->command->info('Création des commerçants...');

        $commercants = [
            [
                'nom' => 'Fallou',
                'prenom' => 'marchant',
                'telephone' => '770000004',
                'email' => 'teuwpapamalick1732@gmail.com',
            ],
            [
                'nom' => 'Cheikh',
                'prenom' => 'Boutique',
                'telephone' => '770000020',
                'email' => 'cheikh.boutique@test.com',
            ],
            [
                'nom' => 'Aminata',
                'prenom' => 'Restaurant',
                'telephone' => '770000021',
                'email' => 'aminata.restaurant@test.com',
            ],
            [
                'nom' => 'Moussa',
                'prenom' => 'Pharmacie',
                'telephone' => '770000022',
                'email' => 'moussa.pharmacie@test.com',
            ],
        ];

        foreach ($commercants as $commercantData) {
            $commercant = User::updateOrCreate(
                ['telephone' => $commercantData['telephone']],
                array_merge($commercantData, [
                    'type' => 'commercant',
                    'password' => Hash::make($password),
                    'statut' => 'actif',
                ])
            );
            $this->command->info("Commerçant créé: {$commercant->nom} {$commercant->prenom} ({$commercant->telephone})");
        }

        // === FOURNISSEURS ===
        $this->command->info('Création des fournisseurs...');

        $fournisseurs = [
            [
                'nom' => 'Allasane',
                'prenom' => 'Fournisseur',
                'telephone' => '770000003',
                'email' => 'demon003teuw@gmail.com',
            ],
            [
                'nom' => 'Ousmane',
                'prenom' => 'Import',
                'telephone' => '770000030',
                'email' => 'ousmane.import@test.com',
            ],
            [
                'nom' => 'Khadija',
                'prenom' => 'Distribution',
                'telephone' => '770000031',
                'email' => 'khadija.distribution@test.com',
            ],
        ];

        foreach ($fournisseurs as $fournisseurData) {
            $fournisseur = User::updateOrCreate(
                ['telephone' => $fournisseurData['telephone']],
                array_merge($fournisseurData, [
                    'type' => 'fournisseur',
                    'password' => Hash::make($password),
                    'statut' => 'actif',
                ])
            );
            $this->command->info("Fournisseur créé: {$fournisseur->nom} {$fournisseur->prenom} ({$fournisseur->telephone})");
        }

        // === UTILISATEURS EN ATTENTE ===
        $this->command->info('Création des utilisateurs en attente...');

        $pendingUsers = [
            [
                'nom' => 'Nouvel',
                'prenom' => 'Client',
                'telephone' => '770000040',
                'email' => 'nouvel.client@test.com',
                'type' => 'client',
            ],
            [
                'nom' => 'Nouveau',
                'prenom' => 'Commercant',
                'telephone' => '770000041',
                'email' => 'nouveau.commercant@test.com',
                'type' => 'commercant',
            ],
        ];

        foreach ($pendingUsers as $pendingData) {
            $pending = User::updateOrCreate(
                ['telephone' => $pendingData['telephone']],
                array_merge($pendingData, [
                    'password' => Hash::make($password),
                    'statut' => 'en_attente',
                ])
            );
            $this->command->info("Utilisateur en attente créé: {$pending->nom} {$pending->prenom} ({$pending->telephone}) - {$pending->type}");
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
                    'montant' => 50000,
                    'reference' => 'TRF-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => $compteClient1->id,
                    'compte_recepteur_id' => $compteClient2->id,
                    'date_transaction' => now(),
                ]);

                // Paiement à un commerçant
                Transaction::create([
                    'type' => 'paiement',
                    'montant' => 25000,
                    'reference' => 'PAY-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => $compteClient1->id,
                    'compte_recepteur_id' => $compteCommercant1->id,
                    'date_transaction' => now(),
                ]);

                // Dépôt
                Transaction::create([
                    'type' => 'depot',
                    'montant' => 100000,
                    'reference' => 'DEP-' . strtoupper(Str::random(8)),
                    'statut' => 'reussie',
                    'compte_emetteur_id' => null,
                    'compte_recepteur_id' => $compteClient1->id,
                    'date_transaction' => now(),
                ]);

                // Retrait
                Transaction::create([
                    'type' => 'retrait',
                    'montant' => 30000,
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
        $this->command->info('=== RÉSUMÉ DES DONNÉES CRÉÉES ===');
        $this->command->info('Utilisateurs: ' . User::count());
        $this->command->info('Comptes: ' . Compte::count());
        $this->command->info('Transactions: ' . Transaction::count());

        $this->command->info('');
        $this->command->info('=== INFORMATIONS DE CONNEXION ===');
        $this->command->info('Mot de passe pour tous les comptes: ' . $password);
        $this->command->info('');
        $this->command->info('Admins actifs:');
        User::where('type', 'admin')->where('statut', 'actif')->each(function($user) {
            $this->command->info("  - {$user->nom} {$user->prenom}: {$user->telephone} / {$user->email}");
        });

        $this->command->info('');
        $this->command->info('Clients actifs:');
        User::where('type', 'client')->where('statut', 'actif')->each(function($user) {
            $this->command->info("  - {$user->nom} {$user->prenom}: {$user->telephone} / {$user->email}");
        });

        $this->command->info('');
        $this->command->info('Commerçants actifs:');
        User::where('type', 'commercant')->where('statut', 'actif')->each(function($user) {
            $this->command->info("  - {$user->nom} {$user->prenom}: {$user->telephone} / {$user->email}");
        });

        $this->command->info('');
        $this->command->info('Codes marchands disponibles:');
        Compte::whereNotNull('code_marchand')->each(function($compte) {
            $user = $compte->utilisateur;
            $this->command->info("  - {$user->nom} {$user->prenom}: {$compte->code_marchand}");
        });
    }
}
