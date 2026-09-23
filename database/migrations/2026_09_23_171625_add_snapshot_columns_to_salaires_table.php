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
        Schema::table('salaires', function (Blueprint $table) {
            $table->decimal('salaire_base', 12, 2)->default(0)->after('agent_id');
            $table->decimal('commission_travail', 12, 2)->default(0)->after('salaire_base');
            $table->decimal('commission_carnet', 12, 2)->default(0)->after('commission_travail');
            $table->decimal('commission_cycle', 12, 2)->default(0)->after('commission_carnet');
            $table->decimal('bonus', 12, 2)->default(0)->after('commission_cycle');
            $table->decimal('total_avances', 12, 2)->default(0)->after('bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaires', function (Blueprint $table) {
            $table->dropColumn([
                'salaire_base',
                'commission_travail',
                'commission_carnet',
                'commission_cycle',
                'bonus',
                'total_avances',
            ]);
        });
    }
};
