<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepositHardeningMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'deposit_migration_test',
            'database.connections.deposit_migration_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('deposit_migration_test');

        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('carnet_id');
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->decimal('montant', 15, 2);
            $table->dateTime('date_depot');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            $table->string('collecte_uid')->unique();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('agent_id');
            $table->integer('pointage');
            $table->decimal('montant', 15, 2);
            $table->dateTime('date_saisie');
            $table->timestamps();
        });
        Schema::create('mouvements_caisses', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->string('mode_paiement')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('cash_session_id')->nullable();
        });

        DB::table('depots')->insert([
            'id' => 1,
            'client_id' => 1,
            'carnet_id' => 1,
            'user_id' => 1,
            'montant' => 500,
            'date_depot' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mouvements_caisses')->insert([
            'reference' => 'DEP-20261109-1',
            'mode_paiement' => 'mobile_money',
            'source_type' => \App\Models\Depot::class,
            'source_id' => 1,
            'cash_session_id' => 7,
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('deposit_migration_test');
        parent::tearDown();
    }

    public function test_migration_adds_deposit_and_collection_audit_fields_and_backfills_legacy_deposits(): void
    {
        $migration = require base_path('database/migrations/2026_11_09_030000_harden_carnet_deposits.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('depots', [
            'cash_session_id',
            'reference_operation',
            'idempotency_key',
            'mode_paiement',
            'reference_paiement',
            'payeur_type',
            'payeur_nom',
            'payeur_telephone',
            'payeur_lien',
            'solde_carnet_avant',
            'solde_carnet_apres',
        ]));
        $this->assertTrue(Schema::hasColumns('collectes', [
            'idempotency_key',
            'reference_operation',
            'cash_session_id',
            'payeur_type',
            'payeur_nom',
            'payeur_telephone',
            'payeur_lien',
        ]));
        $this->assertSame('DEP-20261109-1', DB::table('depots')->where('id', 1)->value('reference_operation'));
        $this->assertSame('mobile_money', DB::table('depots')->where('id', 1)->value('mode_paiement'));
        $this->assertSame('client', DB::table('depots')->where('id', 1)->value('payeur_type'));
        $this->assertSame(7, DB::table('depots')->where('id', 1)->value('cash_session_id'));
    }
}
