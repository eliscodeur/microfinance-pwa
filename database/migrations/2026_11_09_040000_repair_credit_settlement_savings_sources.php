<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('credit_settlement_sources') || ! Schema::hasTable('cycles')) {
            throw new RuntimeException(
                'Impossible de réparer les sources de règlement : les tables credit_settlement_sources et cycles doivent exister.'
            );
        }

        if (! Schema::hasColumn('credit_settlement_sources', 'carnet_id')) {
            Schema::table('credit_settlement_sources', function (Blueprint $table) {
                $table->unsignedBigInteger('carnet_id')->nullable()->after('cycle_id');
            });
        }

        if (Schema::hasColumn('cycles', 'carnet_id')) {
            DB::table('credit_settlement_sources')
                ->whereNotNull('cycle_id')
                ->whereNull('carnet_id')
                ->update([
                    'carnet_id' => DB::raw(
                        '(SELECT cycles.carnet_id FROM cycles WHERE cycles.id = credit_settlement_sources.cycle_id)'
                    ),
                ]);
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $cycleColumn = DB::table('information_schema.COLUMNS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'credit_settlement_sources')
                ->where('COLUMN_NAME', 'cycle_id')
                ->first(['IS_NULLABLE', 'COLUMN_TYPE']);

            if ($cycleColumn && $cycleColumn->IS_NULLABLE !== 'YES') {
                DB::statement(
                    'ALTER TABLE `credit_settlement_sources` MODIFY `cycle_id` '
                    . $cycleColumn->COLUMN_TYPE . ' NULL'
                );
            }
        }

        $this->ensureIndex(
            'css_settlement_carnet_unique',
            'UNIQUE INDEX `css_settlement_carnet_unique` (`credit_settlement_id`, `carnet_id`)'
        );
        $this->ensureIndex(
            'css_carnet_index',
            'INDEX `css_carnet_index` (`carnet_id`)'
        );

        if (DB::connection()->getDriverName() === 'mysql') {
            $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'credit_settlement_sources')
                ->where('COLUMN_NAME', 'carnet_id')
                ->where('REFERENCED_TABLE_NAME', 'carnets')
                ->where('REFERENCED_COLUMN_NAME', 'id')
                ->exists();

            if (! $foreignKeyExists) {
                DB::statement(
                    'ALTER TABLE `credit_settlement_sources` '
                    . 'ADD CONSTRAINT `css_carnet_fk` FOREIGN KEY (`carnet_id`) '
                    . 'REFERENCES `carnets` (`id`) ON DELETE RESTRICT'
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('credit_settlement_sources')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'credit_settlement_sources')
                ->where('COLUMN_NAME', 'carnet_id')
                ->where('REFERENCED_TABLE_NAME', 'carnets')
                ->value('CONSTRAINT_NAME');

            if ($foreignKey) {
                DB::statement("ALTER TABLE `credit_settlement_sources` DROP FOREIGN KEY `{$foreignKey}`");
            }

            foreach (['css_settlement_carnet_unique', 'css_carnet_index'] as $index) {
                $exists = DB::table('information_schema.STATISTICS')
                    ->whereRaw('TABLE_SCHEMA = DATABASE()')
                    ->where('TABLE_NAME', 'credit_settlement_sources')
                    ->where('INDEX_NAME', $index)
                    ->exists();

                if ($exists) {
                    DB::statement("ALTER TABLE `credit_settlement_sources` DROP INDEX `{$index}`");
                }
            }
        } else {
            DB::statement('DROP INDEX IF EXISTS css_settlement_carnet_unique');
            DB::statement('DROP INDEX IF EXISTS css_carnet_index');
        }

        if (Schema::hasColumn('credit_settlement_sources', 'carnet_id')) {
            Schema::table('credit_settlement_sources', function (Blueprint $table) {
                $table->dropColumn('carnet_id');
            });
        }
    }

    private function ensureIndex(string $name, string $definition): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $exists = DB::table('information_schema.STATISTICS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'credit_settlement_sources')
                ->where('INDEX_NAME', $name)
                ->exists();

            if (! $exists) {
                DB::statement("ALTER TABLE `credit_settlement_sources` ADD {$definition}");
            }

            return;
        }

        $indexes = DB::select("PRAGMA index_list('credit_settlement_sources')");
        if (! collect($indexes)->contains(fn ($index) => $index->name === $name)) {
            if ($name === 'css_settlement_carnet_unique') {
                DB::statement(
                    'CREATE UNIQUE INDEX css_settlement_carnet_unique '
                    . 'ON credit_settlement_sources (credit_settlement_id, carnet_id)'
                );
            } else {
                DB::statement(
                    'CREATE INDEX css_carnet_index ON credit_settlement_sources (carnet_id)'
                );
            }
        }
    }
};
