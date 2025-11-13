<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;

class CleanExpiredOtpCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:clean-expired {--dry-run : Afficher les codes qui seraient supprimés sans les supprimer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprimer tous les codes OTP expirés de la base de données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 Mode test : affichage des codes expirés sans suppression');
        }

        // Compter les codes expirés
        $expiredCodesCount = OtpCode::where('expires_at', '<', now())->count();

        if ($expiredCodesCount === 0) {
            $this->info('✅ Aucun code OTP expiré trouvé.');
            return;
        }

        $this->info("📊 {$expiredCodesCount} code(s) OTP expiré(s) trouvé(s).");

        if ($isDryRun) {
            // Afficher les détails des codes expirés
            $expiredCodes = OtpCode::where('expires_at', '<', now())
                ->orderBy('expires_at', 'desc')
                ->get();

            $this->table(
                ['ID', 'Téléphone', 'Type', 'Expiré le', 'Créé le'],
                $expiredCodes->map(function ($code) {
                    return [
                        $code->id,
                        $code->telephone,
                        $code->type,
                        $code->expires_at->format('Y-m-d H:i:s'),
                        $code->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            $this->warn("⚠️  Ces codes seraient supprimés en mode normal.");
        } else {
            // Supprimer réellement les codes expirés
            $deletedCount = OtpCode::where('expires_at', '<', now())->delete();

            $this->info("🗑️  {$deletedCount} code(s) OTP expiré(s) supprimé(s) avec succès.");
            $this->info('✅ Nettoyage terminé.');
        }
    }
}
