<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Récupère les comptes
    
        $adminCompte = Compte::where('numero_compte', 'CMPT-ADMIN-001')->first();
        $clientCompte = Compte::where('numero_compte', 'CMPT-CLIENT-001')->first();
        $commercantCompte = Compte::where('numero_compte', 'CMPT-MRC-001')->first();

        if ($adminCompte && $commercantCompte) {
            // Approvisionnement commerçant par l'admin
            Transaction::factory()->create([
                'id' => (string) Str::uuid(),
                'type' => 'depot',
                'montant' => 100000, // par ex.
                'reference' => strtoupper(Str::random(12)),
                'statut' => 'reussie',
                'compte_emetteur_id' => $adminCompte->id,
                'compte_recepteur_id' => $commercantCompte->id,
                'note' => 'Approvisionnement du commerçant par l’admin',
                'date_transaction' => now(),
            ]);
        }

        if ($commercantCompte && $clientCompte) {
            // Dépôt du client par le commerçant
            Transaction::factory()->create([
                'id' => (string) Str::uuid(),
                'type' => 'depot',
                'montant' => 50000,
                'reference' => strtoupper(Str::random(12)),
                'statut' => 'reussie',
                'compte_emetteur_id' => $commercantCompte->id,
                'compte_recepteur_id' => $clientCompte->id,
                'note' => 'Dépôt sur compte client',
                'date_transaction' => now(),
            ]);
        }

        if ($clientCompte && $commercantCompte) {
            // Paiement du client au commerçant
            Transaction::factory()->create([
                'id' => (string) Str::uuid(),
                'type' => 'paiement',
                'montant' => 20000,
                'reference' => strtoupper(Str::random(12)),
                'statut' => 'reussie',
                'compte_emetteur_id' => $clientCompte->id,
                'compte_recepteur_id' => $commercantCompte->id,
                'note' => 'Paiement d’un achat par le client',
                'date_transaction' => now(),
            ]);
        }
    }
}
