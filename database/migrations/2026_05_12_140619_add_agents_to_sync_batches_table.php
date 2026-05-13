<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sync_batches', function (Blueprint $table) {
            // On utilise jsonb si vous êtes sur PostgreSQL, sinon json pour MySQL
            $table->json('agents')->nullable()->after('sync_uuid');
        });
    }

    public function down()
    {
        Schema::table('sync_batches', function (Blueprint $table) {
            $table->dropColumn('agents');
        });
    }
};
