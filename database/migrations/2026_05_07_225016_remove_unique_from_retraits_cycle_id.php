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
        Schema::table('retraits', function (Blueprint $table) {
            // 1. On supprime d'abord la clé étrangère qui bloque l'index
            $table->dropForeign(['cycle_id']);

            // 2. Ensuite on peut supprimer l'index unique sans erreur
            $table->dropUnique(['cycle_id']);
        });
    }

    public function down()
    {
        Schema::table('retraits', function (Blueprint $table) {
            $table->unique('cycle_id');
            $table->foreign('cycle_id')->references('id')->on('cycles');
        });
    }
};
