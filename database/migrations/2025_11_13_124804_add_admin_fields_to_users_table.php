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
            $table->boolean('transfer_enabled')->default(true);
            $table->boolean('can_transfer_to_client')->default(true);
            $table->boolean('can_pay_merchant')->default(true);
            $table->boolean('banned')->default(false);
            $table->decimal('tax_percentage', 5, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['transfer_enabled', 'can_transfer_to_client', 'can_pay_merchant', 'banned', 'tax_percentage']);
        });
    }
};
