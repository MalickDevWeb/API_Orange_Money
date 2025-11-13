<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Str;

class OtpCodeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des codes OTP de test...');

        // Récupérer quelques utilisateurs
        $admin = User::where('telephone', '770000000')->first();
        $client1 = User::where('telephone', '770000001')->first();
        $client2 = User::where('telephone', '770000002')->first();

        if ($admin && $client1 && $client2) {
            $otpCodes = [
                [
                    'user_id' => $admin->id,
                    'code' => '123456',
                    'type' => 'login',
                    'phone_number' => $admin->telephone,
                    'email' => $admin->email,
                    'expires_at' => now()->addMinutes(5),
                    'used_at' => null,
                    'attempts' => 0,
                ],
                [
                    'user_id' => $client1->id,
                    'code' => '234567',
                    'type' => 'login',
                    'phone_number' => $client1->telephone,
                    'email' => $client1->email,
                    'expires_at' => now()->addMinutes(5),
                    'used_at' => null,
                    'attempts' => 0,
                ],
                [
                    'user_id' => $client2->id,
                    'code' => '345678',
                    'type' => 'login',
                    'phone_number' => $client2->telephone,
                    'email' => $client2->email,
                    'expires_at' => now()->addMinutes(5),
                    'used_at' => null,
                    'attempts' => 0,
                ],
                // Code expiré pour test
                [
                    'user_id' => $admin->id,
                    'code' => '999999',
                    'type' => 'login',
                    'phone_number' => $admin->telephone,
                    'email' => $admin->email,
                    'expires_at' => now()->subMinutes(1),
                    'used_at' => null,
                    'attempts' => 2,
                ],
                // Code utilisé pour test
                [
                    'user_id' => $client1->id,
                    'code' => '111111',
                    'type' => 'login',
                    'phone_number' => $client1->telephone,
                    'email' => $client1->email,
                    'expires_at' => now()->addMinutes(5),
                    'used_at' => now(),
                    'attempts' => 1,
                ],
            ];

            foreach ($otpCodes as $otpData) {
                OtpCode::create($otpData);
            }

            $this->command->info('Codes OTP de test créés avec succès');
            $this->command->info('');
            $this->command->info('=== CODES OTP DISPONIBLES ===');
            $this->command->info('Admin (770000000): 123456 (valide)');
            $this->command->info('Client1 (770000001): 234567 (valide)');
            $this->command->info('Client2 (770000002): 345678 (valide)');
            $this->command->info('Admin (expiré): 999999 (expiré)');
            $this->command->info('Client1 (utilisé): 111111 (déjà utilisé)');
        } else {
            $this->command->warn('Utilisateurs non trouvés pour créer les codes OTP');
        }
    }
}
