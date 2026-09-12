<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_grids', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->decimal('seuil_min', 12, 2)->nullable();
            $table->decimal('seuil_max', 12, 2);

            $table->decimal('salaire_base', 12, 2);
            $table->decimal('commission_travail', 12, 2)->nullable()->default(0);
            $table->decimal('taux_carnet', 5, 2)->default(25.00);

            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->boolean('est_actif')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grids');
    }
};
