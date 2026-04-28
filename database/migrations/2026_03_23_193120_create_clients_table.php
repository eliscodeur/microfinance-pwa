<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('clients'); // Sécurité radicale

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('genre')->nullable();
            $table->string('statut_matrimonial')->nullable();
            $table->string('nationalite')->nullable();
            $table->string('profession')->nullable();
            $table->string('telephone')->unique();
            $table->string('adresse')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('photo')->nullable();
            $table->string('reference_nom')->nullable();
            $table->string('reference_telephone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
};