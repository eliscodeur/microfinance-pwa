<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // On s'assure que la table est supprimée avant de la créer
        Schema::dropIfExists('cycles');

        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            
            // Relation avec le carnet
            $table->foreignId('carnet_id')->constrained()->onDelete('cascade');
            
            // Relation avec l'agent (on utilise la table users ici pour plus de simplicité)
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->integer('montant_journalier');
            $table->integer('nombre_jours_objectif')->default(31); 
            $table->enum('statut', ['en_cours', 'termine', 'annule'])->default('en_cours');
            
            $table->date('date_debut');
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_cloture_reelle')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cycles');
    }
};