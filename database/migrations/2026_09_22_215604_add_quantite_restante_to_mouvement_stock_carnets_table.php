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
        Schema::table('mouvement_stock_carnets', function (Blueprint $table) {
            // On ajoute la colonne après la quantité existante
            $table->integer('quantite_restante')->default(0)->after('quantite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_stock_carnets', function (Blueprint $table) {
            $table->dropColumn('quantite_restante');
        });
    }
};
