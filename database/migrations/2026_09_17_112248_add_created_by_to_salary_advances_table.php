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
        Schema::table('salary_advances', function (Blueprint $table) {
            // Ajout de created_by après agent_id (ou où vous le souhaitez)
            if (! Schema::hasColumn('salary_advances', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('agent_id');
                // Optionnel : ajouter une contrainte de clé étrangère si votre table users existe
                // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }

            // S'assurer que approved_by existe aussi et accepte les valeurs nulles
            if (! Schema::hasColumn('salary_advances', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'approved_by']);
        });
    }
};
