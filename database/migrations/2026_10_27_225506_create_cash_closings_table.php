<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('fond_caisse_initial', 15, 2)->default(0);
            $table->decimal('total_entrees', 15, 2)->default(0);
            $table->decimal('total_sorties', 15, 2)->default(0);
            $table->decimal('solde_theorique', 15, 2)->default(0);
            $table->decimal('solde_physique', 15, 2)->default(0);
            $table->decimal('ecart', 15, 2)->default(0);
            $table->text('commentaire')->nullable();
            $table->dateTime('date_cloture');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
    }
};
