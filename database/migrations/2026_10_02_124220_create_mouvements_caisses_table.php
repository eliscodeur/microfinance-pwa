<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_caisses', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->dateTime('date_mouvement');
            $table->string('type_operation');
            $table->enum('sens', ['entree', 'sortie']);
            $table->decimal('montant', 15, 2);
            $table->string('mode_paiement')->default('especes'); // especes, mobile_money, etc.
            $table->string('reference')->nullable();
            $table->text('libelle')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->nullableMorphs('source');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_caisses');
    }
};
