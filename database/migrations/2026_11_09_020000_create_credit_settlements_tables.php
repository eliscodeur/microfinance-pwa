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
            $this->ensureInnoDbTables([
                'credits',
                'credit_payments',
                'cycles',
                'carnets',
                'depots',
                'retraits',
                'collectes',
                'payment_transactions',
                'clients',
                'users',
                'credit_settlements',
                'credit_settlement_sources',
                'credit_settlement_allocations',
            ]);
        }

        if (! Schema::hasTable('credit_settlements')) {
            Schema::create('credit_settlements', function (Blueprint $table) {
                if (DB::connection()->getDriverName() === 'mysql') {
                    $table->engine = 'InnoDB';
                }

                $table->id();
                $table->string('ulid', 26)->unique();
                $table->string('reference')->unique();
                $table->string('idempotency_key')->unique();
                $table->unsignedBigInteger('credit_id');
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->decimal('montant', 15, 2);
                $table->timestamp('date_operation');
                $table->text('note')->nullable();
                $table->timestamps();

                $table->foreign('credit_id', 'cs_credit_fk')->references('id')->on('credits')->restrictOnDelete();
                $table->foreign('client_id', 'cs_client_fk')->references('id')->on('clients')->restrictOnDelete();
                $table->foreign('admin_id', 'cs_admin_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['credit_id', 'date_operation']);
            });
        }

        if (! Schema::hasTable('credit_settlement_sources')) {
            Schema::create('credit_settlement_sources', function (Blueprint $table) {
                if (DB::connection()->getDriverName() === 'mysql') {
                    $table->engine = 'InnoDB';
                }

                $table->id();
                $table->unsignedBigInteger('credit_settlement_id');
                $table->unsignedBigInteger('cycle_id')->nullable();
                $table->unsignedBigInteger('carnet_id')->nullable();
                $table->decimal('montant_affecte', 15, 2);
                $table->decimal('commission', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('credit_settlement_id', 'css_settlement_fk')
                    ->references('id')->on('credit_settlements')->restrictOnDelete();
                $table->foreign('cycle_id', 'css_cycle_fk')->references('id')->on('cycles')->restrictOnDelete();
                $table->foreign('carnet_id', 'css_carnet_fk')->references('id')->on('carnets')->restrictOnDelete();
                $table->unique(['credit_settlement_id', 'cycle_id']);
                $table->unique(['credit_settlement_id', 'carnet_id'], 'css_settlement_carnet_unique');
                $table->index('cycle_id');
                $table->index('carnet_id', 'css_carnet_index');
            });
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->ensureSavingsSourceColumns();
        }

        if (! Schema::hasTable('credit_settlement_allocations')) {
            Schema::create('credit_settlement_allocations', function (Blueprint $table) {
                if (DB::connection()->getDriverName() === 'mysql') {
                    $table->engine = 'InnoDB';
                }

                $table->id();
                $table->unsignedBigInteger('credit_settlement_id');
                $table->unsignedBigInteger('credit_settlement_source_id');
                $table->unsignedBigInteger('credit_payment_id')->nullable();
                $table->unsignedSmallInteger('echeance')->nullable();
                $table->date('date_echeance')->nullable();
                $table->decimal('montant', 15, 2);
                $table->timestamps();

                $table->foreign('credit_settlement_id', 'csa_settlement_fk')
                    ->references('id')->on('credit_settlements')->restrictOnDelete();
                $table->foreign('credit_settlement_source_id', 'csa_source_fk')
                    ->references('id')->on('credit_settlement_sources')->restrictOnDelete();
                $table->foreign('credit_payment_id', 'csa_payment_fk')
                    ->references('id')->on('credit_payments')->nullOnDelete();
                $table->index(['credit_settlement_id', 'credit_payment_id'], 'csa_settlement_payment_idx');
            });
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->ensureSettlementAllocationColumns();
            $this->ensureForeignKeys();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_settlement_allocations');
        Schema::dropIfExists('credit_settlement_sources');
        Schema::dropIfExists('credit_settlements');
    }

    private function ensureInnoDbTables(array $tables): void
    {
        foreach ($tables as $table) {
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

    private function ensureForeignKeys(): void
    {
        $constraints = [
            ['credit_settlements', 'credit_id', 'credits', 'cs_credit_fk', 'RESTRICT'],
            ['credit_settlements', 'client_id', 'clients', 'cs_client_fk', 'RESTRICT'],
            ['credit_settlements', 'admin_id', 'users', 'cs_admin_fk', 'SET NULL'],
            ['credit_settlement_sources', 'credit_settlement_id', 'credit_settlements', 'css_settlement_fk', 'RESTRICT'],
            ['credit_settlement_sources', 'cycle_id', 'cycles', 'css_cycle_fk', 'RESTRICT'],
            ['credit_settlement_sources', 'carnet_id', 'carnets', 'css_carnet_fk', 'RESTRICT'],
            ['credit_settlement_allocations', 'credit_settlement_id', 'credit_settlements', 'csa_settlement_fk', 'RESTRICT'],
            ['credit_settlement_allocations', 'credit_settlement_source_id', 'credit_settlement_sources', 'csa_source_fk', 'RESTRICT'],
            ['credit_settlement_allocations', 'credit_payment_id', 'credit_payments', 'csa_payment_fk', 'SET NULL'],
        ];

        foreach ($constraints as [$table, $column, $referencedTable, $name, $onDelete]) {
            $exists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->where('REFERENCED_TABLE_NAME', $referencedTable)
                ->where('REFERENCED_COLUMN_NAME', 'id')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` "
                . "FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}` (`id`) ON DELETE {$onDelete}"
            );
        }
    }

    private function ensureSavingsSourceColumns(): void
    {
        $columns = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'credit_settlement_sources')
            ->pluck('COLUMN_NAME')
            ->all();

        if (! in_array('carnet_id', $columns, true)) {
            DB::statement('ALTER TABLE `credit_settlement_sources` ADD `carnet_id` BIGINT UNSIGNED NULL AFTER `cycle_id`');
        }

        if (in_array('cycle_id', $columns, true)) {
            DB::statement('ALTER TABLE `credit_settlement_sources` MODIFY `cycle_id` BIGINT UNSIGNED NULL');
        }

        $indexExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'credit_settlement_sources')
            ->where('INDEX_NAME', 'css_settlement_carnet_unique')
            ->exists();
        if (! $indexExists) {
            DB::statement(
                'ALTER TABLE `credit_settlement_sources` '
                . 'ADD UNIQUE INDEX `css_settlement_carnet_unique` (`credit_settlement_id`, `carnet_id`)'
            );
        }

        $carnetIndexExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'credit_settlement_sources')
            ->where('INDEX_NAME', 'css_carnet_index')
            ->exists();
        if (! $carnetIndexExists) {
            DB::statement('ALTER TABLE `credit_settlement_sources` ADD INDEX `css_carnet_index` (`carnet_id`)');
        }
    }

    private function ensureSettlementAllocationColumns(): void
    {
        $columns = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'credit_settlement_allocations')
            ->pluck('COLUMN_NAME')
            ->all();

        if (! in_array('echeance', $columns, true)) {
            DB::statement('ALTER TABLE `credit_settlement_allocations` ADD `echeance` SMALLINT UNSIGNED NULL');
        }

        if (! in_array('date_echeance', $columns, true)) {
            DB::statement('ALTER TABLE `credit_settlement_allocations` ADD `date_echeance` DATE NULL');
        }

        $indexExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'credit_settlement_allocations')
            ->where('INDEX_NAME', 'csa_settlement_payment_idx')
            ->exists();
        if (! $indexExists) {
            DB::statement(
                'ALTER TABLE `credit_settlement_allocations` '
                . 'ADD INDEX `csa_settlement_payment_idx` (`credit_settlement_id`, `credit_payment_id`)'
            );
        }
    }
};
