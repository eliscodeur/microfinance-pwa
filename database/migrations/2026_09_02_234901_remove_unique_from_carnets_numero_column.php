<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carnets', function (Blueprint $table) {
            // Supprime la contrainte d'unicité sur la colonne numero
            $table->dropUnique('carnets_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::table('carnets', function (Blueprint $table) {
            // Remet l'index unique si un jour vous souhaitez faire un rollback
            $table->unique('numero');
        });
    }
};
