<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_batch_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_batch_id')->constrained()->onDelete('cascade');
            $table->string('cycle_uid')->nullable()->index();
            $table->foreignId('carnet_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->integer('montant_journalier');
            $table->integer('nombre_jours_objectif')->default(31);
            $table->enum('statut', ['en_cours', 'termine', 'annule'])->default('en_cours');
            $table->timestamp('date_debut')->nullable();
            $table->timestamp('date_fin_prevue')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batch_cycles');
    }
};
