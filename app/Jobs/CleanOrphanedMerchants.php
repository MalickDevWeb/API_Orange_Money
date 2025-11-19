<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class CleanOrphanedMerchants implements ShouldQueue
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
            // Compter les commerçants avant nettoyage
            $totalMerchantsBefore = User::where('type', 'commercant')->count();

            // Trouver les commerçants sans code marchand
            // Avec une période de grâce de 24h (pour éviter de supprimer des comptes en cours de création)
            $orphanedMerchants = User::where('type', 'commercant')
                ->where(function ($query) {
                    $query->whereNull('code_marchand')
                          ->orWhere('code_marchand', '');
                })
                ->where('created_at', '<', now()->subDay()) // Créés il y a plus de 24h
                ->get();

            $orphanedCount = $orphanedMerchants->count();

            if ($orphanedCount === 0) {
                Log::info('Nettoyage commerçants orphelins: Aucun commerçant sans code marchand trouvé', [
                    'total_commercants' => $totalMerchantsBefore,
                    'timestamp' => now()->toISOString()
                ]);
                return;
            }

            // Collecter les IDs et informations avant suppression
            $deletedMerchantIds = [];
            $deletedMerchantEmails = [];

            foreach ($orphanedMerchants as $merchant) {
                $deletedMerchantIds[] = $merchant->id;
                $deletedMerchantEmails[] = $merchant->email;

                // Supprimer le commerçant (les comptes associés seront supprimés en cascade)
                $merchant->delete();
            }

            $totalMerchantsAfter = User::where('type', 'commercant')->count();

            // Log les statistiques détaillées
            Log::info('Nettoyage commerçants orphelins terminé', [
                'total_commercants_avant' => $totalMerchantsBefore,
                'total_commercants_apres' => $totalMerchantsAfter,
                'commercants_supprimes' => $orphanedCount,
                'ids_supprimes' => $deletedMerchantIds,
                'emails_supprimes' => $deletedMerchantEmails,
                'periode_grace' => '24 heures',
                'timestamp' => now()->toISOString()
            ]);

            // Alerte si beaucoup de commerçants sont supprimés
            if ($orphanedCount > 10) {
                Log::warning('Nombre élevé de commerçants orphelins supprimés', [
                    'commercants_supprimes' => $orphanedCount,
                    'seuil_alerte' => 10,
                    'emails_concernes' => $deletedMerchantEmails
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage des commerçants orphelins', [
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
        return ['merchants', 'cleanup', 'orphaned', 'maintenance'];
    }
}
