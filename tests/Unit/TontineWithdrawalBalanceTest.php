<?php

namespace Tests\Unit;

use App\Models\Cycle;
use App\Models\Carnet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TontineWithdrawalBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'tontine_balance_test',
            'database.connections.tontine_balance_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('tontine_balance_test');

        Schema::connection('tontine_balance_test')->create('cycles', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant_journalier', 15, 2)->default(0);
        });
        Schema::connection('tontine_balance_test')->create('carnets', function (Blueprint $table) {
            $table->id();
        });
        Schema::connection('tontine_balance_test')->create('depots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carnet_id');
            $table->decimal('montant', 15, 2);
        });
        Schema::connection('tontine_balance_test')->create('collectes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->decimal('montant', 15, 2);
        });
        Schema::connection('tontine_balance_test')->create('retraits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('carnet_id')->nullable();
            $table->decimal('montant_net', 15, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
        });
        Schema::connection('tontine_balance_test')->create('credit_settlement_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('carnet_id')->nullable();
            $table->decimal('montant_affecte', 15, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
        });
    }

    protected function tearDown(): void
    {
        DB::purge('tontine_balance_test');
        parent::tearDown();
    }

    public function test_already_deducted_commission_is_not_charged_again_after_a_partial_withdrawal(): void
    {
        DB::table('cycles')->insert(['id' => 1, 'montant_journalier' => 100]);
        DB::table('collectes')->insert(['cycle_id' => 1, 'montant' => 1000]);
        DB::table('retraits')->insert([
            'cycle_id' => 1,
            'montant_net' => 300,
            'commission' => 100,
        ]);

        $cycle = Cycle::findOrFail(1);

        $this->assertSame(600.0, (float) $cycle->solde_net_restant);
    }

    public function test_commission_is_reserved_before_the_first_withdrawal(): void
    {
        DB::table('cycles')->insert(['id' => 1, 'montant_journalier' => 100]);
        DB::table('collectes')->insert(['cycle_id' => 1, 'montant' => 1000]);

        $cycle = Cycle::findOrFail(1);

        $this->assertSame(900.0, (float) $cycle->solde_net_restant);
    }

    public function test_internal_credit_settlement_uses_savings_without_deducting_a_new_commission(): void
    {
        DB::table('cycles')->insert(['id' => 1, 'montant_journalier' => 100]);
        DB::table('collectes')->insert(['cycle_id' => 1, 'montant' => 1000]);
        DB::table('credit_settlement_sources')->insert([
            'cycle_id' => 1,
            'montant_affecte' => 300,
            'commission' => 0,
        ]);

        $cycle = Cycle::findOrFail(1);

        $this->assertSame(700.0, (float) $cycle->solde_compensation_restant);
    }

    public function test_compensation_balance_only_excludes_withdrawal_fees_already_recorded(): void
    {
        DB::table('cycles')->insert(['id' => 1, 'montant_journalier' => 100]);
        DB::table('collectes')->insert(['cycle_id' => 1, 'montant' => 1000]);
        DB::table('retraits')->insert([
            'cycle_id' => 1,
            'montant_net' => 300,
            'commission' => 100,
        ]);

        $cycle = Cycle::findOrFail(1);

        $this->assertSame(600.0, (float) $cycle->solde_compensation_restant);
    }

    public function test_savings_account_balance_excludes_previous_internal_settlements(): void
    {
        DB::table('carnets')->insert(['id' => 1]);
        DB::table('depots')->insert(['carnet_id' => 1, 'montant' => 1000]);
        DB::table('credit_settlement_sources')->insert([
            'cycle_id' => null,
            'carnet_id' => 1,
            'montant_affecte' => 300,
            'commission' => 0,
        ]);

        $carnet = Carnet::findOrFail(1);

        $this->assertSame(700.0, (float) $carnet->solde_disponible);
    }
}
