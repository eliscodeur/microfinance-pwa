<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mouvement_stock_carnets', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->unsignedBigInteger('categories_tontine_id')->nullable();
            $table->foreign('categories_tontine_id')
                ->references('id')
                ->on('categories_tontine')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->enum('type', ['entree', 'sortie']);

            $table->integer('quantite');

            $table->decimal('prix_unitaire_achat', 10, 2)->nullable();
            $table->decimal('prix_unitaire_vente', 10, 2)->nullable();

            $table->string('motif')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvement_stock_carnets');
    }
};
