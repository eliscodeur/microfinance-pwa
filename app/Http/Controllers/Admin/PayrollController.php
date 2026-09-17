<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Carnet;
use App\Models\Cycle;
use App\Models\Salaire;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            // On récupère les avances en cours de l'agent pour cette période
            $avancesEnCours = \App\Models\SalaryAdvance::where('agent_id', $agent->id)
                ->where('statut', 'en_cours')
                ->get();

            // On somme les tranches mensuelles à déduire ce mois-ci
            $totalAvanceDeduite = $avancesEnCours->sum('montant_mensuel');

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
        // $request->validate([
        //     'periode' => 'required|date_format:Y-m',
        // ]);

        // $periodeInput = $request->input('periode');
        // $dateDebut    = Carbon::createFromFormat('Y-m', $periodeInput)->startOfMonth();
        // $dateFin      = Carbon::createFromFormat('Y-m', $periodeInput)->endOfMonth();

        // $mois   = $dateDebut->month;
        // $annee  = $dateDebut->year;
        // $agents = Agent::all();

        // DB::transaction(function () use ($agents, $dateDebut, $dateFin, $mois, $annee) {
        //     foreach ($agents as $agent) {
        //         $salaireBase      = $agent->salaire_base ?? 0;
        //         $commissions      = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());
        //         $commissionCarnet = method_exists($this->payrollService, 'calculerCommissionCarnet')
        //             ? $this->payrollService->calculerCommissionCarnet($agent, $dateDebut->toDateString(), $dateFin->toDateString())
        //             : 0;
        //         $bonus = method_exists($this->payrollService, 'calculerBonus')
        //             ? $this->payrollService->calculerBonus($agent, $dateDebut->toDateString(), $dateFin->toDateString())
        //             : 0;

        //         $montantNet = $salaireBase + $commissions + $commissionCarnet + $bonus;

        //         // 1. Enregistrer ou mettre à jour le salaire net dans la table des salaires
        //         DB::table('salaires')->updateOrInsert(
        //             [
        //                 'agent_id' => $agent->id,
        //                 'mois'     => $mois,
        //                 'annee'    => $annee,
        //             ],
        //             [
        //                 'montant_net'   => $montantNet,
        //                 'periode_debut' => $dateDebut->toDateString(),
        //                 'periode_fin'   => $dateFin->toDateString(),
        //                 'statut'        => 'valide',
        //                 'updated_at'    => now(),
        //                 'created_at'    => now(),
        //             ]
        //         );

        //         // 2. Marquer les paiements de cet agent sur la période comme inclus
        //         DB::table('paiements')
        //             ->where('agent_id', $agent->id)
        //             ->whereNull('inclus_dans_salaire_at')
        //             ->whereBetween('created_at', [$dateDebut->copy()->startOfDay(), $dateFin->copy()->endOfDay()])
        //             ->update(['inclus_dans_salaire_at' => now()]);
        //     }
        // });

        // return redirect()->route('admin.payrolls.index', [
        //     'periode' => $periodeInput,
        // ])->with('success', 'Tous les salaires du mois ont été validés et verrouillés avec succès !');
    }
    public function details(Request $request, $agentUlid)
    {
        // Récupérer la période depuis la requête ou utiliser par défaut le mois en cours/précédent
        $mois  = $request->input('mois', now()->subMonth()->month);
        $annee = $request->input('annee', now()->subMonth()->year);

        $dateDebut = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin   = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        // Récupérer l'agent par son ULID
        $agent = Agent::where('ulid', $agentUlid)->firstOrFail();

        // 1. Calcul des commissions via le service (exactement comme dans l'index)
        $calculs = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

        $salaireBase       = $calculs['salaire_base'] ?? ($agent->salaire_base ?? 0);
        $commissionTravail = $calculs['commission_travail'] ?? 0;
        $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
        $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
        $bonus             = $calculs['montant_total_bonus'] ?? 0;
        $salaireNet        = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;

        // 2. Vérifier si un enregistrement officiel existe déjà en base
        $salaireEnregistré = DB::table('salaires')
            ->where('agent_id', $agent->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        // 3. Créer un objet virtuel (ou modèle non sauvegardé) pour alimenter la vue proprement
        $salaire = new Salaire([
            'reference'         => $salaireEnregistré->reference ?? 'PREV-' . $annee . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . $agent->id,
            'mois'              => $mois,
            'annee'             => $annee,
            'periode_debut'     => $dateDebut,
            'periode_fin'       => $dateFin,
            'salaire_base'      => $salaireBase,
            'commissions'       => $commissionTravail,
            'commission_carnet' => $commissionCarnet,
            'commission_cycle'  => $commissionCycle,
            'bonus'             => $bonus,
            'montant_net'       => $salaireNet,
            'statut'            => $salaireEnregistré ? ucfirst($salaireEnregistré->statut) : 'En attente',
            'created_at'        => $salaireEnregistré->created_at ?? null,
            'validated_at'      => $salaireEnregistré->validated_at ?? null,
        ]);

        // Attacher la relation agent pour la vue
        $salaire->setRelation('agent', $agent);

        // 4. Récupérer les carnets détaillés pour la période
        $carnets = Carnet::where('agent_id', $agent->id)
            ->with(['client', 'depots'])
            ->get()
            ->map(function ($carnet) use ($mois, $annee) {
                $montantCotiseMois = $carnet->depots()
                    ->whereMonth('created_at', $mois)
                    ->whereYear('created_at', $annee)
                    ->sum('montant');
                $carnet->montant_cotise_mois = $montantCotiseMois;
                $carnet->commission_generee  = $montantCotiseMois * 0.25;
                return $carnet;
            });

        // 5. Récupérer les cycles de la période (en utilisant la logique approuvée)
        $cycles = Cycle::where('agent_id', $agent->id)
            ->where(function ($query) use ($mois, $annee) {
                $query->where(function ($q) use ($mois, $annee) {
                    $q->whereMonth('date_debut', $mois)->whereYear('date_debut', $annee);
                })->orWhere(function ($q) use ($mois, $annee) {
                    $q->whereMonth('date_cloture_reelle', $mois)->whereYear('date_cloture_reelle', $annee);
                });
            })
            ->with(['carnet.client', 'collectes'])
            ->get();

        return view('admin.payroll.details', compact('salaire', 'carnets', 'cycles'));
    }

    public function previewDetails(Request $request)
    {
        $agentUlid = $request->input('agent');
        $mois      = $request->input('mois');
        $annee     = $request->input('annee');

        $agent = Agent::where('ulid', $agentUlid)->firstOrFail();

        // On peut chercher ou simuler les données pour la vue de détail
        $dateDebut = \Carbon\Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin   = \Carbon\Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        // Récupérer le calcul des carnets et cycles pour cet agent sur la période
        $calculs = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

        $salaireBase       = $calculs['salaire_base'] ?? 0;
        $commissionTravail = $calculs['commission_travail'] ?? 0;
        $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
        $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
        $bonus             = $calculs['montant_total_bonus'] ?? 0;
        $tauxCarnet        = $calculs['taux_carnet'] ?? 25; // Récupère le taux dynamique ou valeur par défaut

        // 1. Récupérer les avances sur salaire validées ou applicables du mois
        $avancesList = \App\Models\SalaryAdvance::where('agent_id', $agent->id)
            ->whereMonth('created_at', $mois)
            ->whereYear('created_at', $annee)
            ->get();

                                                                                 // Calcul du total des avances à déduire
        $totalAvances = $avancesList->where('statut', 'valide')->sum('montant'); // ou 'accordee' selon votre nomenclature

        // Salaire Brut / Total avant déductions
        $salaireBrut = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;

        // Salaire Net après déduction des avances
        $montantNet = $salaireBrut - $totalAvances;

        // Utilisation d'un objet stdClass pour contourner les restrictions Eloquent et injecter toutes les propriétés d'affichage
        $salaire                     = new \stdClass();
        $salaire->reference          = 'PREV-' . $annee . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . $agent->ulid;
        $salaire->mois               = $mois;
        $salaire->annee              = $annee;
        $salaire->periode_debut      = $dateDebut;
        $salaire->periode_fin        = $dateFin;
        $salaire->salaire_base       = $salaireBase;
        $salaire->commission_travail = $commissionTravail;
        $salaire->commission_carnet  = $commissionCarnet;
        $salaire->commission_cycle   = $commissionCycle;
        $salaire->bonus              = $bonus;
        $salaire->motif_bonus        = $calculs['motif_bonus'] ?? null;
        $salaire->total_avances      = $totalAvances; // Ajouté pour la vue
        $salaire->montant_net        = $montantNet;
        $salaire->taux_carnet        = $tauxCarnet;
        $salaire->statut             = 'En attente';
        $salaire->created_at         = null;
        $salaire->validator          = null;
        $salaire->validated_at       = null;

        // Attacher la relation agent pour la vue
        $salaire->agent = $agent;

        // 1. Récupérer les IDs des carnets de l'agent pour le mois/année sélectionné
        $carnetsIds = DB::table('carnet_agent_histories')
            ->where('agent_id', $agent->id)
            ->where(function ($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('assigned_at', [$dateDebut, $dateFin])
                    ->orWhereNull('unassigned_at')
                    ->orWhereBetween('unassigned_at', [$dateDebut, $dateFin]);
            })
            ->pluck('carnet_id')
            ->unique();

        // 2. Récupérer les données d'historique pour ces carnets et cet agent
        $carnetsData = DB::table('carnet_agent_histories')
            ->select('carnet_id', 'assigned_at')
            ->where('agent_id', $agent->id)
            ->whereIn('carnet_id', $carnetsIds)
            ->orderBy('assigned_at', 'asc')
            ->get()
            ->keyBy('carnet_id');

        // 3. Charger les carnets avec leurs relations et injecter la date
        $carnets = \App\Models\Carnet::with(['client', 'categoryTontine', 'depots', 'retraits'])
            ->whereIn('id', $carnetsIds)
            ->get()
            ->map(function ($carnet) use ($mois, $annee, $tauxCarnet, $carnetsData) {
                $prixCategory = $carnet->categoryTontine->prix ?? 0;

                // Récupération de la date d'assignation
                $carnet->assigned_at        = $carnetsData[$carnet->id]->assigned_at ?? null;
                $carnet->prix_category      = $prixCategory;
                $carnet->commission_generee = ($prixCategory * $tauxCarnet) / 100;

                return $carnet;
            });

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

        $bonusManuels = \App\Models\Bonus::where('agent_id', $agent->id)
            ->manuels()
            ->whereMonth('date_attribution', $mois)
            ->whereYear('date_attribution', $annee)
            ->with(['admin', 'validator'])
            ->get();

        // On transmet aussi la liste des avances à la vue si vous souhaitez afficher un tableau dédié
        return view('admin.payrolls.show', compact('salaire', 'carnets', 'cycles', 'bonusManuels', 'avancesList'));
    }
}
