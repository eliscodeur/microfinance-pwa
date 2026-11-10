<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\MouvementCaisse;
use App\Models\Salaire;
use App\Models\SalaireEmploye;
use App\Models\SalaryAdvance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollWorkflowService
{
    public function approveAdvance(string $uid): void
    {
        DB::transaction(function () use ($uid) {
            $advance = SalaryAdvance::where('advance_uid', $uid)->lockForUpdate()->firstOrFail();
            if ($advance->statut !== 'en_attente' || $advance->approval_status !== 'pending') {
                throw ValidationException::withMessages(['advance' => 'Cette demande n’est plus en attente d’approbation.']);
            }

            $advance->update([
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });
    }

    public function rejectAdvance(string $uid, ?string $reason): void
    {
        DB::transaction(function () use ($uid, $reason) {
            $advance = SalaryAdvance::where('advance_uid', $uid)->lockForUpdate()->firstOrFail();
            if ($advance->statut !== 'en_attente' || $advance->approval_status !== 'pending') {
                throw ValidationException::withMessages(['advance' => 'Cette demande n’est plus en attente de décision.']);
            }

            $advance->update([
                'statut' => 'rejetee',
                'approval_status' => 'rejected',
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });
    }

    public function disburseAdvance(string $uid): void
    {
        DB::transaction(function () use ($uid) {
            $advance = SalaryAdvance::where('advance_uid', $uid)->lockForUpdate()->firstOrFail();
            if ($advance->statut !== 'en_attente' || $advance->approval_status !== 'approved' || $advance->disbursed_at) {
                throw ValidationException::withMessages(['advance' => 'Seule une avance approuvée et non décaissée peut être payée.']);
            }

            $beneficiary = $advance->agent?->nom
                ?? trim(($advance->employe?->nom ?? '') . ' ' . ($advance->employe?->prenoms ?? ''));
            $reference = 'AV-SAL-' . strtoupper($advance->advance_uid);

            $expense = $this->createExpense(
                (float) $advance->montant_total,
                $beneficiary,
                $reference,
                'Décaissement d’avance sur salaire - ' . $beneficiary,
                'avance sur salaire'
            );
            $this->createCashMovement($expense, (float) $advance->montant_total, $reference, 'Décaissement avance sur salaire - ' . $beneficiary, 'avance sur salaire');

            $advance->update([
                'statut' => 'en_cours',
                'disbursed_by' => Auth::id(),
                'disbursed_at' => now(),
            ]);
        });
    }

    public function approveSalary(string $type, string $identifier): void
    {
        DB::transaction(function () use ($type, $identifier) {
            $salary = $this->lockSalary($type, $identifier);
            if (strtolower((string) $salary->statut) !== 'brouillon') {
                throw ValidationException::withMessages(['salary' => 'Seul un bulletin en brouillon peut être approuvé.']);
            }

            $salary->update([
                'statut' => 'valide',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->synchronizeApprovedAdvanceDeductions($salary, $type);

            if ($type === 'agent') {
                DB::table('paiements')
                    ->where('agent_id', $salary->agent_id)
                    ->whereNull('inclus_dans_salaire_at')
                    ->whereBetween('created_at', [
                        $salary->periode_debut->copy()->startOfDay(),
                        $salary->periode_fin->copy()->endOfDay(),
                    ])
                    ->update(['inclus_dans_salaire_at' => now()]);
            }
        });
    }

    public function rejectSalary(string $type, string $identifier, ?string $reason): void
    {
        DB::transaction(function () use ($type, $identifier, $reason) {
            $salary = $this->lockSalary($type, $identifier);
            if (strtolower((string) $salary->statut) !== 'brouillon') {
                throw ValidationException::withMessages(['salary' => 'Seul un bulletin en brouillon peut être rejeté.']);
            }

            $this->clearSalaryDeductions($salary, $type);
            $salary->update([
                'statut' => 'rejete',
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });
    }

    public function paySalary(string $type, string $identifier): void
    {
        DB::transaction(function () use ($type, $identifier) {
            $salary = $this->lockSalary($type, $identifier);
            if (strtolower((string) $salary->statut) !== 'valide' || $salary->paid_at) {
                throw ValidationException::withMessages(['salary' => 'Seul un bulletin approuvé et non payé peut être réglé.']);
            }

            $employee = $type === 'agent' ? $salary->agent : $salary->employe;
            $beneficiary = $type === 'agent'
                ? ($employee->nom ?? $employee->name ?? 'Agent')
                : trim(($employee->nom ?? '') . ' ' . ($employee->prenoms ?? ''));
            $reference = $type === 'agent'
                ? $salary->reference
                : 'SAL-EMP-' . $salary->annee . '-' . str_pad((string) $salary->mois, 2, '0', STR_PAD_LEFT) . '-' . $salary->ulid;
            $amount = (float) ($type === 'agent' ? $salary->montant_net : $salary->salaire_net);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le salaire net doit être supérieur à zéro pour enregistrer un paiement.']);
            }

            $expense = $this->createExpense(
                $amount,
                $beneficiary,
                $reference,
                'Paiement du salaire de ' . $beneficiary . ' - ' . str_pad((string) $salary->mois, 2, '0', STR_PAD_LEFT) . '/' . $salary->annee,
                'salaire'
            );
            $this->createCashMovement(
                $expense,
                $amount,
                $reference,
                'Paiement salaire - ' . $beneficiary,
                $type === 'agent' ? 'salaire agent' : 'salaire employe'
            );

            $salary->update([
                'statut' => 'paye',
                'depense_id' => $expense->id,
                'paid_by' => Auth::id(),
                'paid_at' => now(),
            ]);
        });
    }

    private function lockSalary(string $type, string $identifier): Model
    {
        if ($type === 'agent') {
            return Salaire::with('agent')->whereKey($identifier)->lockForUpdate()->firstOrFail();
        }

        if ($type === 'employee') {
            return SalaireEmploye::with('employe')->where('ulid', $identifier)->lockForUpdate()->firstOrFail();
        }

        throw new \InvalidArgumentException('Type de paie inconnu.');
    }

    private function createExpense(float $amount, string $beneficiary, string $reference, string $reason, string $categoryHint): Depense
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Le montant à décaisser doit être supérieur à zéro.']);
        }

        try {
            app(CashExpenseGuard::class)->assertForAuthenticatedUser('especes', $amount);
        } catch (\DomainException $exception) {
            throw ValidationException::withMessages(['caisse' => $exception->getMessage()]);
        }

        $category = DB::table('categories_charges')
            ->where('libelle', 'LIKE', '%' . $categoryHint . '%')
            ->value('id');

        if (! $category) {
            $category = DB::table('categories_charges')
                ->where('code_analytique', '6611')
                ->value('id');
        }

        if (! $category) {
            throw ValidationException::withMessages([
                'category' => 'Aucune catégorie de charge du personnel (6611) n’est configurée pour enregistrer ce paiement.',
            ]);
        }

        return Depense::create([
            'ulid' => strtolower((string) Str::ulid()),
            'categories_charge_id' => $category,
            'montant' => $amount,
            'date_depense' => now()->toDateString(),
            'beneficiaire' => $beneficiary,
            'mode_paiement' => 'Espèces',
            'reference_piece' => $reference,
            'motif' => $reason,
            'user_id' => Auth::id(),
        ]);
    }

    private function createCashMovement(Depense $expense, float $amount, string $reference, string $description, string $operation): void
    {
        try {
            MouvementCaisse::create([
                'ulid' => strtolower((string) Str::ulid()),
                'type_operation' => $operation,
                'sens' => 'sortie',
                'montant' => $amount,
                'date_mouvement' => now(),
                'mode_paiement' => 'Espèces',
                'reference' => $reference,
                'libelle' => $description,
                'source_type' => Depense::class,
                'source_id' => $expense->id,
                'user_id' => Auth::id(),
            ]);
        } catch (\DomainException $exception) {
            throw ValidationException::withMessages(['caisse' => $exception->getMessage()]);
        }
    }

    private function synchronizeApprovedAdvanceDeductions(Model $salary, string $type): void
    {
        $foreignKey = $type === 'agent' ? 'agent_id' : 'employe_administratif_id';
        $beneficiaryId = $type === 'agent' ? $salary->agent_id : $salary->employe_id;
        $advanceIds = DB::table('deductions_avances')
            ->where($foreignKey, $beneficiaryId)
            ->where('mois', $salary->mois)
            ->where('annee', $salary->annee)
            ->pluck('salary_advance_id');

        foreach (SalaryAdvance::whereIn('id', $advanceIds)->lockForUpdate()->get() as $advance) {
            $deductions = DB::table('deductions_avances as deductions')
                ->where('deductions.salary_advance_id', $advance->id)
                ->whereExists(function ($query) use ($type) {
                    if ($type === 'agent') {
                        $query->selectRaw('1')
                            ->from('salaires')
                            ->whereColumn('salaires.agent_id', 'deductions.agent_id')
                            ->whereColumn('salaires.mois', 'deductions.mois')
                            ->whereColumn('salaires.annee', 'deductions.annee')
                            ->whereIn('salaires.statut', ['valide', 'paye']);
                    } else {
                        $query->selectRaw('1')
                            ->from('salaires_employe')
                            ->whereColumn('salaires_employe.employe_id', 'deductions.employe_administratif_id')
                            ->whereColumn('salaires_employe.mois', 'deductions.mois')
                            ->whereColumn('salaires_employe.annee', 'deductions.annee')
                            ->whereIn('salaires_employe.statut', ['valide', 'paye']);
                    }
                });
            $deductedTranches = (int) $deductions->sum('deductions.nombre_tranches');
            $advance->update([
                'tranches_payees' => $deductedTranches,
                'statut' => $deductedTranches >= $advance->nombre_tranches ? 'soldee' : 'en_cours',
            ]);
        }
    }

    private function clearSalaryDeductions(Model $salary, string $type): void
    {
        $foreignKey = $type === 'agent' ? 'agent_id' : 'employe_administratif_id';
        $beneficiaryId = $type === 'agent' ? $salary->agent_id : $salary->employe_id;

        DB::table('deductions_avances')
            ->where($foreignKey, $beneficiaryId)
            ->where('mois', $salary->mois)
            ->where('annee', $salary->annee)
            ->delete();
    }
}
