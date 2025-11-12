<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Compte;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un admin
        $admin = User::updateOrCreate(
            ['telephone' => '771234567'],
            [
                'nom' => 'Admin',
                'prenom' => 'Test',
                'telephone' => '771234567',
                'email' => 'admin@test.com',
                'type' => 'admin',
                'password' => bcrypt('password123'),
                'statut' => 'actif'
            ]
        );

        // Créer un compte pour l'admin
        Compte::updateOrCreate(
            ['numero_compte' => 'ADMIN-001'],
            [
                'numero_compte' => 'ADMIN-001',
                'titulaire' => 'Admin Test',
                'utilisateur_id' => $admin->id,
                'statut' => 'actif'
            ]
        );

        // Créer un fournisseur en attente
        $supplier = User::updateOrCreate(
            ['telephone' => '772345678'],
            [
                'nom' => 'Fournisseur',
                'prenom' => 'Test',
                'telephone' => '772345678',
                'email' => 'supplier@test.com',
                'type' => 'commercant',
                'password' => bcrypt('password123'),
                'statut' => 'en_attente'
            ]
        );

        // Créer un client (automatiquement actif)
        $client = User::updateOrCreate(
            ['telephone' => '773456789'],
            [
                'nom' => 'Client',
                'prenom' => 'Test',
                'telephone' => '773456789',
                'email' => 'client@test.com',
                'type' => 'client',
                'password' => bcrypt('password123'),
                'statut' => 'actif'
            ]
        );

        // Créer un compte pour le client
        Compte::updateOrCreate(
            ['numero_compte' => 'CLIENT-001'],
            [
                'numero_compte' => 'CLIENT-001',
                'titulaire' => 'Client Test',
                'utilisateur_id' => $client->id,
                'statut' => 'actif'
            ]
        );

        echo "Test data created:\n";
        echo "Admin: admin@test.com / password123 (statut: actif)\n";
        echo "Supplier (pending approval): supplier@test.com / password123 (statut: en_attente)\n";
        echo "Client (auto-approved): client@test.com / password123 (statut: actif)\n";
    }
}
