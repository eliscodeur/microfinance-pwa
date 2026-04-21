<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_batch_collectes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('sync_batch_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('collecte_uid')->nullable()->index();
            $table->string('cycle_ref')->nullable()->index();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->integer('pointage')->default(1);
            $table->decimal('montant', 15, 2);
            $table->timestamp('date_saisie')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batch_collectes');
    }
};
