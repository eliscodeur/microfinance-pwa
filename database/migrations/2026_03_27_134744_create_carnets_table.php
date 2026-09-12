<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('carnets');

        Schema::create('carnets', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('type'); // 'tontine', 'epargne', etc.
            $table->foreignId('category_tontine_id')->nullable()->constrained('category_tontines')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('carnets')->nullOnDelete();
            $table->string('numero')->unique();
            $table->string('statut')->default('actif'); // 'actif', 'termine', 'suspendu'
            $table->date('date_debut')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carnets');
    }
};