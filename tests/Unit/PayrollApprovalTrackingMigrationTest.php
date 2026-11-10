<?php

namespace Tests\Unit;

use App\Models\SalaryAdvance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayrollApprovalTrackingMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'payroll_tracking_migration_test',
            'database.connections.payroll_tracking_migration_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('payroll_tracking_migration_test');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->string('statut');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
        Schema::create('salaires', function (Blueprint $table) {
            $table->id();
            $table->string('statut');
            $table->unsignedBigInteger('depense_id')->nullable();
            $table->timestamps();
        });
        Schema::create('salaires_employe', function (Blueprint $table) {
            $table->id();
            $table->string('statut');
            $table->unsignedBigInteger('depense_id')->nullable();
            $table->timestamps();
        });
        Schema::create('mouvements_caisses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->string('source_type');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('date_mouvement');
        });
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('payroll_tracking_migration_test');
        parent::tearDown();
    }

    public function test_migration_adds_workflow_fields_and_backfills_legacy_records(): void
    {
        DB::table('users')->insert(['id' => 1]);
        DB::table('salary_advances')->insert([
            ['id' => 1, 'statut' => 'en_cours', 'approved_by' => null, 'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-02 10:00:00'],
            ['id' => 2, 'statut' => 'rejetee', 'approved_by' => 1, 'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-02 10:00:00'],
        ]);
        DB::table('mouvements_caisses')->insert([
            'source_id' => 1,
            'source_type' => SalaryAdvance::class,
            'user_id' => 1,
            'date_mouvement' => '2026-01-03 10:00:00',
        ]);
        DB::table('depenses')->insert([
            'id' => 1,
            'user_id' => 1,
            'created_at' => '2026-01-04 10:00:00',
            'updated_at' => '2026-01-04 10:00:00',
        ]);
        DB::table('salaires')->insert([
            'id' => 1,
            'statut' => 'valide',
            'depense_id' => 1,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-02 10:00:00',
        ]);
        DB::table('salaires_employe')->insert([
            'id' => 1,
            'statut' => 'valide',
            'depense_id' => 1,
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-02 10:00:00',
        ]);

        $migration = require base_path('database/migrations/2026_11_10_000000_add_approval_and_payout_tracking_to_payroll.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('salary_advances', [
            'approval_status', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason', 'disbursed_by', 'disbursed_at',
        ]));
        $this->assertSame('approved', DB::table('salary_advances')->where('id', 1)->value('approval_status'));
        $this->assertSame(1, DB::table('salary_advances')->where('id', 1)->value('disbursed_by'));
        $this->assertSame('2026-01-03 10:00:00', DB::table('salary_advances')->where('id', 1)->value('disbursed_at'));
        $this->assertSame('rejected', DB::table('salary_advances')->where('id', 2)->value('approval_status'));
        $this->assertSame('paye', DB::table('salaires')->where('id', 1)->value('statut'));
        $this->assertSame(1, DB::table('salaires')->where('id', 1)->value('paid_by'));
        $this->assertSame('paye', DB::table('salaires_employe')->where('id', 1)->value('statut'));

    }
}
