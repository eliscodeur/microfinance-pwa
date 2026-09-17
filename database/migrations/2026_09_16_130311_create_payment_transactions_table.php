<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uid')->unique(); // ULID unique pour la transaction

            // Liaisons
            $table->foreignId('credit_payment_id')->constrained('credit_payments')->onDelete('cascade');
            $table->foreignId('credit_id')->constrained('credits')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete(); // L'agent ou l'admin qui a encaissé

                                                                 // Montant et mode de paiement
            $table->integer('montant');                          // Montant versé lors de cette transaction précise
            $table->string('mode_paiement')->default('especes'); // especes, mobile_money, virement, etc.
            $table->string('reference_externe')->nullable();     // Numéro de transaction opérateur (si Mobile Money)

                                                       // Gestion des tiers payeurs (si quelqu'un d'autre que le client paie)
            $table->string('payer_name')->nullable();  // Nom et prénom du tiers
            $table->string('payer_phone')->nullable(); // Téléphone du tiers
            $table->string('payer_relation')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
