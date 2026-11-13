<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\AgentController;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class AgentPinResetAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->nullable();
            $table->string('pin_hash')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
    }

    public function test_reset_writes_a_pin_reset_audit_for_the_agent_without_exposing_the_hash(): void
    {
        $agent = Agent::withoutAuditing(function () {
            return Agent::create(['pin_hash' => 'sensitive-hash']);
        });

        $actor = new User();
        $actor->id = 7;

        $request = Request::create('/admin/agents/' . $agent->id . '/reset-pin', 'POST');
        $request->setUserResolver(function () use ($actor) {
            return $actor;
        });

        $response = (new AgentController())->resetPin($request, $agent->id);

        $audit = Audit::query()->where('event', 'agent_pin_reset')->firstOrFail();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($agent->fresh()->pin_hash);
        $this->assertSame(Agent::class, $audit->auditable_type);
        $this->assertSame((int) $agent->id, (int) $audit->auditable_id);
        $this->assertSame(['pin_configured' => true], $audit->old_values);
        $this->assertSame(['pin_configured' => false], $audit->new_values);
        $this->assertStringNotContainsString('sensitive-hash', json_encode($audit->toArray()));
    }
}
