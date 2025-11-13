<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BalanceRequest;
use App\Models\User;
use Illuminate\Support\Str;

class BalanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des demandes de solde de test...');

        // Récupérer quelques utilisateurs
        $client1 = User::where('telephone', '770000001')->first();
        $client2 = User::where('telephone', '770000002')->first();
        $admin = User::where('type', 'admin')->first();

        if ($client1 && $client2 && $admin) {
            $balanceRequests = [
                [
                    'supplier_id' => $client1->id,
                    'admin_id' => $admin->id,
                    'montant' => 50000,
                    'statut' => 'en_attente',
                    'motif_rejet' => null,
                ],
                [
                    'supplier_id' => $client2->id,
                    'admin_id' => $admin->id,
                    'montant' => 75000,
                    'statut' => 'approuvee',
                    'motif_rejet' => null,
                ],
                [
                    'supplier_id' => $client1->id,
                    'admin_id' => $admin->id,
                    'montant' => 100000,
                    'statut' => 'rejetee',
                    'motif_rejet' => 'Motif non conforme à la politique',
                ],
            ];

            foreach ($balanceRequests as $requestData) {
                BalanceRequest::updateOrCreate(
                    [
                        'supplier_id' => $requestData['supplier_id'],
                        'montant' => $requestData['montant'],
                        'statut' => $requestData['statut']
                    ],
                    $requestData
                );
            }

            $this->command->info('Demandes de solde créées avec succès');
        } else {
            $this->command->warn('Utilisateurs non trouvés pour créer les demandes de solde');
        }
    }
}
