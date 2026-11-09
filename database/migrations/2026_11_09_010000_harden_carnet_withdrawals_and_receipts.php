<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retraits', function (Blueprint $table) {
            $table->foreignId('cash_session_id')
                ->nullable()
                ->after('admin_id')
                ->constrained('cash_sessions')
                ->nullOnDelete();
            $table->string('reference_operation')->nullable()->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('mode_paiement')->nullable();
            $table->string('reference_paiement')->nullable();
            $table->string('motif')->nullable();
            $table->decimal('solde_carnet_avant', 15, 2)->nullable();
            $table->decimal('solde_carnet_apres', 15, 2)->nullable();
        });

        DB::table('retraits')
            ->whereNull('reference_operation')
            ->orderBy('id')
            ->chunkById(500, function ($retraits) {
                foreach ($retraits as $retrait) {
                    DB::table('retraits')
                        ->where('id', $retrait->id)
                        ->update([
                            'reference_operation' => 'RET-HIST-' . str_pad((string) $retrait->id, 6, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('retraits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_session_id');
            $table->dropUnique(['reference_operation']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'reference_operation',
                'idempotency_key',
                'mode_paiement',
                'reference_paiement',
                'motif',
                'solde_carnet_avant',
                'solde_carnet_apres',
            ]);
        });
    }
};
