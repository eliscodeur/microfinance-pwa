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
        Schema::table('bonuses', function (Blueprint $table) {
            // Ajoute le statut pour suivre l'état du bonus sans dépendre uniquement de paiement_id
            $table->string('statut')->default('en_attente')->after('montant');
            // 'en_attente', 'valide', 'refuse'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bonuses', function (Blueprint $table) {
            //
        });
    }
};
