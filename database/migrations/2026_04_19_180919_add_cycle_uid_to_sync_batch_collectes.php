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
        Schema::table('sync_batch_collectes', function (Blueprint $table) {
            // On ajoute le champ cycle_uid juste après collecte_uid
            if (!Schema::hasColumn('sync_batch_collectes', 'cycle_uid')) {
                $table->string('cycle_uid')->nullable()->after('collecte_uid');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sync_batch_collectes', function (Blueprint $table) {
            if (Schema::hasColumn('sync_batch_collectes', 'cycle_uid')) {
                $table->dropColumn('cycle_uid');
            }
        });
    }
};
