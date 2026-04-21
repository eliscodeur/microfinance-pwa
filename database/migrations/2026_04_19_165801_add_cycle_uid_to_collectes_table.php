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
        // On vérifie si la colonne n'existe pas déjà pour éviter l'erreur
        if (!Schema::hasColumn('collectes', 'cycle_uid')) {
            Schema::table('collectes', function (Blueprint $table) {
                // after('cycle_id') permet de bien positionner la colonne visuellement
                $table->string('cycle_uid', 36)->nullable()->after('cycle_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collectes', function (Blueprint $table) {
            // On vérifie que la colonne existe avant de tenter de la supprimer
            if (Schema::hasColumn('collectes', 'cycle_uid')) {
                $table->dropColumn('cycle_uid');
            }
        });
    }
};