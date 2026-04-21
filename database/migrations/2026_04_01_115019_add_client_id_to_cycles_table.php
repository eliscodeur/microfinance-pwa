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
        Schema::table('cycles', function (Blueprint $table) {
        // On ajoute client_id après l'id ou après agent_id
            $table->foreignId('client_id')
              ->nullable() // nullable au cas où tu as déjà des données
              ->after('id') 
              ->constrained('clients')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            });
    }
};
