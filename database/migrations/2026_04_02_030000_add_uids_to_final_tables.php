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

        // Le bloc 'collectes' a été retiré d'ici car 'collecte_uid' est déjà créé à la source.
    }

    public function down(): void
    {
        // Le bloc de suppression 'collectes' a été retiré d'ici aussi.

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropUnique(['cycle_uid']);
            $table->dropColumn('cycle_uid');
        });
    }
};