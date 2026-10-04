<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recettes', function (Blueprint $table) {
            $table->id();                   // ID classique auto-incrémenté (pour les relations internes / clés étrangères)
            $table->ulid('ulid')->unique(); // ULID unique et sécurisé (pour les routes, URLs et API)

            $table->string('reference')->unique();
            $table->string('type_recette'); // 'vente_carnet', 'frais_dossier', 'remboursement', etc.
            $table->decimal('montant', 12, 2);

            // Relations
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('credit_id')->nullable()->constrained('credits')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');

            $table->string('mode_paiement')->default('especes'); // especes, mobile_money, etc.
            $table->dateTime('date_recette');
            $table->text('commentaire')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recettes');
    }
};
