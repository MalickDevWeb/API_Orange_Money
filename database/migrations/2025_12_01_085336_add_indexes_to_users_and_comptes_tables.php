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
        Schema::table('users', function (Blueprint $table) {
            $table->index('telephone');
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->index('numero_compte');
            $table->index('nom_compte');
        });
    }

    /**ua
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['telephone']);
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->dropIndex(['numero_compte']);
            $table->dropIndex(['nom_compte']);
        });
    }
};
