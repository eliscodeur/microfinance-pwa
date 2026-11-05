<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('salaires', 'taux_carnet')) {
            Schema::table('salaires', function (Blueprint $table) {
                $table->decimal('taux_carnet', 5, 2)->nullable()->after('bonus');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('salaires', 'taux_carnet')) {
            Schema::table('salaires', function (Blueprint $table) {
                $table->dropColumn('taux_carnet');
            });
        }
    }
};
