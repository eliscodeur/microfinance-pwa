<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalReceiptMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'withdrawal_migration_test',
            'database.connections.withdrawal_migration_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('withdrawal_migration_test');

        Schema::connection('withdrawal_migration_test')->create('cash_sessions', function (Blueprint $table) {
            $table->id();
        });
        Schema::connection('withdrawal_migration_test')->create('retraits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
        });
        DB::table('retraits')->insert([
            ['id' => 1, 'admin_id' => 10],
            ['id' => 2, 'admin_id' => 20],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('withdrawal_migration_test');
        parent::tearDown();
    }

    public function test_migration_adds_withdrawal_receipt_fields_and_backfills_unique_references(): void
    {
        $migration = require base_path('database/migrations/2026_11_09_010000_harden_carnet_withdrawals_and_receipts.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('retraits', [
            'cash_session_id',
            'reference_operation',
            'idempotency_key',
            'mode_paiement',
            'reference_paiement',
            'motif',
            'solde_carnet_avant',
            'solde_carnet_apres',
        ]));
        $this->assertSame('RET-HIST-000001', DB::table('retraits')->where('id', 1)->value('reference_operation'));
        $this->assertSame('RET-HIST-000002', DB::table('retraits')->where('id', 2)->value('reference_operation'));
    }
}
