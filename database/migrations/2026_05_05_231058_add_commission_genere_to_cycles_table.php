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
            // Ajout de la colonne pour éviter les doubles commissions
            $table->boolean('commission_genere')->default(false)->after('statut');
        });
    }

    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('commission_genere');
        });
    }
};
