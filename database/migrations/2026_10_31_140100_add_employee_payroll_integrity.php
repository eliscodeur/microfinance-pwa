<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('salaires_employe')
            ->select('employe_id', 'mois', 'annee')
            ->groupBy('employe_id', 'mois', 'annee')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicates) {
            throw new RuntimeException(sprintf(
                'Cannot enforce one employee payroll per period: duplicate rows exist for employee %d, %02d/%d. Reconcile them before retrying.',
                $duplicates->employe_id,
                $duplicates->mois,
                $duplicates->annee
            ));
        }

        Schema::table('salaires_employe', function (Blueprint $table) {
            $table->unique(
                ['employe_id', 'mois', 'annee'],
                'salaires_employe_employee_period_unique'
            );

            if (! Schema::hasColumn('salaires_employe', 'validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('salaires_employe', function (Blueprint $table) {
            $table->dropUnique('salaires_employe_employee_period_unique');

            if (Schema::hasColumn('salaires_employe', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
        });
    }
};
