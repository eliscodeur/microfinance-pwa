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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_agent')->unique();
            $table->string('nom');
            $table->string('telephone');
            $table->string('pin_hash')->nullable();
            $table->string('image')->nullable();
            $table->decimal('taux_commission', 5, 2)->default(0.00);
            $table->decimal('portefeuille_virtuel', 12, 2)->default(0.00);
            $table->boolean('actif')->default(true);
            $table->boolean('can_sync')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};