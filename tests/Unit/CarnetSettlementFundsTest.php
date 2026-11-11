<?php

namespace Tests\Unit;

use App\Models\Carnet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CarnetSettlementFundsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'carnet_settlement_funds_test',
            'database.connections.carnet_settlement_funds_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('carnet_settlement_funds_test');

        Schema::create('carnets', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('type');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carnet_id');
            $table->string('statut');
            $table->timestamp('retire_at')->nullable();
            $table->decimal('montant_journalier', 12, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->decimal('montant', 12, 2)->default(0);
        });
        Schema::create('retraits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->decimal('montant_net', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);
        });
        Schema::create('credit_settlement_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->decimal('montant_affecte', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);
        });
    }

    protected function tearDown(): void
    {
        DB::purge('carnet_settlement_funds_test');
        parent::tearDown();
    }

    public function test_tontine_settlement_funds_load_for_linked_carnets(): void
    {
        DB::table('carnets')->insert([
            ['id' => 1, 'client_id' => 7, 'type' => 'tontine', 'parent_id' => null],
            ['id' => 2, 'client_id' => 7, 'type' => 'tontine', 'parent_id' => 1],
        ]);
        DB::table('cycles')->insert([
            ['id' => 1, 'carnet_id' => 1, 'statut' => 'termine', 'retire_at' => null],
            ['id' => 2, 'carnet_id' => 2, 'statut' => 'termine', 'retire_at' => null],
        ]);
        DB::table('collectes')->insert([
            ['cycle_id' => 1, 'montant' => 100],
            ['cycle_id' => 2, 'montant' => 50],
        ]);
        DB::table('retraits')->insert([
            ['cycle_id' => 2, 'montant_net' => 5, 'commission' => 5],
        ]);
        DB::table('credit_settlement_sources')->insert([
            ['cycle_id' => 1, 'montant_affecte' => 20, 'commission' => 5],
        ]);

        $carnet = Carnet::findOrFail(1);

        $this->assertSame(115.0, $carnet->availableTontineSettlementFunds());
    }
}
