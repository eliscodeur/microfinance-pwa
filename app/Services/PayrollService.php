<?php
namespace App\Services;

use App\Models\Agent;
use App\Models\Carnet;
use App\Models\SalaryGrid;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Calcule la commission globale sur les carnets pour un agent sur une période donnée.
     */
    public function calculerCommissionGlobaleCarnets(Agent $agent, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $dateDebut     = $dateDebut ? Carbon::parse($dateDebut)->startOfDay() : now()->startOfMonth();
        $dateFin       = $dateFin ? Carbon::parse($dateFin)->endOfDay() : now()->endOfMonth();
        $dateReference = $dateDebut->toDateString();

        // 1. Récupérer les IDs des carnets dont l'agent est le premier gestionnaire sur la période
        $carnetsIds = DB::table('carnet_agent_histories')
            ->select('carnet_id')
            ->where('agent_id', $agent->id)
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('carnet_agent_histories')
                    ->groupBy('carnet_id');
            })
            ->whereBetween('assigned_at', [$dateDebut, $dateFin])
            ->pluck('carnet_id');
        // dd($carnetsIds);
        // 2. Récupérer les carnets et calculer le total collecté
        $carnets = Carnet::with('categoryTontine')
            ->whereIn('id', $carnetsIds)
            ->get();

        $totalCarnetVendus = $carnets->sum(function ($carnet) {
            return $carnet->categoryTontine->prix ?? 0;
        });

        // 3. Récupérer le montant total des paiements non inclus (commissions sur cycle) pour affiner la grille
        $totalBonus = DB::table('paiements')
            ->where('agent_id', $agent->id)
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->whereNull('inclus_dans_salaire_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('bonuses')
                    ->whereColumn('bonuses.paiement_id', 'paiements.id')
                    ->whereNull('cycle_id');
            })
            ->sum('montant_total');

        $totalCommissionCycle = DB::table('paiements')
            ->where('agent_id', $agent->id)
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->whereNull('inclus_dans_salaire_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('bonuses')
                    ->whereColumn('bonuses.paiement_id', 'paiements.id')
                    ->whereNotNull('cycle_id');
            })
            ->sum('montant_total');

        // 4. Interroger la grille salariale
        $grilleModel = new SalaryGrid();
        $grille      = $grilleModel->getGrilleForAmount($totalCommissionCycle, $dateReference);

        $tauxCarnet        = $grille ? $grille->taux_carnet : 25.00;
        $salaireBase       = $grille ? $grille->salaire_base : 0.00;
        $commissionTravail = $grille ? $grille->commission_travail : 0.00;

        // 5. Calculer le montant total de la commission
        $montantTotalCommission = ($totalCarnetVendus * $tauxCarnet) / 100;

        return [
            'salaire_base'                   => (float) $salaireBase,
            'commission_travail'             => (float) $commissionTravail,
            'montant_commission_carnet'      => (float) $montantTotalCommission,
            'montant_total_commission_cycle' => (float) $totalCommissionCycle,
            'montant_total_bonus'            => (float) $totalBonus,
            'taux_carnet'                    => (float) $tauxCarnet,
        ];
    }
}
