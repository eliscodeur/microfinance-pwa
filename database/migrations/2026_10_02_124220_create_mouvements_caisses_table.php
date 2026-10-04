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
            $table->ulid('ulid')->unique(); // Identifiant unique sécurisé
            $table->dateTime('date_mouvement');
            $table->string('type_operation');           // ex: depot_epargne, retrait_epargne, remboursement_credit, etc.
            $table->enum('sens', ['entree', 'sortie']); // Entrée d'argent ou Sortie d'argent
            $table->decimal('montant', 15, 2);
            $table->string('mode_paiement')->default('especes');                     // especes, mobile_money, etc.
            $table->string('reference')->nullable();                                 // Numéro de reçu ou de transaction
            $table->text('libelle')->nullable();                                     // Description claire de l'opération
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // L'auteur / caissier
            $table->nullableMorphs('source');                                        // Optionnel: pour lier directement au modèle d'origine (Recette, Epargne, etc.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_caisses');
    }
};
