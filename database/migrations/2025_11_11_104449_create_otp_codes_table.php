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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id'); // Use string for UUID compatibility
            $table->string('code', 6);
            $table->string('phone_number');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->string('type')->default('login'); // login, registration, transaction
            $table->timestamps();

            // Remove foreign key constraint to avoid type mismatch
            $table->index(['phone_number', 'type', 'used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
