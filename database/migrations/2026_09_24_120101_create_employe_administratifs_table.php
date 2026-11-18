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
        Schema::create('employe_administratifs', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            // Identité
            $table->string('nom');
            $table->string('prenoms');
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();

            // Coordonnées
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->text('adresse')->nullable(); // Ex: Vakpossito, Lomé

            // Professionnel & Fonction
            $table->foreignId('fonction_id')->constrained('fonctions')->onDelete('cascade');
            $table->decimal('salaire_base', 12, 2)->nullable(); // Nullable pour utiliser celui de la fonction par défaut ou un montant négocié
            $table->enum('statut_contrat', ['essai', 'confirme', 'cdd', 'cdi'])->default('essai');
            $table->date('date_embauche');

                                                          // Informations complémentaires / Urgence
            $table->string('piece_identite')->nullable(); // Numéro CNI ou Passeport
            $table->string('contact_urgence_nom')->nullable();
            $table->string('contact_urgence_telephone')->nullable();

            $table->boolean('actif')->default(true);
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
        Schema::dropIfExists('employe_administratifs');
    }
};
