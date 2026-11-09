<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreditSettlementMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'credit_settlement_migration_test',
            'database.connections.credit_settlement_migration_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('credit_settlement_migration_test');

        foreach (['credits', 'clients', 'users', 'cycles', 'carnets', 'credit_payments'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('credit_settlement_migration_test');
        parent::tearDown();
    }

    public function test_migration_creates_and_drops_the_atomic_settlement_ledger_tables(): void
    {
        $migration = require base_path('database/migrations/2026_11_09_020000_create_credit_settlements_tables.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('credit_settlements', [
            'idempotency_key',
            'credit_id',
            'client_id',
            'admin_id',
            'montant',
            'date_operation',
        ]));
        $this->assertTrue(Schema::hasColumns('credit_settlement_sources', [
            'credit_settlement_id',
            'cycle_id',
            'carnet_id',
            'montant_affecte',
            'commission',
        ]));
        $this->assertTrue(Schema::hasColumn('credit_settlement_sources', 'cycle_id'));
        $this->assertTrue(Schema::hasColumns('credit_settlement_allocations', [
            'credit_settlement_id',
            'credit_settlement_source_id',
            'credit_payment_id',
            'echeance',
            'date_echeance',
            'montant',
        ]));

        $migration->down();

        $this->assertFalse(Schema::hasTable('credit_settlements'));
        $this->assertFalse(Schema::hasTable('credit_settlement_sources'));
        $this->assertFalse(Schema::hasTable('credit_settlement_allocations'));
    }
}
