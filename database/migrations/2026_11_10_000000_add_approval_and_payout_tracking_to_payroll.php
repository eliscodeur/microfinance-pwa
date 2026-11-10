<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->string('approval_status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
        });

        Schema::table('salaires', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
        });

        Schema::table('salaires_employe', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
        });

        DB::table('salary_advances')
            ->whereIn('statut', ['en_cours', 'soldee'])
            ->update([
                'approval_status' => 'approved',
                'approved_at' => DB::raw('COALESCE(updated_at, created_at)'),
                'disbursed_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);

        $advanceIds = DB::table('salary_advances')
            ->whereIn('statut', ['en_cours', 'soldee'])
            ->pluck('id');
        $disbursements = DB::table('mouvements_caisses')
            ->where('source_type', \App\Models\SalaryAdvance::class)
            ->whereIn('source_id', $advanceIds)
            ->orderBy('id')
            ->get(['source_id', 'user_id', 'date_mouvement'])
            ->unique('source_id');

        foreach ($disbursements as $disbursement) {
            DB::table('salary_advances')
                ->where('id', $disbursement->source_id)
                ->update([
                    'disbursed_by' => $disbursement->user_id,
                    'disbursed_at' => $disbursement->date_mouvement,
                ]);
        }

        DB::table('salary_advances')
            ->where('statut', 'rejetee')
            ->update([
                'approval_status' => 'rejected',
                'rejected_by' => DB::raw('approved_by'),
                'rejected_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);

        DB::table('salaires')
            ->where('statut', 'valide')
            ->whereNotNull('depense_id')
            ->update([
                'statut' => 'paye',
                'paid_by' => DB::raw('(SELECT user_id FROM depenses WHERE depenses.id = salaires.depense_id)'),
                'paid_at' => DB::raw('(SELECT COALESCE(depenses.created_at, salaires.updated_at, salaires.created_at) FROM depenses WHERE depenses.id = salaires.depense_id)'),
            ]);

        DB::table('salaires_employe')
            ->where('statut', 'valide')
            ->whereNotNull('depense_id')
            ->update([
                'statut' => 'paye',
                'paid_by' => DB::raw('(SELECT user_id FROM depenses WHERE depenses.id = salaires_employe.depense_id)'),
                'paid_at' => DB::raw('(SELECT COALESCE(depenses.created_at, salaires_employe.updated_at, salaires_employe.created_at) FROM depenses WHERE depenses.id = salaires_employe.depense_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['disbursed_by']);
            $table->dropColumn([
                'approval_status',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'disbursed_by',
                'disbursed_at',
            ]);
        });

        Schema::table('salaires', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['created_by', 'rejected_by', 'rejected_at', 'rejection_reason', 'paid_by', 'paid_at']);
        });

        Schema::table('salaires_employe', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['created_by', 'rejected_by', 'rejected_at', 'rejection_reason', 'paid_by', 'paid_at']);
        });
    }
};
