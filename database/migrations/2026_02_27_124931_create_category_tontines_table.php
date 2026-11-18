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
            $table->string('libelle')->unique();
            $table->decimal('prix', 15, 2)->default(0);
            $table->integer('nombre_cycles')->default(31);
            $table->text('description')->nullable();
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
