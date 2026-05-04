<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->uuid('credit_uid')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carnet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('montant_demande', 15, 2);
            $table->decimal('montant_accorde', 15, 2)->nullable();
            $table->decimal('taux', 8, 4);
            $table->decimal('taux_manuelle', 8, 4)->nullable();
            $table->string('type')->default('compte');
            $table->string('mode')->default('degressif');
            $table->string('periodicite')->default('mensuelle');
            $table->integer('nombre_echeances')->default(1);
            $table->decimal('montant_echeance', 15, 2)->nullable();
            $table->decimal('interet_total', 15, 2)->default(0);
            $table->decimal('montant_rembourse', 15, 2)->default(0);
            $table->decimal('blocked_amount', 15, 2)->default(0);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->string('statut')->default('pending');
            $table->date('date_demande')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
