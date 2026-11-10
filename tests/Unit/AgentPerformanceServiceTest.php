<?php

namespace Tests\Unit;

use App\Services\AgentPerformanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgentPerformanceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'agent_performance_test',
            'database.connections.agent_performance_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('agent_performance_test');

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('code_agent');
            $table->string('nom');
            $table->boolean('actif')->default(true);
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('carnets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->nullable();
        });
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carnet_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('statut');
            $table->date('date_debut');
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_cloture_reelle')->nullable();
            $table->integer('montant_journalier');
            $table->integer('nombre_jours_objectif');
        });
        Schema::create('carnet_agent_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carnet_id');
            $table->unsignedBigInteger('agent_id');
            $table->dateTime('assigned_at');
            $table->dateTime('unassigned_at')->nullable();
        });
        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('agent_id');
            $table->integer('pointage');
            $table->decimal('montant', 15, 2);
            $table->dateTime('date_saisie');
        });
    }

    protected function tearDown(): void
    {
        DB::purge('agent_performance_test');
        parent::tearDown();
    }

    public function test_monthly_report_counts_weekdays_and_includes_late_collections(): void
    {
        $agent = $this->createAgent('A-001', 'Agent Un');
        $carnet = $this->createCarnet($agent);
        $firstCycle = $this->createCycle($carnet, '2024-03-04', '2024-03-08', 100, 5);
        $this->createCycle($carnet, '2024-03-11', '2024-03-12', 50, 2);
        $lateCycle = $this->createCycle($carnet, '2024-02-01', '2024-02-07', 20, 5);

        $this->createCollection($firstCycle, $agent, '2024-03-04 10:00:00', 100, 1);
        $this->createCollection($firstCycle, $agent, '2024-03-08 10:00:00', 150, 2);
        $this->createCollection($firstCycle, $agent, '2024-03-09 10:00:00', 999, 9);
        $this->createCollection($lateCycle, $agent, '2024-03-13 10:00:00', 40, 1);

        $report = app(AgentPerformanceService::class)->monthly(2024, 3);

        $this->assertCount(1, $report['rows']);
        $row = $report['rows']->first();
        $this->assertSame(600.0, $row['expected']);
        $this->assertSame(290.0, $row['collected']);
        $this->assertSame(4, $row['pointages']);
        $this->assertSame(3, $row['cycles']);
        $this->assertSame(7, $row['weekdays']);
        $this->assertSame(3, $report['summary']['cycles']);
        $this->assertSame(290.0, array_sum($report['daily_collected']));
    }

    public function test_expected_amount_follows_carnet_assignment_history_and_selected_sort(): void
    {
        $previousAgent = $this->createAgent('A-001', 'Agent Un');
        $newAgent = $this->createAgent('A-002', 'Agent Deux');
        $carnet = $this->createCarnet($newAgent);
        $cycle = $this->createCycle($carnet, '2024-03-04', '2024-03-07', 100, 4);

        DB::table('carnet_agent_histories')->insert([
            [
                'carnet_id' => $carnet,
                'agent_id' => $previousAgent,
                'assigned_at' => '2024-03-01 09:00:00',
                'unassigned_at' => '2024-03-05 10:00:00',
            ],
            [
                'carnet_id' => $carnet,
                'agent_id' => $newAgent,
                'assigned_at' => '2024-03-05 10:00:00',
                'unassigned_at' => null,
            ],
        ]);
        $this->createCollection($cycle, $previousAgent, '2024-03-04 10:00:00', 50, 1);
        $this->createCollection($cycle, $newAgent, '2024-03-05 10:00:00', 120, 1);

        $report = app(AgentPerformanceService::class)->monthly(2024, 3, null, 'collected');

        $this->assertSame(2, $report['rows'][0]['agent_id']);
        $this->assertSame(1, $report['rows'][0]['rank']);
        $this->assertSame(1, $report['rows'][1]['agent_id']);
        $this->assertSame(2, $report['rows'][1]['rank']);
        $this->assertSame(300.0, $report['rows'][0]['expected']);
        $this->assertSame(100.0, $report['rows'][1]['expected']);
    }

    private function createAgent(string $code, string $name): int
    {
        return (int) DB::table('agents')->insertGetId([
            'code_agent' => $code,
            'nom' => $name,
            'actif' => true,
        ]);
    }

    private function createCarnet(int $agentId): int
    {
        return (int) DB::table('carnets')->insertGetId(['agent_id' => $agentId]);
    }

    private function createCycle(int $carnetId, string $start, string $end, int $dailyAmount, int $objectiveDays): int
    {
        return (int) DB::table('cycles')->insertGetId([
            'carnet_id' => $carnetId,
            'statut' => 'en_cours',
            'date_debut' => $start,
            'date_fin_prevue' => $end,
            'montant_journalier' => $dailyAmount,
            'nombre_jours_objectif' => $objectiveDays,
        ]);
    }

    private function createCollection(int $cycleId, int $agentId, string $date, int $amount, int $pointages): void
    {
        DB::table('collectes')->insert([
            'cycle_id' => $cycleId,
            'agent_id' => $agentId,
            'date_saisie' => $date,
            'montant' => $amount,
            'pointage' => $pointages,
        ]);
    }
}
