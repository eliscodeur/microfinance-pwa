<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('sync_uuid')->unique();
            $table->unsignedInteger('nb_collectes')->default(0);
            $table->unsignedInteger('nb_cycles')->default(0);
            $table->decimal('total_montant', 15, 2)->default(0);
            $table->string('status')->default('success');
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_histories');
    }
};