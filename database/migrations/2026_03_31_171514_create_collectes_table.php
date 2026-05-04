<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('collectes', function (Blueprint $table) {
        //     $table->id();
            
        //     // Liens hiérarchiques (Relations)
        //     $table->foreignId('cycle_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('client_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            
        //     // Détails du pointage
        //     $table->integer('pointage'); // 
        //     $table->decimal('montant', 15, 2); // Le montant de la mise au moment du pointage
            
        //     // Tracabilité temporelle
        //     $table->timestamp('date_saisie'); // La date/heure RÉELLE du pointage sur le terrain (mobile)
            
        //     // Pour la synchronisation
        //     $table->string('sync_uuid')->nullable(); // Optionnel : pour lier à une session de synchro précise
            
        //     $table->timestamps(); // created_at (date d'arrivée sur le serveur)
            
        //     // Index pour la performance des rapports
        //     $table->index(['cycle_id', 'pointage']); 
        // });
        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            
            // UNIQUE ID (Provenance Mobile/Dexie)
            // C'est cet index qui permet l'upsert sans doublons
            $table->string('collecte_uid')->unique(); 

            // Liens hiérarchiques (Relations)
            $table->foreignId('cycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            
            // Détails du pointage
            $table->integer('pointage'); 
            $table->decimal('montant', 15, 2); 
            
            // Tracabilité temporelle
            $table->timestamp('date_saisie'); 
            
            // Pour la synchronisation
            $table->string('sync_uuid')->nullable();
            $table->string('cycle_uid')->nullable(); // Utile pour garder la trace du lien mobile
            
            $table->timestamps(); 

            // Index pour la performance
            $table->index(['cycle_id', 'pointage']); 
            $table->index('sync_uuid'); // Très utile pour tes rapports par session de synchro
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collectes');
    }
};