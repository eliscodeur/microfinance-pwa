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
        Schema::create('salaires_employe', function (Blueprint $table) {
            $table->id();

            // Utilisation d'un ULID natif ou stocké en string
            $table->ulid('ulid')->unique();

            // Relation avec l'employé
            $table->unsignedBigInteger('employe_id');
            // $table->foreign('employe_id')->references('id')->on('employes')->onDelete('cascade');

            // Lien optionnel avec la dépense attestant le décaissement/validation
            $table->unsignedBigInteger('depense_id')->nullable();
            // $table->foreign('depense_id')->references('id')->on('depenses')->onDelete('set null');

                                         // Période de paie
            $table->tinyInteger('mois'); // 1 à 12
            $table->year('annee');       // Ex: 2026

            // Éléments de gains
            $table->decimal('salaire_base', 12, 2)->default(0);
            $table->decimal('primes_totales', 12, 2)->default(0);
            $table->decimal('indemnites_totales', 12, 2)->default(0);
            $table->decimal('salaire_brut', 12, 2)->default(0);

            // Retenues et charges
            $table->decimal('avances_prets', 12, 2)->default(0);
            $table->decimal('autres_retenues', 12, 2)->default(0);
            $table->decimal('total_retenues', 12, 2)->default(0);

            // Résultat final
            $table->decimal('salaire_net', 12, 2)->default(0);

            // Statut du bulletin (brouillon, valide, paye...)
            $table->string('statut')->default('brouillon');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaires_employe');
    }
};
