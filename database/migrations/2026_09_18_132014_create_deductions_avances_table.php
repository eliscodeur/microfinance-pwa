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
            $table->ulid('ulid')->unique();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('salary_advance_id');
            $table->decimal('montant', 10, 2);
            $table->integer('nombre_tranches')->default(1);
            $table->integer('mois');
            $table->integer('annee');
            $table->timestamps();

            // Contrainte d'unicité pour updateOrCreate
            $table->unique(
                ['agent_id', 'salary_advance_id', 'mois', 'annee'],
                'unique_deduction_avance_mois'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deductions_avances');
    }
};
