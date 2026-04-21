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
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            // On ajoute la colonne agent_id après l'id
            // On la met en nullable au cas où tu as déjà des cycles existants sans agent
            $table->foreignId('agent_id')
                ->nullable() 
                ->after('id')
                ->constrained('agents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            // Pour pouvoir revenir en arrière
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });
    }
};
