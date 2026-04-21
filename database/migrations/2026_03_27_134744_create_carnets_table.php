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
        Schema::create('carnets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('numero')->unique(); // Ex: CAR-001
            // $table->string('libelle'); // Ex: Cotisation Scolaire
            // $table->integer('montant_fixe'); // Le montant par mise (ex: 500)
            $table->enum('statut', ['actif', 'termine', 'en_attente'])->default('actif');
            $table->date('date_debut')->nullable();
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
        Schema::dropIfExists('carnets');
    }
};
