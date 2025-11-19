<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanOrphanedMerchantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merchants:clean-orphaned {--dry-run : Afficher les commerçants qui seraient supprimés sans les supprimer} {--force : Forcer la suppression sans période de grâce}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprimer tous les commerçants qui n\'ont pas de code marchand';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        if ($isDryRun) {
            $this->info('🔍 Mode test : affichage des commerçants orphelins sans suppression');
        }

        // Compter les commerçants totaux
        $totalMerchants = User::where('type', 'commercant')->count();

        // Construire la requête pour les commerçants orphelins
        $query = User::where('type', 'commercant')
            ->where(function ($q) {
                $q->whereNull('code_marchand')
                  ->orWhere('code_marchand', '');
            });

        // Appliquer la période de grâce sauf si --force
        if (!$isForce) {
            $query->where('created_at', '<', now()->subDay());
            $this->info('⏰ Période de grâce : 24 heures (utiliser --force pour ignorer)');
        } else {
            $this->warn('⚠️ Mode forcé : Aucune période de grâce appliquée');
        }

        $orphanedMerchants = $query->get();
        $orphanedCount = $orphanedMerchants->count();

        if ($orphanedCount === 0) {
            $this->info('✅ Aucun commerçant orphelin trouvé.');
            $this->info("📊 Total de commerçants : {$totalMerchants}");
            return;
        }

        $this->info("📊 {$totalMerchants} commerçant(s) au total");
        $this->info("🗑️  {$orphanedCount} commerçant(s) orphelin(s) trouvé(s).");

        if ($isDryRun) {
            // Afficher les détails des commerçants orphelins
            $this->table(
                ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Créé le'],
                $orphanedMerchants->map(function ($merchant) {
                    return [
                        $merchant->id,
                        $merchant->nom,
                        $merchant->prenom,
                        $merchant->email,
                        $merchant->telephone,
                        $merchant->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            $this->warn("⚠️  Ces commerçants seraient supprimés en mode normal.");
        } else {
            // Demander confirmation sauf si --force
            if (!$isForce && !$this->confirm("Êtes-vous sûr de vouloir supprimer {$orphanedCount} commerçant(s) orphelin(s) ?")) {
                $this->info('❌ Opération annulée.');
                return;
            }

            // Supprimer réellement les commerçants orphelins
            $deletedCount = 0;
            $deletedEmails = [];

            foreach ($orphanedMerchants as $merchant) {
                $deletedEmails[] = $merchant->email;
                $merchant->delete();
                $deletedCount++;
            }

            $remainingMerchants = User::where('type', 'commercant')->count();

            $this->info("🗑️  {$deletedCount} commerçant(s) orphelin(s) supprimé(s) avec succès.");
            $this->info("📊 Commerçants restants : {$remainingMerchants}");

            // Afficher les emails supprimés
            if (!empty($deletedEmails)) {
                $this->info("📧 Commerçants supprimés :");
                foreach ($deletedEmails as $email) {
                    $this->line("   - {$email}");
                }
            }

            $this->info('✅ Nettoyage terminé.');
        }
    }
}
