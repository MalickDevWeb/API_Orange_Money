<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index pour la table comptes
        Schema::table('comptes', function (Blueprint $table) {
            // Index sur statut (fréquemment filtré dans les requêtes)
            $table->index('statut');
            // Index composite pour optimiser les requêtes utilisateur + statut
            $table->index(['utilisateur_id', 'statut']);
        });

        // Index pour la table transactions
        Schema::table('transactions', function (Blueprint $table) {
            // Index sur statut (fréquemment filtré)
            $table->index('statut');
            // Index sur date_transaction pour les tris
            $table->index('date_transaction');
            // Index composite pour les transactions émises triées par date
            $table->index(['compte_emetteur_id', 'created_at']);
            // Index composite pour les transactions reçues triées par date
            $table->index(['compte_recepteur_id', 'created_at']);
            // Index composite pour filtrer par statut et date
            $table->index(['statut', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropIndex(['utilisateur_id', 'statut']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropIndex(['date_transaction']);
            $table->dropIndex(['compte_emetteur_id', 'created_at']);
            $table->dropIndex(['compte_recepteur_id', 'created_at']);
            $table->dropIndex(['statut', 'created_at']);
        });
    }
};
