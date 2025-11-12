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
        Schema::create('balance_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->foreign('supplier_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('montant', 15, 2);
            $table->string('statut')->default('en_attente'); // en_attente, approuvee, rejetee
            $table->text('motif_rejet')->nullable();
            $table->uuid('admin_id')->nullable(); // Admin qui traite la demande
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('traitee_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_requests');
    }
};
