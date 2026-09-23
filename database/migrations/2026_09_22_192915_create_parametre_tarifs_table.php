<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametre_tarifs', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('libelle');
            $table->string('code')->unique();
            $table->decimal('prix', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametre_tarifs');
    }
};
