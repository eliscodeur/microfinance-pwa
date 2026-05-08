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
            // On supprime l'index unique
            $table->dropUnique(['cycle_id']); 
            // Note: Si le nom automatique ne marche pas, essaie :
            // $table->dropUnique('retraits_cycle_id_unique');
        });
    }

    public function down()
    {
        Schema::table('retraits', function (Blueprint $table) {
            $table->unique('cycle_id');
        });
    }
};
