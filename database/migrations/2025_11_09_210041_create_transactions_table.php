<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); //type de transaction
            $table->float('montant', 15, 2);
            $table->string('reference')->unique();
            $table->string('statut'); // etat de la transaction
            $table->text('note')->nullable();
            $table->uuid('compte_emetteur_id');
            $table->uuid('compte_recepteur_id');
            $table->foreign('compte_emetteur_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('compte_recepteur_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->timestamp('date_transaction');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
