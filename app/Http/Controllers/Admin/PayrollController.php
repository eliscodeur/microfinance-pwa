<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Bonus;
use App\Models\Carnet;
use App\Models\Cycle;
use App\Models\DeductionAvance;
use App\Models\Depense;
use App\Models\MouvementCaisse;
use App\Models\Salaire;
use App\Models\SalaryAdvance;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        $mois  = $request->input('mois', now()->subMonth()->month);
        $annee = $request->input('annee', now()->subMonth()->year);

        // Sécurisation au cas où les valeurs seraient vides
        $mois  = ! empty($mois) ? $mois : now()->subMonth()->month;
        $annee = ! empty($annee) ? $annee : now()->subMonth()->year;

        // Déduction des dates de début et de fin du mois sélectionné
        $dateDebut = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin   = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        $agents   = Agent::where('actif', 1)->get();
        $payrolls = [];

        foreach ($agents as $agent) {
            // Vérifier si un enregistrement existe déjà dans la table 'salaires' pour ce mois
            $salaireEnregistré = DB::table('salaires')
                ->where('agent_id', $agent->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->first();

            if ($salaireEnregistré) {
                // --- CAS 1 : LE SALAIRE EST DÉJÀ ENREGISTRÉ (ON RÉCUPÈRE LES DONNÉES FIGÉES) ---
                $salaireBase        = $salaireEnregistré->salaire_base;
                $commissionTravail  = $salaireEnregistré->commission_travail;
                $commissionCarnet   = $salaireEnregistré->commission_carnet;
                $commissionCycle    = $salaireEnregistré->commission_cycle;
                $bonus              = $salaireEnregistré->bonus;
                $totalAvanceDeduite = $salaireEnregistré->total_avances;
                $salaireNet         = $salaireEnregistré->montant_net;

                // Récupérer les avances pour affichage
                $avancesConcernes = \App\Models\SalaryAdvance::where('agent_id', $agent->id)->get();
            } else {
                // --- CAS 2 : PRÉVISUALISATION DANS INDEX ---
                $calculs = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

                $salaireBase       = $calculs['salaire_base'] ?? ($agent->salaire_base ?? 0);
                $commissionTravail = $calculs['commission_travail'] ?? 0;
                $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
                $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
                $bonus             = $calculs['montant_total_bonus'] ?? 0;

                // Calcul du Salaire Brut Réel
                $salaireBrut = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;

                $avancesEnCours = \App\Models\SalaryAdvance::where('agent_id', $agent->id)
                    ->where('statut', 'en_cours')
                    ->get();

                $deductionsPersonnalisees = \App\Models\DeductionAvance::where('agent_id', $agent->id)
                    ->where('mois', $mois)
                    ->where('annee', $annee)
                    ->get();

                if ($deductionsPersonnalisees->isNotEmpty()) {
                    // Si des ajustements manuels ont été enregistrés
                    $totalAvanceDeduite = $deductionsPersonnalisees->sum('montant');
                } else {
                    // RÈGLE FIFO ET BRUT DISPONIBLE (TOUT OU RIEN PAR TRANCHE)
                    $totalAvanceDeduite = 0;

                    if ($salaireBrut > 0) {
                        // Trier par ancienneté (FIFO : la plus ancienne en premier)
                        $avancesTriees = $avancesEnCours->sortBy('created_at');

                        foreach ($avancesTriees as $avance) {
                            $montantTranche = $avance->montant_mensuel ?? 0;
                            $brutRestant    = $salaireBrut - $totalAvanceDeduite;

                            // Prélèvement uniquement si le brut restant couvre l'intégralité de la tranche
                            if ($montantTranche > 0 && $brutRestant >= $montantTranche) {
                                $totalAvanceDeduite += $montantTranche;
                            }
                        }
                    }
                }

                // Calcul du Salaire Net (Sécurisé avec plancher à 0)
                $salaireNet       = max(0, $salaireBrut - $totalAvanceDeduite);
                $avancesConcernes = $avancesEnCours;
            }

            $payrolls[] = (object) [
                'agent'              => $agent,
                'salaire_base'       => $salaireBase,
                'commission_travail' => $commissionTravail,
                'salaire_id'         => $salaireEnregistré ? $salaireEnregistré->id : null,
                'commission_carnet'  => $commissionCarnet,
                'commission_cycle'   => $commissionCycle,
                'bonus'              => $bonus,
                'avance_deduite'     => $totalAvanceDeduite,
                'avances_concernes'  => $avancesConcernes,
                'salaire_net'        => $salaireNet,
                'statut'             => $salaireEnregistré ? ucfirst($salaireEnregistré->statut) : 'En attente',
            ];
        }

        return view('admin.payrolls.index', compact('payrolls', 'mois', 'annee'));
    }
    public function showValidated(string $id)
    {

        $salaire = Salaire::with(['agent', 'validator', 'depense'])
            ->where('id', $id)
            ->firstOrFail();

        return view('admin.payrolls.show', compact('salaire'));
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

            $mois  = $dateDebut->month;
            $annee = $dateDebut->year;
            if ($dateDebut->gte(now()->startOfMonth())) {
                return redirect()->back()->with('error', 'La paie ne peut être validée qu’après la clôture du mois concerné.');
            }
            $agents = Agent::where('actif', 1)->get();

            // 1. Récupérer en amont la catégorie de charge liée aux salaires
            $categorieSalaire = DB::table('categories_charges')
                ->where('libelle', 'LIKE', '%salaire%')
                ->orWhere('code_analytique', 'LIKE', '66%')
                ->first();

            $categorieId = $categorieSalaire ? $categorieSalaire->id : null;

            DB::transaction(function () use ($agents, $dateDebut, $dateFin, $mois, $annee, $categorieId) {
                foreach ($agents as $agent) {
                    $salaireExistant = DB::table('salaires')
                        ->where('agent_id', $agent->id)
                        ->where('mois', $mois)
                        ->where('annee', $annee)
                        ->lockForUpdate()
                        ->first();

                    if ($salaireExistant && strtolower($salaireExistant->statut) !== 'brouillon') {
                        continue;
                    }

                    // 1. Récupération des composantes financières PayrollService
                    $payrollDetails = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());

                    $salaireBase             = $payrollDetails['salaire_base'] ?? ($agent->salaire_base ?? 0);
                    $commissionTravail       = $payrollDetails['commission_travail'] ?? 0;
                    $montantCommissionCarnet = $payrollDetails['montant_commission_carnet'] ?? 0;
                    $totalCommissionCycle    = $payrollDetails['montant_total_commission_cycle'] ?? 0;
                    $totalBonus              = $payrollDetails['montant_total_bonus'] ?? 0;
                    $tauxCarnet              = $payrollDetails['taux_carnet'] ?? 25;

                    // Calcul du brut ou total avant déductions d'avances
                    $montantBrut = $salaireBase + $commissionTravail + $montantCommissionCarnet + $totalCommissionCycle + $totalBonus;

                    // 2. GESTION DES AVANCES SUR SALAIRE POUR CET AGENT
                    $avancesList = DB::table('salary_advances')
                        ->where('agent_id', $agent->id)
                        ->where('statut', 'en_cours')
                        ->get();

                    $totalDeductionsAvances = 0;

                    foreach ($avancesList as $avance) {
                        $deductionExistante = DB::table('deductions_avances')
                            ->where('salary_advance_id', $avance->id)
                            ->where('mois', $mois)
                            ->where('annee', $annee)
                            ->first();
                        $montantDeduction = $deductionExistante
                            ? (float) $deductionExistante->montant
                            : (float) ($avance->montant_mensuel ?? 0);

                        if ($montantDeduction <= 0) {
                            continue;
                        }

                        if ($montantBrut < ($totalDeductionsAvances + $montantDeduction)) {
                            if ($deductionExistante) {
                                throw ValidationException::withMessages([
                                    'avances' => 'Les tranches sélectionnées dépassent le salaire brut de l’agent. Ajustez les retenues avant validation.',
                                ]);
                            }
                            continue;
                        }

                        if (! $deductionExistante) {
                            DB::table('deductions_avances')->insert([
                                'ulid'              => strtolower((string) \Illuminate\Support\Str::ulid()),
                                'agent_id'          => $agent->id,
                                'salary_advance_id' => $avance->id,
                                'montant'           => $montantDeduction,
                                'nombre_tranches'   => 1,
                                'mois'              => $mois,
                                'annee'             => $annee,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ]);
                        }

                        $totalDeductionsAvances += $montantDeduction;
                        $totalTranchesPayees    = DB::table('deductions_avances')
                            ->where('salary_advance_id', $avance->id)
                            ->sum('nombre_tranches');

                        $updateData = ['tranches_payees' => $totalTranchesPayees];
                        if ($totalTranchesPayees >= $avance->nombre_tranches) {
                            $updateData['statut'] = 'soldee';
                        }

                        DB::table('salary_advances')
                            ->where('id', $avance->id)
                            ->update($updateData);
                    }

                    // Le montant net final
                    $montantNet = max(0, $montantBrut - $totalDeductionsAvances);

                    // 3. GESTION DE LA DÉPENSE ASSOCIÉE (Comptabilité / Trésorerie)
                    $depenseId    = $salaireExistant ? $salaireExistant->depense_id : null;
                    $motifDepense = 'Paiement du salaire de ' . $agent->name . ' (Mois : ' . $mois . '/' . $annee . ')';

                    if ($montantNet > 0) {
                        if ($depenseId) {
                            // Mise à jour de la dépense existante
                            DB::table('depenses')->where('id', $depenseId)->update([
                                'categories_charge_id' => $categorieId,
                                'montant'              => $montantNet,
                                'motif'                => $motifDepense,
                                'updated_at'           => now(),
                            ]);

                            // Mise à jour du mouvement de caisse lié
                            MouvementCaisse::where('source_type', \App\Models\Depense::class)
                                ->where('source_id', $depenseId)
                                ->update([
                                    'montant'        => $montantNet,
                                    'libelle'        => 'Dépense : ' . $motifDepense . ' (Bénéficiaire: ' . $agent->name . ')',
                                    'date_mouvement' => now(),
                                ]);

                        } else {
                            // Création d'une nouvelle ligne de décaissement dans depenses
                            $depenseId = DB::table('depenses')->insertGetId([
                                'ulid'                 => strtolower((string) Str::ulid()),
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

                            // Création automatique du mouvement de caisse (SORTIE)
                            MouvementCaisse::create([
                                'ulid'           => strtolower((string) Str::ulid()),
                                'type_operation' => 'salaire agent',
                                'sens'           => 'sortie',
                                'montant'        => $montantNet,
                                'date_mouvement' => now(),
                                'mode_paiement'  => 'Virement Bancaire',
                                'reference'      => 'SAL-' . $annee . '-' . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . $agent->code_agent,
                                'libelle'        => 'Dépense : ' . $motifDepense . ' (Bénéficiaire: ' . $agent->name . ')',
                                'source_type'    => Depense::class,
                                'source_id'      => $depenseId,
                                'user_id'        => auth()->id(),
                            ]);
                        }
                    } else {
                        // Si le montant net est égal à 0, on supprime l'éventuelle dépense ET son mouvement de caisse
                        if ($depenseId) {
                            MouvementCaisse::where('source_type', Depense::class)
                                ->where('source_id', $depenseId)
                                ->delete();

                            DB::table('depenses')->where('id', $depenseId)->delete();
                            $depenseId = null;
                        }
                    }

                    // 4. Enregistrer ou mettre à jour le salaire net avec la liaison
                    Salaire::updateOrCreate(
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
                            'taux_carnet'        => $tauxCarnet,
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la validation des salaires agents.', [
                'periode'   => $request->input('periode'),
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'La validation des salaires agents a échoué. Aucune modification partielle n’a été conservée.');
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

        $salaireEnregistre = Salaire::with('validator')
            ->where('agent_id', $agent->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        if ($salaireEnregistre && strtolower($salaireEnregistre->statut) !== 'brouillon') {
            $calculs           = [];
            $salaireBase       = $salaireEnregistre->salaire_base;
            $commissionTravail = $salaireEnregistre->commission_travail;
            $commissionCarnet  = $salaireEnregistre->commission_carnet;
            $commissionCycle   = $salaireEnregistre->commission_cycle;
            $bonus             = $salaireEnregistre->bonus;
            $tauxCarnet        = $salaireEnregistre->taux_carnet ?? 25;
        } else {
            $calculs           = $this->payrollService->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());
            $salaireBase       = $calculs['salaire_base'] ?? 0;
            $commissionTravail = $calculs['commission_travail'] ?? 0;
            $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
            $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
            $bonus             = $calculs['montant_total_bonus'] ?? 0;
            $tauxCarnet        = $calculs['taux_carnet'] ?? 25;
        }

        // Calcul du Salaire Brut Réel
        $salaireBrut = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;

        $avancesList = \App\Models\SalaryAdvance::where('agent_id', $agent->id)
            ->where('statut', 'en_cours')
            ->get();

        // 2. Vérifier s'il existe des déductions personnalisées enregistrées pour ce mois/année précis
        $deductionsPersonnalisees = DeductionAvance::where('agent_id', $agent->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->get();

        // 3. Calcul du total des avances à déduire (Aligné sur l'index avec la règle FIFO & Brut)
        if ($salaireEnregistre && strtolower($salaireEnregistre->statut) !== 'brouillon') {
            $totalAvances            = $salaireEnregistre->total_avances;
            $idsAvancesSelectionnees = $deductionsPersonnalisees->pluck('salary_advance_id')->toArray();
        } elseif ($deductionsPersonnalisees->isNotEmpty()) {
            $totalAvances            = $deductionsPersonnalisees->sum('montant');
            $idsAvancesSelectionnees = $deductionsPersonnalisees->pluck('salary_advance_id')->toArray();
        } else {
            $totalAvances            = 0;
            $idsAvancesSelectionnees = [];

            if ($salaireBrut > 0) {
                // Trier par ancienneté (FIFO : la plus ancienne en premier)
                $avancesTriees = $avancesList->sortBy('created_at');

                foreach ($avancesTriees as $avance) {
                    $montantTranche = $avance->montant_mensuel ?? 0;
                    $brutRestant    = $salaireBrut - $totalAvances;

                    // Prélèvement uniquement si le brut restant couvre l'intégralité de la tranche
                    if ($montantTranche > 0 && $brutRestant >= $montantTranche) {
                        $totalAvances              += $montantTranche;
                        $idsAvancesSelectionnees[]  = $avance->id;
                    }
                }
            }
        }

        // Gestion de l'affichage des tranches dans la vue détaillée
        foreach ($avancesList as $avance) {
            $montantMensuel = $avance->montant_mensuel;
            $nombreTranches = $avance->nombre_tranches ?? 1;
            $dateDepart     = \Carbon\Carbon::parse($avance->created_at ?? now());

            $tranchesPasseesCount = DeductionAvance::where('salary_advance_id', $avance->id)
                ->where(function ($q) use ($mois, $annee) {
                    $q->where('annee', '<', $annee)
                        ->orWhere(function ($sub) use ($mois, $annee) {
                            $sub->where('annee', $annee)->where('mois', '<', $mois);
                        });
                })
                ->sum('nombre_tranches');

            $deductionMoisEnCours = DeductionAvance::where('salary_advance_id', $avance->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->first();

            // L'avance est cochée dans les détails si son ID fait partie des avances sélectionnées par la règle FIFO
            $estSelectionneeParFIFO = in_array($avance->id, $idsAvancesSelectionnees);

            $nbTranchesMoisActuel = $deductionMoisEnCours
                ? $deductionMoisEnCours->nombre_tranches
                : ($estSelectionneeParFIFO ? 1 : 0);

            $tranches = [];

            for ($i = 1; $i <= $nombreTranches; $i++) {
                $moisTranche = $dateDepart->copy()->addMonths($i - 1);

                $estDejaPaye        = ($i <= $tranchesPasseesCount);
                $estCocheMoisActuel = (! $estDejaPaye && $i <= ($tranchesPasseesCount + $nbTranchesMoisActuel));

                $tranches[] = [
                    'id'          => $i,
                    'numero'      => $i,
                    'mois'        => $moisTranche->translatedFormat('F Y'),
                    'montant'     => $montantMensuel,
                    'deja_paye'   => $estDejaPaye,
                    'selectionne' => $estDejaPaye || $estCocheMoisActuel,
                ];
            }

            $avance->tranches_formatees = $tranches;
        }

        // Salaire Net (Sécurisé avec plancher à 0)
        $montantNet = max(0, $salaireBrut - $totalAvances);

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
        $salaire->statut                    = $salaireEnregistre ? ucfirst($salaireEnregistre->statut) : 'En attente';
        $salaire->created_at                = $salaireEnregistre ? $salaireEnregistre->created_at : null;
        $salaire->validator                 = $salaireEnregistre ? $salaireEnregistre->validator : null;
        $salaire->validated_at              = $salaireEnregistre ? $salaireEnregistre->validated_at : null;
        $salaire->is_editable               = ! $salaireEnregistre || strtolower($salaireEnregistre->statut) === 'brouillon';
        $salaire->agent                     = $agent;

        // Récupération des carnets de l'agent (reste inchangé)
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
                $prixCategory               = $carnet->categoryTontine->prix ?? 0;
                $carnet->assigned_at        = $carnetsData[$carnet->id]->assigned_at ?? null;
                $carnet->prix_category      = $prixCategory;
                $carnet->commission_generee = ($prixCategory * $tauxCarnet) / 100;

                return $carnet;
            });

        // Récupération des cycles (reste inchangé)
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
                    $cycle->validated_at    = $null    = null;
                    $cycle->validateur_nom  = '---';
                }

                return $cycle;
            });

        // Récupération des bonus manuels (reste inchangé)
        $bonusManuels = Bonus::where('agent_id', $agent->id)
            ->manuels()
            ->whereMonth('date_attribution', $mois)
            ->whereYear('date_attribution', $annee)
            ->with(['admin', 'validator'])
            ->get();

        return view('admin.payrolls.preview', compact('salaire', 'carnets', 'cycles', 'bonusManuels', 'avancesList', 'mois', 'annee'));
    }

    // public function updateTranches(Request $request, $id)
    // {
    //     $avance    = SalaryAdvance::findOrFail($id);
    //     $validated = $request->validate([
    //         'mois'           => ['required', 'integer', 'between:1,12'],
    //         'annee'          => ['required', 'integer', 'between:2000,2100'],
    //         'tranches_ids'   => ['sometimes', 'array'],
    //         'tranches_ids.*' => ['integer', 'distinct', 'between:1,' . max(1, (int) $avance->nombre_tranches)],
    //     ]);
    //     $mois  = (int) $validated['mois'];
    //     $annee = (int) $validated['annee'];

    //     $salaireValide = Salaire::where('agent_id', $avance->agent_id)
    //         ->where('mois', $validated['mois'])
    //         ->where('annee', $validated['annee'])
    //         ->where('statut', '!=', 'brouillon')
    //         ->exists();

    //     if ($salaireValide) {
    //         return back()->with('error', 'Les retenues d’une paie validée sont figées.');
    //     }

    //     // 1. Récupérer les numéros de tranches cochés (ex: ["1", "2", "3"])
    //     $tranchesSelectionnees = $validated['tranches_ids'] ?? [];

    //     // Si aucune case n'est cochée, on supprime l'enregistrement s'il existait
    //     if (empty($tranchesSelectionnees)) {
    //         DeductionAvance::where('salary_advance_id', $avance->id)
    //             ->where('mois', $mois)
    //             ->where('annee', $annee)
    //             ->delete();
    //         return redirect()->back()->with('success', 'Tranches mises à jour avec succès.');
    //     }

    //     // 2. Calculer le montant d'une seule tranche
    //     $montantParTranche = $avance->nombre_tranches > 0
    //         ? $avance->montant_total / $avance->nombre_tranches
    //         : $avance->montant_total;

    //     // 3. Compter combien de tranches sont cochées et calculer le montant total correspondant
    //     $nombreTranchesCochees = count($tranchesSelectionnees);
    //     $montantTotal          = $nombreTranchesCochees * $montantParTranche;

    //     // 4. Enregistrer ou mettre à jour UNE SEULE LIGNE globale pour cette avance
    //     DeductionAvance::updateOrCreate(
    //         [
    //             'salary_advance_id' => $avance->id, // Recherche par avance (1 seule ligne)
    //             'mois'              => $mois,
    //             'annee'             => $annee,
    //         ],
    //         [
    //             'ulid'            => strtolower((string) Str::ulid()),
    //             'agent_id'        => $avance->agent_id,
    //             'nombre_tranches' => $nombreTranchesCochees, // Ex: 3 si 3 cases cochées
    //             'montant'         => $montantTotal,          // Ex: 3 * montant_par_tranche
    //         ]
    //     );

    //     return redirect()->back()->with('success', 'Tranches de déduction mises à jour avec succès.');
    // }

    public function updateTranches(Request $request, $id)
    {
        $avance    = SalaryAdvance::findOrFail($id);
        $validated = $request->validate([
            'mois'           => ['required', 'integer', 'between:1,12'],
            'annee'          => ['required', 'integer', 'between:2000,2100'],
            'tranches_ids'   => ['sometimes', 'array'],
            'tranches_ids.*' => ['integer', 'distinct', 'between:1,' . max(1, (int) $avance->nombre_tranches)],
        ]);

        $mois  = (int) $validated['mois'];
        $annee = (int) $validated['annee'];

        $salaireValide = Salaire::where('agent_id', $avance->agent_id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->where('statut', '!=', 'brouillon')
            ->exists();

        if ($salaireValide) {
            return back()->with('error', 'Les retenues d’une paie validée sont figées.');
        }

        // 1. Récupérer les numéros de tranches cochés
        $tranchesSelectionnees = $validated['tranches_ids'] ?? [];

        // Si aucune case n'est cochée, on supprime l'enregistrement s'il existait
        if (empty($tranchesSelectionnees)) {
            DeductionAvance::where('salary_advance_id', $avance->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->delete();
            return redirect()->back()->with('success', 'Tranches mises à jour avec succès.');
        }

        // 2. Calculer le montant d'une seule tranche
        $montantParTranche = $avance->nombre_tranches > 0
            ? $avance->montant_total / $avance->nombre_tranches
            : $avance->montant_total;

        // 3. Compter et calculer le montant total demandé pour cette avance
        $nombreTranchesCochees = count($tranchesSelectionnees);
        $montantTotal          = $nombreTranchesCochees * $montantParTranche;

        // --- 4. CONTRÔLE DE CAPACITÉ (VÉRIFICATION DU SALAIRE BRUT) ---
        $agent     = Agent::findOrFail($avance->agent_id);
        $dateDebut = \Carbon\Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin   = \Carbon\Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        // Récupérer le brut calculé pour cet agent sur ce mois
        $calculs           = app(\App\Services\PayrollService::class)->calculerCommissionGlobaleCarnets($agent, $dateDebut->toDateString(), $dateFin->toDateString());
        $salaireBase       = $calculs['salaire_base'] ?? ($agent->salaire_base ?? 0);
        $commissionTravail = $calculs['commission_travail'] ?? 0;
        $commissionCarnet  = $calculs['montant_commission_carnet'] ?? 0;
        $commissionCycle   = $calculs['montant_total_commission_cycle'] ?? 0;
        $bonus             = $calculs['montant_total_bonus'] ?? 0;

        $salaireBrut = $salaireBase + $commissionTravail + $commissionCycle + $bonus + $commissionCarnet;

        // Calculer les autres déductions déjà appliquées sur D'AUTRES avances pour ce même mois
        $autresDeductions = DeductionAvance::where('agent_id', $agent->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->where('salary_advance_id', '!=', $avance->id)
            ->sum('montant');

        // Le brut disponible pour cette avance spécifique
        $brutDisponible = $salaireBrut - $autresDeductions;

        if ($montantTotal > $brutDisponible) {
            return back()->with('error', "Impossible de valider ces tranches : le montant total demandé (" . number_format($montantTotal, 0, ',', ' ') . " FCFA) dépasse le salaire brut disponible (" . number_format(max(0, $brutDisponible), 0, ',', ' ') . " FCFA). Le salaire net ne peut pas être négatif.");
        }
        // -------------------------------------------------------------

        // 5. Enregistrer ou mettre à jour la ligne de déduction
        DeductionAvance::updateOrCreate(
            [
                'salary_advance_id' => $avance->id,
                'mois'              => $mois,
                'annee'             => $annee,
            ],
            [
                'ulid'            => strtolower((string) Str::ulid()),
                'agent_id'        => $avance->agent_id,
                'nombre_tranches' => $nombreTranchesCochees,
                'montant'         => $montantTotal,
            ]
        );

        return redirect()->back()->with('success', 'Tranches de déduction mises à jour avec succès.');
    }
}
