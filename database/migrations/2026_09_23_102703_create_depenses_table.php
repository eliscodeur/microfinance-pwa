<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('categories_charge_id')->constrained('categories_charges')->onDelete('cascade');
            $table->decimal('montant', 15, 2);
            $table->date('date_depense');
            $table->string('beneficiaire')->nullable();
            $table->string('mode_paiement')->default('Espèces');
            $table->string('reference_piece')->nullable();
            $table->text('motif');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
