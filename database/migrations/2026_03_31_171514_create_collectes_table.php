<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            
            // Liens hiérarchiques (Relations)
            $table->foreignId('cycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            
            // Détails du pointage
            $table->integer('pointage'); // 
            $table->decimal('montant', 15, 2); // Le montant de la mise au moment du pointage
            
            // Tracabilité temporelle
            $table->timestamp('date_saisie'); // La date/heure RÉELLE du pointage sur le terrain (mobile)
            
            // Pour la synchronisation
            $table->string('sync_uuid')->nullable(); // Optionnel : pour lier à une session de synchro précise
            
            $table->timestamps(); // created_at (date d'arrivée sur le serveur)
            
            // Index pour la performance des rapports
            $table->index(['cycle_id', 'pointage']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collectes');
    }
};