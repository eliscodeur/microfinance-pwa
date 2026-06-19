<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_guarantors', function (Blueprint $table) {
            $table->id();
            // Clé étrangère reliée à la table credits
            $table->foreignId('credit_id')->constrained('credits')->onDelete('cascade');
            
            // Informations civiles de l'avaliste / caution solidaire
            $table->string('nom_prenom');
            $table->string('telephone');
            $table->string('profession')->nullable();
            $table->string('adresse')->nullable(); // Quartier / Ville

            // Documents KYC (Chemins d'accès des fichiers sauvegardés)
            $table->string('piece_identite'); // CNIB, Passeport, etc.
            $table->string('justificatif_revenu')->nullable(); // Plan, attestation, ou facture

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_guarantors');
    }
};