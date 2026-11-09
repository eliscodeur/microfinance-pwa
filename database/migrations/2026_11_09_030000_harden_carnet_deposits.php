<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            foreach (['users', 'admins', 'clients', 'agents', 'paiements', 'carnets', 'cycles', 'depots', 'collectes', 'bonuses', 'cash_sessions', 'mouvements_caisses', 'audits'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $engine = DB::table('information_schema.TABLES')
                    ->whereRaw('TABLE_SCHEMA = DATABASE()')
                    ->where('TABLE_NAME', $table)
                    ->value('ENGINE');

                if ($engine && strtoupper($engine) !== 'INNODB') {
                    DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
                }
            }
        }

        if (Schema::hasTable('depots')) {
            Schema::table('depots', function (Blueprint $table) {
                if (! Schema::hasColumn('depots', 'cash_session_id')) {
                    $table->unsignedBigInteger('cash_session_id')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('depots', 'reference_operation')) {
                    $table->string('reference_operation')->nullable()->unique();
                }
                if (! Schema::hasColumn('depots', 'idempotency_key')) {
                    $table->string('idempotency_key')->nullable()->unique();
                }
                if (! Schema::hasColumn('depots', 'mode_paiement')) {
                    $table->string('mode_paiement')->nullable();
                }
                if (! Schema::hasColumn('depots', 'reference_paiement')) {
                    $table->string('reference_paiement')->nullable();
                }
                if (! Schema::hasColumn('depots', 'payeur_type')) {
                    $table->string('payeur_type')->nullable();
                }
                if (! Schema::hasColumn('depots', 'payeur_nom')) {
                    $table->string('payeur_nom')->nullable();
                }
                if (! Schema::hasColumn('depots', 'payeur_telephone')) {
                    $table->string('payeur_telephone', 30)->nullable();
                }
                if (! Schema::hasColumn('depots', 'payeur_lien')) {
                    $table->string('payeur_lien', 80)->nullable();
                }
                if (! Schema::hasColumn('depots', 'solde_carnet_avant')) {
                    $table->decimal('solde_carnet_avant', 15, 2)->nullable();
                }
                if (! Schema::hasColumn('depots', 'solde_carnet_apres')) {
                    $table->decimal('solde_carnet_apres', 15, 2)->nullable();
                }
            });

            DB::table('depots')
                ->whereNull('reference_operation')
                ->orderBy('id')
                ->chunkById(500, function ($depots) {
                    foreach ($depots as $depot) {
                        $movement = null;
                        if (Schema::hasTable('mouvements_caisses')) {
                            $movement = DB::table('mouvements_caisses')
                                ->where('source_type', \App\Models\Depot::class)
                                ->where('source_id', $depot->id)
                                ->first();
                        }

                        DB::table('depots')
                            ->where('id', $depot->id)
                            ->update([
                                'reference_operation' => $movement->reference
                                    ?? 'DEP-HIST-' . str_pad((string) $depot->id, 8, '0', STR_PAD_LEFT),
                                'cash_session_id' => $depot->cash_session_id ?? $movement->cash_session_id ?? null,
                                'mode_paiement' => $depot->mode_paiement ?: ($movement->mode_paiement ?? 'especes'),
                                'payeur_type' => $depot->payeur_type ?: 'client',
                            ]);
                    }
                });
        }

        if (Schema::hasTable('collectes')) {
            Schema::table('collectes', function (Blueprint $table) {
                if (! Schema::hasColumn('collectes', 'idempotency_key')) {
                    $table->string('idempotency_key')->nullable()->unique();
                }
                if (! Schema::hasColumn('collectes', 'reference_operation')) {
                    $table->string('reference_operation')->nullable()->unique();
                }
                if (! Schema::hasColumn('collectes', 'cash_session_id')) {
                    $table->unsignedBigInteger('cash_session_id')->nullable();
                }
                if (! Schema::hasColumn('collectes', 'payeur_type')) {
                    $table->string('payeur_type')->nullable();
                }
                if (! Schema::hasColumn('collectes', 'payeur_nom')) {
                    $table->string('payeur_nom')->nullable();
                }
                if (! Schema::hasColumn('collectes', 'payeur_telephone')) {
                    $table->string('payeur_telephone', 30)->nullable();
                }
                if (! Schema::hasColumn('collectes', 'payeur_lien')) {
                    $table->string('payeur_lien', 80)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('collectes')) {
            Schema::table('collectes', function (Blueprint $table) {
                foreach (['idempotency_key', 'reference_operation'] as $column) {
                    if (Schema::hasColumn('collectes', $column)) {
                        $table->dropUnique([$column]);
                        $table->dropColumn($column);
                    }
                }
                $columns = array_values(array_filter(
                    ['cash_session_id', 'payeur_type', 'payeur_nom', 'payeur_telephone', 'payeur_lien'],
                    fn ($column) => Schema::hasColumn('collectes', $column)
                ));
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('depots')) {
            Schema::table('depots', function (Blueprint $table) {
                foreach (['reference_operation', 'idempotency_key'] as $column) {
                    if (Schema::hasColumn('depots', $column)) {
                        $table->dropUnique([$column]);
                        $table->dropColumn($column);
                    }
                }
                $columns = array_values(array_filter([
                    'cash_session_id',
                    'mode_paiement',
                    'reference_paiement',
                    'payeur_type',
                    'payeur_nom',
                    'payeur_telephone',
                    'payeur_lien',
                    'solde_carnet_avant',
                    'solde_carnet_apres',
                ], fn ($column) => Schema::hasColumn('depots', $column)));
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
