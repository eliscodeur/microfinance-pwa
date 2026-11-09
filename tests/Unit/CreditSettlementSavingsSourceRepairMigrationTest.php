<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreditSettlementSavingsSourceRepairMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'settlement_repair_test',
            'database.connections.settlement_repair_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('settlement_repair_test');

        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carnet_id');
        });
        Schema::create('credit_settlement_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_settlement_id');
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->decimal('montant_affecte', 15, 2);
            $table->decimal('commission', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['credit_settlement_id', 'cycle_id']);
        });

        DB::table('cycles')->insert(['id' => 10, 'carnet_id' => 42]);
        DB::table('credit_settlement_sources')->insert([
            'id' => 5,
            'credit_settlement_id' => 7,
            'cycle_id' => 10,
            'montant_affecte' => 1200,
            'commission' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('settlement_repair_test');
        parent::tearDown();
    }

    public function test_migration_adds_and_backfills_savings_source_carnet_column(): void
    {
        $migration = require base_path('database/migrations/2026_11_09_040000_repair_credit_settlement_savings_sources.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('credit_settlement_sources', 'carnet_id'));
        $this->assertSame(42, DB::table('credit_settlement_sources')->where('id', 5)->value('carnet_id'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('credit_settlement_sources', 'carnet_id'));
    }
}
