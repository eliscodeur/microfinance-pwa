<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->char('advance_uid', 26)->unique();
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');

            $table->integer('montant_total');
            $table->integer('montant_mensuel');
            $table->integer('nombre_tranches');
            $table->integer('tranches_payees')->default(0);
            $table->integer('montant_restant');
            $table->date('date_demande');
            $table->enum('statut', ['en_attente', 'en_cours', 'soldee', 'rejetee'])->default('en_attente');
            $table->text('motif')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_advances');
    }
};
