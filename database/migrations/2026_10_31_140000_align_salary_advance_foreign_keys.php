<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('salary_advances')) {
            return;
        }

        $orphanedAgents = DB::table('salary_advances as advances')
            ->leftJoin('agents', 'agents.id', '=', 'advances.agent_id')
            ->whereNotNull('advances.agent_id')
            ->whereNull('agents.id')
            ->count();
        $orphanedEmployees = DB::table('salary_advances as advances')
            ->leftJoin('employe_administratifs', 'employe_administratifs.id', '=', 'advances.employe_administratif_id')
            ->whereNotNull('advances.employe_administratif_id')
            ->whereNull('employe_administratifs.id')
            ->count();

        if ($orphanedAgents || $orphanedEmployees) {
            throw new RuntimeException(sprintf(
                'Cannot add salary advance foreign keys: %d orphan agent IDs and %d orphan employee IDs must be reconciled first.',
                $orphanedAgents,
                $orphanedEmployees
            ));
        }

        if (DB::getDriverName() === 'mysql') {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME, COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'salary_advances'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                   AND COLUMN_NAME IN ('agent_id', 'employe_administratif_id')"
            );

            foreach ($foreignKeys as $foreignKey) {
                $name = str_replace('`', '``', $foreignKey->CONSTRAINT_NAME);
                DB::statement("ALTER TABLE `salary_advances` DROP FOREIGN KEY `{$name}`");
            }

            Schema::table('salary_advances', function (Blueprint $table) {
                $table->foreign('agent_id', 'salary_advances_agent_agents_fk')
                    ->references('id')
                    ->on('agents')
                    ->cascadeOnDelete();
                $table->foreign('employe_administratif_id', 'salary_advances_employee_fk')
                    ->references('id')
                    ->on('employe_administratifs')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('salary_advances')) {
            return;
        }

        Schema::table('salary_advances', function (Blueprint $table) {
            $table->dropForeign('salary_advances_agent_agents_fk');
            $table->dropForeign('salary_advances_employee_fk');
            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->cascadeOnDelete();
            $table->foreign('employe_administratif_id')
                ->references('id')
                ->on('employe_administratifs')
                ->cascadeOnDelete();
        });
    }
};
