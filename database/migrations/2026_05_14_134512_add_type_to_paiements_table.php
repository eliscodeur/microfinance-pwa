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
        Schema::table('paiements', function (Blueprint $table) {
            // Ajoute le champ type pour distinguer les versements réels des rejets administratifs
            $table->string('type')->default('deboursement')->after('montant_total'); 
            // Note: 'deboursement' pour les paiements effectifs, 'rejet' pour les refus
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paiements', function (Blueprint $table) {
            //
        });
    }
};
