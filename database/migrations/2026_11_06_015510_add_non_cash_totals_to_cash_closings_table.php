<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_closings', function (Blueprint $table) {
            $table->decimal('total_entrees_hors_especes', 15, 2)->default(0);
            $table->decimal('total_sorties_hors_especes', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('cash_closings', function (Blueprint $table) {
            $table->dropColumn(['total_entrees_hors_especes', 'total_sorties_hors_especes']);
        });
    }
};
