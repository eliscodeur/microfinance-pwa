<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->string('sync_uuid')->unique();
            $table->enum('status', ['pending_review', 'approved', 'rejected', 'cancelled'])->default('pending_review');
            $table->unsignedInteger('nb_collectes')->default(0);
            $table->unsignedInteger('nb_cycles')->default(0);
            $table->decimal('total_montant', 15, 2)->default(0);
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batches');
    }
};
