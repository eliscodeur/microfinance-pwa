<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_charges', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('libelle');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_charges');
    }
};
