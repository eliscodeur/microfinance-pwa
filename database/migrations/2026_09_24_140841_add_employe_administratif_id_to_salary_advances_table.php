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
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable()->change(); // Rendre agent_id optionnel
            $table->unsignedBigInteger('employe_administratif_id')->nullable()->after('agent_id');

            $table->foreign('employe_administratif_id')
                ->references('id')
                ->on('employe_administratifs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->dropForeign(['employe_administratif_id']);
            $table->dropColumn('employe_administratif_id');
        });
    }
};
