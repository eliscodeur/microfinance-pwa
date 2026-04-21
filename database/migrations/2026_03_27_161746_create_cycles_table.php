<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            // Liaison avec le carnet
            $table->foreignId('carnet_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            
            // Paramètres du cycle
            $table->integer('montant_journalier'); // ex: 500, 1000
            $table->integer('nombre_jours_objectif')->default(31); 
            
            // État du cycle
            $table->enum('statut', ['en_cours', 'termine', 'annule'])->default('en_cours');
            
            // Dates
            $table->date('date_debut');
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_cloture_reelle')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cycles');
    }
};
