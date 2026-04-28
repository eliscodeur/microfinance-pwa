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
        Schema::create('categories_tontine', function (Blueprint $table) {
            $table->id();
            $table->string('libelle'); // Ex: "Crédit 3 mois"
            $table->integer('nombre_cycles')->nullable(); // Ex: 3
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories_tontine');
    }
};
