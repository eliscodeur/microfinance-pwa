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
        Schema::create('salaires', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference')->unique(); // Ex: SAL-202609-00123
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');

            $table->unsignedTinyInteger('mois');
            $table->unsignedSmallInteger('annee');
            $table->date('periode_debut');
            $table->date('periode_fin');

            // Montant et Statut
            $table->decimal('montant_net', 12, 2);
            $table->string('statut')->default('valide');

            // Traçabilité de la validation
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            // Un seul salaire par agent et par mois
            $table->unique(['agent_id', 'mois', 'annee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaires');
    }
};
