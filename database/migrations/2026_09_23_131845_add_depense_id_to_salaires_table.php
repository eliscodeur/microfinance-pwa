<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaires', function (Blueprint $table) {
            // Ajout de la clé étrangère liée à la table des dépenses
            $table->foreignId('depense_id')
                ->nullable()
                ->after('id') // Optionnel: positionne la colonne après l'id
                ->constrained('depenses')
                ->nullOnDelete(); // Si la dépense est supprimée, on remet depense_id à NULL au lieu de supprimer le salaire
        });
    }

    public function down(): void
    {
        Schema::table('salaires', function (Blueprint $table) {
            $table->dropForeign(['depense_id']);
            $table->dropColumn('depense_id');
        });
    }
};
