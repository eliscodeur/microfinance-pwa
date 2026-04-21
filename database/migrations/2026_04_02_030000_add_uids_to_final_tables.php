<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->string('cycle_uid')->nullable()->unique()->after('id');
        });

        Schema::table('collectes', function (Blueprint $table) {
            $table->string('collecte_uid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('collectes', function (Blueprint $table) {
            $table->dropUnique(['collecte_uid']);
            $table->dropColumn('collecte_uid');
        });

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropUnique(['cycle_uid']);
            $table->dropColumn('cycle_uid');
        });
    }
};
