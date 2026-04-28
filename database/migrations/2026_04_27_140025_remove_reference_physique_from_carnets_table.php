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
        Schema::table('carnets', function (Blueprint $table) {
            $table->dropColumn('reference_physique');
        });
    }

    public function down()
    {
        Schema::table('carnets', function (Blueprint $table) {
            $table->string('reference_physique')->nullable();
        });
    }
};
