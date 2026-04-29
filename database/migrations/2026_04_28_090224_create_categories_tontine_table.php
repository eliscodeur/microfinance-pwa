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
    public function up(): void
    {
        Schema::create('categories_tontine', function (Blueprint $table) {
            $table->id();
            $table->string('libelle')->unique();
            $table->decimal('prix', 15, 2)->default(0); // On l'intègre directement ici
            $table->integer('nombre_cycles')->default(31); // On l'intègre directement ici
            $table->text('description')->nullable();    // On l'intègre directement ici
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
