<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\OtpCode;

class CleanExpiredOtpCodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Compter les codes avant nettoyage
            $totalBefore = OtpCode::count();

            // Supprimer les codes OTP expirés (expires_at < maintenant)
            $expiredDeleted = OtpCode::where('expires_at', '<', now())->delete();

            // Supprimer les codes OTP utilisés (used_at non null et expires_at < maintenant - 1 heure)
            // Pour garder un historique des codes utilisés récemment
            $usedDeleted = OtpCode::whereNotNull('used_at')
                ->where('expires_at', '<', now()->subHour())
                ->delete();

            $totalDeleted = $expiredDeleted + $usedDeleted;
            $totalAfter = OtpCode::count();

            // Log les statistiques
            Log::info('Nettoyage codes OTP terminé', [
                'avant_nettoyage' => $totalBefore,
                'apres_nettoyage' => $totalAfter,
                'codes_expires_supprimes' => $expiredDeleted,
                'codes_utilises_supprimes' => $usedDeleted,
                'total_supprimes' => $totalDeleted,
                'timestamp' => now()->toISOString()
            ]);

            // Log un avertissement si beaucoup de codes sont supprimés
            if ($totalDeleted > 100) {
                Log::warning('Nombre élevé de codes OTP supprimés', [
                    'total_supprimes' => $totalDeleted,
                    'seuil' => 100
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage des codes OTP', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            // Relancer l'exception pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Définir les tags pour le job
     */
    public function tags(): array
    {
        return ['otp', 'cleanup', 'maintenance'];
    }
}
