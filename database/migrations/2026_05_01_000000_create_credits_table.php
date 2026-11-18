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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->char('credit_uid', 36)->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('carnet_id')->nullable();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('credit_product_id');
            $table->unsignedBigInteger('credit_object_id')->nullable();
            $table->string('type_support')->default('compte');
            $table->decimal('montant_demande', 15, 2);
            $table->decimal('montant_accorde', 15, 2)->nullable();
            $table->decimal('taux', 8, 4);
            $table->decimal('taux_manuel', 8, 4)->nullable();
            $table->string('mode')->default('degressif');
            $table->string('periodicite')->default('mensuelle');
            $table->integer('nombre_echeances')->default(1);
            $table->integer('differe')->default(0);
            $table->decimal('montant_echeance_differe', 15, 0)->nullable();
            $table->decimal('frais_dossier', 15, 2)->default(0.00);
            $table->decimal('montant_echeance', 15, 2)->nullable();
            $table->decimal('interet_total', 15, 2)->default(0.00);
            $table->decimal('montant_rembourse', 15, 2)->default(0.00);
            $table->decimal('blocked_amount', 15, 2)->default(0.00);
            $table->decimal('penalty_amount', 15, 2)->default(0.00);
            $table->string('statut')->default('pending');
            $table->date('date_demande')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
