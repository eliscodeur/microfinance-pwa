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
        Schema::create('credit_objects', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Ex: Achat de stock, Prêt scolaire
            $table->string('secteur_activite')->nullable(); // Ex: Commerce, Éducation
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('credit_objects');
    }
};
