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
            $this->info('🔍 Mode test : affichage des codes expirés et utilisés sans suppression');
        }

        // Compter les codes expirés
        $expiredCodesCount = OtpCode::where('expires_at', '<', now())->count();

        // Compter les codes utilisés (expirés depuis plus d'1 heure)
        $usedCodesCount = OtpCode::whereNotNull('used_at')
            ->where('expires_at', '<', now()->subHour())
            ->count();

        $totalCodesToClean = $expiredCodesCount + $usedCodesCount;

        if ($totalCodesToClean === 0) {
            $this->info('✅ Aucun code OTP expiré ou utilisé trouvé.');
            return;
        }

        $this->info("📊 {$expiredCodesCount} code(s) OTP expiré(s) trouvé(s).");
        $this->info("📊 {$usedCodesCount} code(s) OTP utilisé(s) trouvé(s).");
        $this->info("📊 Total: {$totalCodesToClean} code(s) à nettoyer.");

        if ($isDryRun) {
            // Afficher les détails des codes expirés
            if ($expiredCodesCount > 0) {
                $this->info("\n🔴 Codes OTP expirés:");
                $expiredCodes = OtpCode::where('expires_at', '<', now())
                    ->orderBy('expires_at', 'desc')
                    ->take(10) // Limiter l'affichage
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
            }

            // Afficher les détails des codes utilisés
            if ($usedCodesCount > 0) {
                $this->info("\n🔵 Codes OTP utilisés (expirés depuis > 1h):");
                $usedCodes = OtpCode::whereNotNull('used_at')
                    ->where('expires_at', '<', now()->subHour())
                    ->orderBy('used_at', 'desc')
                    ->take(10) // Limiter l'affichage
                    ->get();

                $this->table(
                    ['ID', 'Téléphone', 'Type', 'Utilisé le', 'Expiré le'],
                    $usedCodes->map(function ($code) {
                        return [
                            $code->id,
                            $code->telephone,
                            $code->type,
                            $code->used_at->format('Y-m-d H:i:s'),
                            $code->expires_at->format('Y-m-d H:i:s'),
                        ];
                    })
                );
            }

            $this->warn("⚠️  Ces codes seraient supprimés en mode normal.");
        } else {
            // Supprimer réellement les codes expirés
            $expiredDeleted = OtpCode::where('expires_at', '<', now())->delete();

            // Supprimer les codes utilisés (expirés depuis plus d'1 heure)
            $usedDeleted = OtpCode::whereNotNull('used_at')
                ->where('expires_at', '<', now()->subHour())
                ->delete();

            $totalDeleted = $expiredDeleted + $usedDeleted;

            $this->info("🗑️  {$expiredDeleted} code(s) OTP expiré(s) supprimé(s).");
            $this->info("🗑️  {$usedDeleted} code(s) OTP utilisé(s) supprimé(s).");
            $this->info("🗑️  Total: {$totalDeleted} code(s) supprimé(s).");
            $this->info('✅ Nettoyage terminé.');
        }
    }
}
