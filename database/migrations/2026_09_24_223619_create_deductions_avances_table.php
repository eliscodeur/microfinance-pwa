<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deductions_avances', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();

            // Relation avec l'avance sur salaire
            $table->unsignedBigInteger('salary_advance_id');
            $table->foreign('salary_advance_id')
                ->references('id')
                ->on('salary_advances')
                ->onDelete('cascade');

            // Soit c'est un agent, soit c'est un employé (les deux sont nullable pour éviter l'erreur NOT NULL)
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('employe_administratif_id')->nullable();

            // Clés étrangères optionnelles (à adapter selon les noms de tes tables si besoin)
            $table->foreign('employe_administratif_id')
                ->references('id')
                ->on('employes_administratifs')
                ->onDelete('cascade');

            // Détails de la déduction
            $table->integer('nombre_tranches');
            $table->decimal('montant', 12, 2);
            $table->unsignedTinyInteger('mois');
            $table->year('annee');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deductions_avances');
    }
};
