<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Bonus;
use App\Models\Carnet;
use App\Models\Cycle;
use App\Models\DeductionAvance;
use App\Models\SalaryAdvance;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        // Récupération de la période (par défaut le mois passé, ex: 2026-08)
        $mois  = $request->input('mois', now()->subMonth()->month);
        $annee = $request->input('annee', now()->subMonth()->year);

        // Sécurisation au cas où les valeurs seraient vides
        $mois  = ! empty($mois) ? $mois : now()->subMonth()->month;
        $annee = ! empty($annee) ? $annee : now()->subMonth()->year;

        // Déduction des dates de début et de fin du mois sélectionné
        $dateDebut = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin   = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        $agents   = Agent::all();
        $payrolls = [];

        // Calcul ou récupération automatique pour tous les agents sur ce mois
        foreach ($agents as $agent) {
            // Récupération des données du service (qui retourne un tableau)
            $calculs = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

            $salaireBase       = $calculs['salaire_base'] ?? ($agent->salaire_base ?? 0);
            $commissionTravail = $calculs['commission_travail'] ?? 0;
            $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
            $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
            $bonus             = $calculs['montant_total_bonus'] ?? 0;

            // --- GESTION DES AVANCES SUR SALAIRE (Étalement) ---
            $avancesEnCours = \App\Models\SalaryAdvance::where('agent_id', $agent->id)
                ->where('statut', 'en_cours')
                ->get();

            // 2. On vérifie s'il existe des choix personnalisés dans notre table 'deductions_avances'
            $deductionsPersonnalisees = \App\Models\DeductionAvance::where('agent_id', $agent->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->get();

            if ($deductionsPersonnalisees->isNotEmpty()) {
                // Si l'administrateur a fait un choix spécifique (coché/décoché dans la modale)
                $totalAvanceDeduite = $deductionsPersonnalisees->sum('montant');
            } else {
                // Comportement par défaut : on somme toutes les avances en cours
                $totalAvanceDeduite = $avancesEnCours->sum('montant_mensuel');
            }

            // Vérifier si un enregistrement existe déjà dans la table 'salaires' pour ce mois
            $salaireEnregistré = DB::table('salaires')
                ->where('agent_id', $agent->id)
                ->where('mois', $dateDebut->month)
                ->where('annee', $dateDebut->year)
                ->first();

            // Calcul du salaire net global en additionnant les gains et en soustrayant l'avance étalée
            $salaireBrut = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;
            $salaireNet  = max(0, $salaireBrut - $totalAvanceDeduite); // Évite un net négatif par sécurité

            $payrolls[] = (object) [
                'agent'              => $agent,
                'salaire_base'       => $salaireBase,
                'commission_travail' => $commissionTravail,
                'commission_carnet'  => $commissionCarnet,
                'commission_cycle'   => $commissionCycle,
                'bonus'              => $bonus,
                'avance_deduite'     => $totalAvanceDeduite, // Pour affichage éventuel sur le bulletin
                'avances_concernes'  => $avancesEnCours,     // Pour le suivi
                'salaire_net'        => $salaireNet,
                'statut'             => $salaireEnregistré ? ucfirst($salaireEnregistré->statut) : 'En attente',
            ];
        }

        return view('admin.payrolls.index', compact('payrolls', 'mois', 'annee'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|date_format:Y-m',
            ]);

            $periodeInput = $request->input('periode');
            $dateDebut    = Carbon::createFromFormat('Y-m', $periodeInput)->startOfMonth();
            $dateFin      = Carbon::createFromFormat('Y-m', $periodeInput)->endOfMonth();

            $mois   = $dateDebut->month;
            $annee  = $dateDebut->year;
            $agents = Agent::all();

            // 1. Récupérer en amont la catégorie de charge liée aux salaires
            $categorieSalaire = DB::table('categories_charges')
                ->where('libelle', 'LIKE', '%salaire%')
                ->orWhere('code_analytique', 'LIKE', '66%')
                ->first();

            $categorieId = $categorieSalaire ? $categorieSalaire->id : null;

            DB::transaction(function () use ($agents, $dateDebut, $dateFin, $mois, $annee, $categorieId) {
                foreach ($agents as $agent) {
                    // 1. Récupération des composantes financières PayrollService
                    $payrollDetails = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

                    $salaireBase             = $payrollDetails['salaire_base'] ?? ($agent->salaire_base ?? 0);
                    $commissionTravail       = $payrollDetails['commission_travail'] ?? 0;
                    $montantCommissionCarnet = $payrollDetails['montant_commission_carnet'] ?? 0;
                    $totalCommissionCycle    = $payrollDetails['montant_total_commission_cycle'] ?? 0;
                    $totalBonus              = $payrollDetails['montant_total_bonus'] ?? 0;

                    // Calcul du brut ou total avant déductions d'avances
                    $montantBrut = $salaireBase + $commissionTravail + $montantCommissionCarnet + $totalCommissionCycle + $totalBonus;

                    // 2. GESTION DES AVANCES SUR SALAIRE POUR CET AGENT
                    $avancesList = DB::table('salary_advances')
                        ->where('agent_id', $agent->id)
                        ->where('statut', 'en_cours')
                        ->get();

                    $totalDeductionsAvances = 0;

                    foreach ($avancesList as $avance) {$deductionExistante = DB::table('deductions_avances')
                            ->where('salary_advance_id', $avance->id)
                            ->where('mois', $mois)
                            ->where('annee', $annee)
                            ->first();

                        if (! $deductionExistante) {
                            $montantTranche = $avance->montant_mensuel ?? 0;

                            if ($montantTranche > 0) {
                                DB::table('deductions_avances')->insert([
                                    'ulid'              => (string) \Illuminate\Support\Str::ulid(),
                                    'agent_id'          => $agent->id,
                                    'salary_advance_id' => $avance->id,
                                    'montant'           => $montantTranche,
                                    'nombre_tranches'   => 1,
                                    'mois'              => $mois,
                                    'annee'             => $annee,
                                    'created_at'        => now(),
                                    'updated_at'        => now(),
                                ]);
                            }
                        }

                        $totalTranchesPayees = DB::table('deductions_avances')
                            ->where('salary_advance_id', $avance->id)
                            ->sum('nombre_tranches');

                        $montantCeMois = DB::table('deductions_avances')
                            ->where('salary_advance_id', $avance->id)
                            ->where('mois', $mois)
                            ->where('annee', $annee)
                            ->sum('montant');

                        $totalDeductionsAvances += $montantCeMois;

                        $updateData = ['tranches_payees' => $totalTranchesPayees];
                        if ($totalTranchesPayees >= $avance->nombre_tranches) {$updateData['statut'] = 'soldee';}

                        DB::table('salary_advances')
                            ->where('id', $avance->id)
                            ->update($updateData);}

                    // Le montant net final
                    $montantNet = max(0, $montantBrut - $totalDeductionsAvances);
                    // 3. GESTION DE LA DÉPENSE ASSOCIÉE (Comptabilité / Trésorerie)
                    // Vérifier si un salaire existe déjà pour récupérer son éventuel depense_id existant
                    $salaireExistant = DB::table('salaires')
                        ->where('agent_id', $agent->id)
                        ->where('mois', $mois)
                        ->where('annee', $annee)
                        ->first();

                    $depenseId    = $salaireExistant ? $salaireExistant->depense_id : null;
                    $motifDepense = 'Paiement du salaire de ' . $agent->name . ' (Mois : ' . $mois . '/' . $annee . ')';

                    if ($montantNet > 0) {
                        if ($depenseId) {
                            // Mise à jour de la dépense existante si le salaire est recalculé
                            DB::table('depenses')->where('id', $depenseId)->update([
                                'categories_charge_id' => $categorieId,
                                'montant'              => $montantNet,
                                'motif'                => $motifDepense,
                                'updated_at'           => now(),
                            ]);
                        } else {
                            // Création d'une nouvelle ligne de décaissement dans les dépenses
                            $depenseId = DB::table('depenses')->insertGetId([
                                'ulid'                 => (string) \Illuminate\Support\Str::ulid(),
                                'categories_charge_id' => $categorieId,
                                'montant'              => $montantNet,
                                'date_depense'         => now(),
                                'beneficiaire'         => $agent->name ?? 'Personnel',
                                'mode_paiement'        => 'Virement Bancaire',
                                'reference_piece'      => 'SAL-' . $annee . '-' . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . $agent->code_agent,
                                'motif'                => $motifDepense,
                                'user_id'              => auth()->id(),
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ]);
                        }
                    }

                    // 4. Enregistrer ou mettre à jour le salaire net avec la liaison

                    \App\Models\Salaire::updateOrCreate(
                        [
                            'agent_id' => $agent->id,
                            'mois'     => $mois,
                            'annee'    => $annee,
                        ],
                        [
                            'depense_id'         => $depenseId,
                            'salaire_base'       => $salaireBase,
                            'commission_travail' => $commissionTravail,
                            'commission_carnet'  => $montantCommissionCarnet,
                            'commission_cycle'   => $totalCommissionCycle,
                            'bonus'              => $totalBonus,
                            'total_avances'      => $totalDeductionsAvances,
                            'montant_net'        => $montantNet,
                            'periode_debut'      => $dateDebut->toDateString(),
                            'periode_fin'        => $dateFin->toDateString(),
                            'statut'             => 'valide',
                            'validated_by'       => auth()->id(),
                            'validated_at'       => now(),
                        ]
                    );
                    // 5. Marquer les paiements de cet agent sur la période comme inclus
                    DB::table('paiements')
                        ->where('agent_id', $agent->id)
                        ->whereNull('inclus_dans_salaire_at')
                        ->whereBetween('created_at', [$dateDebut->copy()->startOfDay(), $dateFin->copy()->endOfDay()])
                        ->update(['inclus_dans_salaire_at' => now()]);
                }
            });

            return redirect()->route('admin.payrolls.index', [
                'mois'  => $mois,
                'annee' => $annee,
            ])->with('success', 'Tous les salaires du mois ont été validés, comptabilisés en dépenses et verrouillés avec succès !');
        } catch (\Exception $e) {
            dd("ERREUR CATCHÉE : " . $e->getMessage(), $e->getTraceAsString());
        }
    }

    public function previewDetails(Request $request)
    {
        $agentUlid = $request->input('agent');
        $mois      = $request->input('mois');
        $annee     = $request->input('annee');

        $agent = Agent::where('ulid', $agentUlid)->firstOrFail();

        // Définition des dates de début et de fin du mois
        $dateDebut = \Carbon\Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin   = \Carbon\Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        // Récupérer le calcul des carnets et cycles pour cet agent sur la période
        $calculs = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

        $salaireBase       = $calculs['salaire_base'] ?? 0;
        $commissionTravail = $calculs['commission_travail'] ?? 0;
        $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
        $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
        $bonus             = $calculs['montant_total_bonus'] ?? 0;
        $tauxCarnet        = $calculs['taux_carnet'] ?? 25;

        // 1. Récupérer toutes les avances en cours et générer leurs tranches formatées
        $avancesList = \App\Models\SalaryAdvance::where('agent_id', $agent->id)
            ->where('statut', 'en_cours')
            ->get();

        foreach ($avancesList as $avance) {
            $montantMensuel = $avance->montant_mensuel;
            $nombreTranches = $avance->nombre_tranches ?? 1;
            $dateDepart     = \Carbon\Carbon::parse($avance->created_at ?? now());

            // A. Compter le CUMUL TOTAL des tranches déjà payées sur cette avance lors des bulletins PRÉCÉDENTS (excluant le mois/année en cours)
            $tranchesPasseesCount = DeductionAvance::where('salary_advance_id', $avance->id)
                ->where(function ($q) use ($mois, $annee) {
                    $q->where('annee', '<', $annee)
                        ->orWhere(function ($sub) use ($mois, $annee) {
                            $sub->where('annee', $annee)->where('mois', '<', $mois);
                        });
                })
                ->sum('nombre_tranches');

            // B. Vérifier s'il y a un enregistrement spécifique pour le MOIS EN COURS
            $deductionMoisEnCours = DeductionAvance::where('salary_advance_id', $avance->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->first();

            // Nombre de tranches à cocher pour ce mois-ci (s'il y a une saisie, on la prend, sinon par défaut 1 si tout n'est pas déjà payé)
            $nbTranchesMoisActuel = $deductionMoisEnCours
                ? $deductionMoisEnCours->nombre_tranches
                : ($tranchesPasseesCount < $nombreTranches ? 1 : 0);

            $tranches = [];

            for ($i = 1; $i <= $nombreTranches; $i++) {
                $moisTranche = $dateDepart->copy()->addMonths($i - 1);

                // Tranche appartenant aux mois précédents (bloquée / disabled)
                $estDejaPaye = ($i <= $tranchesPasseesCount);

                // Tranche incluse dans le mois en cours (sélectionnée par défaut ou via la saisie du mois)
                $estCocheMoisActuel = (! $estDejaPaye && $i <= ($tranchesPasseesCount + $nbTranchesMoisActuel));

                $tranches[] = [
                    'id'          => $i,
                    'numero'      => $i,
                    'mois'        => $moisTranche->translatedFormat('F Y'), // Indicatif textuel (juste pour l'affichage visuel)
                    'montant'     => $montantMensuel,
                    'deja_paye'   => $estDejaPaye,
                    'selectionne' => $estDejaPaye || $estCocheMoisActuel,
                ];
            }

            // Attache le tableau des tranches directement à l'objet avance
            $avance->tranches_formatees = $tranches;
        }

        // 2. Vérifier s'il existe des déductions personnalisées enregistrées pour ce mois/année précis
        $deductionsPersonnalisees = DeductionAvance::where('agent_id', $agent->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->get();

        // 3. Calcul du total des avances à déduire
        if ($deductionsPersonnalisees->isNotEmpty()) {
            $totalAvances            = $deductionsPersonnalisees->sum('montant');
            $idsAvancesSelectionnees = $deductionsPersonnalisees->pluck('salary_advance_id')->toArray();
        } else {
            // Par défaut, on somme les montants des tranches marquées comme sélectionnées pour ce mois
            $totalAvances            = 0;
            $idsAvancesSelectionnees = [];

            foreach ($avancesList as $avance) {
                $tranchesMois = collect($avance->tranches_formatees)->where('selectionne', true)->where('deja_paye', false);
                $sommeAvance  = $tranchesMois->count() * $avance->montant_mensuel;

                if ($sommeAvance > 0) {
                    $totalAvances              += $sommeAvance;
                    $idsAvancesSelectionnees[]  = $avance->id;
                }
            }
        }

        // Salaire Brut et Net
        $salaireBrut = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;
        $montantNet  = max(0, $salaireBrut - $totalAvances);

        // Construction de l'objet stdClass pour la vue
        $salaire                            = new \stdClass();
        $salaire->reference                 = 'PREV-' . $annee . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . $agent->ulid;
        $salaire->mois                      = $mois;
        $salaire->annee                     = $annee;
        $salaire->periode_debut             = $dateDebut;
        $salaire->periode_fin               = $dateFin;
        $salaire->salaire_base              = $salaireBase;
        $salaire->commission_travail        = $commissionTravail;
        $salaire->commission_carnet         = $commissionCarnet;
        $salaire->commission_cycle          = $commissionCycle;
        $salaire->bonus                     = $bonus;
        $salaire->motif_bonus               = $calculs['motif_bonus'] ?? null;
        $salaire->total_avances             = $totalAvances;
        $salaire->ids_avances_selectionnees = $idsAvancesSelectionnees;
        $salaire->montant_net               = $montantNet;
        $salaire->taux_carnet               = $tauxCarnet;
        $salaire->statut                    = 'En attente';
        $salaire->created_at                = null;
        $salaire->validator                 = null;
        $salaire->validated_at              = null;
        $salaire->agent                     = $agent;

        // Récupération des carnets de l'agent
        $carnetsIds = DB::table('carnet_agent_histories as ch1')
            ->select('ch1.carnet_id')
            ->where('ch1.agent_id', $agent->id)
            ->whereBetween('ch1.assigned_at', [$dateDebut, $dateFin])
            ->where('ch1.id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('carnet_agent_histories')
                    ->whereColumn('carnet_id', 'ch1.carnet_id');
            })
            ->pluck('carnet_id');

        $carnetsData = DB::table('carnet_agent_histories')
            ->select('carnet_id', 'assigned_at')
            ->where('agent_id', $agent->id)
            ->whereIn('carnet_id', $carnetsIds)
            ->get()
            ->keyBy('carnet_id');

        $carnets = Carnet::with(['client', 'categoryTontine', 'depots', 'retraits'])
            ->whereIn('id', $carnetsIds)
            ->where('type', 'tontine')
            ->get()
            ->map(function ($carnet) use ($mois, $annee, $tauxCarnet, $carnetsData) {
                $prixCategory = $carnet->categoryTontine->prix ?? 0;

                $carnet->assigned_at        = $carnetsData[$carnet->id]->assigned_at ?? null;
                $carnet->prix_category      = $prixCategory;
                $carnet->commission_generee = ($prixCategory * $tauxCarnet) / 100;

                return $carnet;
            });

        // Récupération des cycles
        $cycles = Cycle::where('agent_id', $agent->id)
            ->whereMonth('date_debut', $mois)
            ->whereYear('date_debut', $annee)
            ->with([
                'carnet.client',
                'collectes' => function ($query) use ($mois, $annee) {
                    $query->whereMonth('created_at', $mois)->whereYear('created_at', $annee);
                },
                'bonuses.paiement',
                'bonuses.validator',
            ])
            ->get()
            ->map(function ($cycle) {
                $cycle->nombre_pointages = $cycle->collectes->sum('pointage');
                $cycle->mise             = $cycle->montant_journalier;

                $bonus = $cycle->bonuses->first();

                if ($bonus && $bonus->paiement_id) {
                    $cycle->statut_paiement = 'Validé';
                    $cycle->validated_at    = $bonus->validated_at ?? optional($bonus->paiement)->created_at;
                    $cycle->validateur_nom  = optional($bonus->validator)->name ?? 'Admin #' . $bonus->validated_by;
                } else {
                    $cycle->statut_paiement = $bonus ? ucfirst($bonus->statut) : 'En attente';
                    $cycle->validated_at    = null;
                    $cycle->validateur_nom  = '---';
                }

                return $cycle;
            });

        // Récupération des bonus manuels
        $bonusManuels = Bonus::where('agent_id', $agent->id)
            ->manuels()
            ->whereMonth('date_attribution', $mois)
            ->whereYear('date_attribution', $annee)
            ->with(['admin', 'validator'])
            ->get();

        return view('admin.payrolls.preview', compact('salaire', 'carnets', 'cycles', 'bonusManuels', 'avancesList'));
    }

    public function updateTranches(Request $request, $id)
    {
        $avance = SalaryAdvance::findOrFail($id);

        // 1. Récupérer les numéros de tranches cochés (ex: ["1", "2", "3"])
        $tranchesSelectionnees = $request->input('tranches_ids', []);

        // Si aucune case n'est cochée, on supprime l'enregistrement s'il existait
        if (empty($tranchesSelectionnees)) {
            DeductionAvance::where('salary_advance_id', $avance->id)->delete();
            return redirect()->back()->with('success', 'Tranches mises à jour avec succès.');
        }

        // 2. Calculer le montant d'une seule tranche
        $montantParTranche = $avance->nombre_tranches > 0
            ? $avance->montant_total / $avance->nombre_tranches
            : $avance->montant_total;

        // 3. Compter combien de tranches sont cochées et calculer le montant total correspondant
        $nombreTranchesCochees = count($tranchesSelectionnees);
        $montantTotal          = $nombreTranchesCochees * $montantParTranche;

        // 4. Enregistrer ou mettre à jour UNE SEULE LIGNE globale pour cette avance
        DeductionAvance::updateOrCreate(
            [
                'salary_advance_id' => $avance->id, // Recherche par avance (1 seule ligne)
            ],
            [
                'ulid'            => strtolower((string) Str::ulid()),
                'agent_id'        => $avance->agent_id,
                'nombre_tranches' => $nombreTranchesCochees, // Ex: 3 si 3 cases cochées
                'montant'         => $montantTotal,          // Ex: 3 * montant_par_tranche
                'mois'            => $avance->mois ?? now()->month,
                'annee'           => $avance->annee ?? now()->year,
            ]
        );

        return redirect()->back()->with('success', 'Tranches de déduction mises à jour avec succès.');
    }
}
