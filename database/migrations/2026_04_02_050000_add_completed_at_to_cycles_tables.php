<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('date_fin_prevue');
        });

        Schema::table('sync_batch_cycles', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('date_fin_prevue');
        });
    }

    public function down(): void
    {
        Schema::table('sync_batch_cycles', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
