<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeductionAvance;
use App\Models\EmployeAdministratif;
use App\Models\SalaireEmploye;
use App\Models\SalaryAdvance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollEmployerController extends Controller
{
    /**
     * Affiche la liste des bulletins de paie des employés administratifs (Prévisualisation ou Validé).
     */
    public function index(Request $request)
    {
        $mois  = $request->input('mois', now()->subMonth()->month);
        $annee = $request->input('annee', now()->subMonth()->year);

        // Sécurisation
        $mois  = ! empty($mois) ? $mois : now()->subMonth()->month;
        $annee = ! empty($annee) ? $annee : now()->subMonth()->year;

        // On charge les employés avec leur relation 'fonction' pour éviter les requêtes en cascade
        $employes = EmployeAdministratif::with('fonction')
            ->where('actif', 1)
            ->get();
        $payrolls = [];

        foreach ($employes as $employe) {
            // Vérifier si un enregistrement existe déjà dans la table 'salaires_employe' pour ce mois
            $salaireEnregistré = SalaireEmploye::with(['creator', 'validator', 'payer', 'rejecter'])
                ->where('employe_id', $employe->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->first();

            if ($salaireEnregistré) {
                // --- CAS 1 : BULLETIN DÉJÀ ENREGISTRÉ (DONNÉES FIGÉES) ---
                $salaireBase        = $salaireEnregistré->salaire_base;
                $primes             = $salaireEnregistré->primes_totales;
                $indemnites         = $salaireEnregistré->indemnites_totales;
                $salaireBrut        = $salaireEnregistré->salaire_brut;
                $totalAvanceDeduite = $salaireEnregistré->avances_prets;
                $autresRetenues     = $salaireEnregistré->autres_retenues;
                $salaireNet         = $salaireEnregistré->salaire_net;

                $avancesConcernes = SalaryAdvance::where('employe_administratif_id', $employe->id)->get();
            } else {
                // --- CAS 2 : PRÉVISUALISATION (CALCUL À LA VOLÉE) ---

                // Utilisation de la méthode du modèle pour récupérer le salaire (propre ou fonction)
                $salaireBase = $employe->getSalaireBase();

                $primes     = 0;
                $indemnites = 0;

                $salaireBrut = $salaireBase + $primes + $indemnites;

                // Récupérer les avances en cours pour cet employé
                $avancesEnCours = SalaryAdvance::where('employe_administratif_id', $employe->id)
                    ->where('statut', 'en_cours')
                    ->get();

                $totalAvanceDeduite = 0;

                // 1. On vérifie s'il y a des déductions spécifiques déjà enregistrées/modifiées (DeductionAvance) pour ce mois/année
                $idsAvances     = $avancesEnCours->pluck('id');
                $deductionsMois = DeductionAvance::whereIn('salary_advance_id', $idsAvances)
                    ->where('mois', $mois)
                    ->where('annee', $annee)
                    ->sum('montant');

                if ($deductionsMois > 0) {
                    // Si des tranches ont été explicitement enregistrées/cochées pour ce mois
                    $totalAvanceDeduite = $deductionsMois;
                } else {
                    // Sinon, on applique le calcul automatique par défaut (1 tranche par avance en cours)
                    foreach ($avancesEnCours as $avance) {
                        $montantTranche = $avance->montant_mensuel ?? 0;
                        if ($montantTranche > 0 && $salaireBrut >= ($totalAvanceDeduite + $montantTranche)) {
                            $totalAvanceDeduite += $montantTranche;
                        }
                    }
                }

                $autresRetenues = 0;
                $totalRetenues  = $totalAvanceDeduite + $autresRetenues;

                $salaireNet       = max(0, $salaireBrut - $totalRetenues);
                $avancesConcernes = $avancesEnCours;
            }

            $payrolls[] = (object) [
                'id'                 => $salaireEnregistré ? $salaireEnregistré->id : null,   // <-- Ajouté ici
                'ulid'               => $salaireEnregistré ? $salaireEnregistré->ulid : null, // <-- Ajouté ici
                'employe'            => $employe,
                'salaire_base'       => $salaireBase,
                'primes_totales'     => $primes,
                'indemnites_totales' => $indemnites,
                'salaire_brut'       => $salaireBrut,
                'salaire_id'         => $salaireEnregistré ? $salaireEnregistré->id : null,
                'salaire_ulid'       => $salaireEnregistré ? $salaireEnregistré->ulid : null,
                'depense_id'         => $salaireEnregistré ? $salaireEnregistré->depense_id : null,
                'avance_deduite'     => $totalAvanceDeduite,
                'avances_concernes'  => $avancesConcernes,
                'autres_retenues'    => $autresRetenues ?? 0,
                'salaire_net'        => $salaireNet,
                'statut'             => $salaireEnregistré ? strtolower($salaireEnregistré->statut) : 'en attente',
                'created_by_name'    => $salaireEnregistré?->creator?->name,
                'validated_by_name'  => $salaireEnregistré?->validator?->name,
                'paid_by_name'       => $salaireEnregistré?->payer?->name,
                'rejected_by_name'   => $salaireEnregistré?->rejecter?->name,
                'rejection_reason'   => $salaireEnregistré?->rejection_reason,
            ];
        }

        return view('admin.payrolls.employes.index', compact('payrolls', 'mois', 'annee'));
    }

    /**
     * Prépare les bulletins des employés sans créer d'écritures de paiement.
     */
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
                return redirect()->back()->with('error', 'Les bulletins ne peuvent être préparés qu’après la clôture du mois concerné.');
            }

            $employes = EmployeAdministratif::where('actif', 1)->get();

            if ($employes->isEmpty()) {
                return redirect()->back()->with('error', 'Aucun employé actif trouvé pour cette période.');
            }

            DB::transaction(function () use ($employes, $mois, $annee, $dateDebut, $dateFin) {
                $donneesSalaires = [];

                // 1. Première passe : Calculer les salaires et avances pour chaque employé
                foreach ($employes as $employe) {
                    $salaireExistant = SalaireEmploye::where('employe_id', $employe->id)
                        ->where('mois', $mois)
                        ->where('annee', $annee)
                        ->lockForUpdate()
                        ->first();

                    if ($salaireExistant && in_array(strtolower($salaireExistant->statut), ['valide', 'paye'], true)) {
                        $salaireBase = $salaireExistant->salaire_base;
                        $primes = $salaireExistant->primes_totales;
                        $indemnites = $salaireExistant->indemnites_totales;
                        $salaireBrut = $salaireExistant->salaire_brut;
                        $totalDeductionsAvances = $salaireExistant->avances_prets;
                        $autresRetenues = $salaireExistant->autres_retenues;
                        $salaireNet = $salaireExistant->salaire_net;
                    } elseif ($salaireExistant) {
                        $salaireBase = $salaireExistant->salaire_base;
                        $primes = $salaireExistant->primes_totales;
                        $indemnites = $salaireExistant->indemnites_totales;
                        $salaireBrut = $salaireBase + $primes + $indemnites;
                        $totalDeductionsAvances = $salaireExistant->avances_prets;
                        $autresRetenues = $salaireExistant->autres_retenues;
                        $salaireNet = max(0, $salaireBrut - $totalDeductionsAvances - $autresRetenues);
                    } else {
                        $salaireBase = $employe->getSalaireBase();
                        $primes = 0;
                        $indemnites = 0;
                        $salaireBrut = $salaireBase + $primes + $indemnites;
                        $autresRetenues = 0;
                        $totalDeductionsAvances = 0;

                        $avancesList = SalaryAdvance::where('employe_administratif_id', $employe->id)
                            ->where('statut', 'en_cours')
                            ->get();

                        foreach ($avancesList as $avance) {
                            $deductionExistante = DB::table('deductions_avances')
                                ->where('salary_advance_id', $avance->id)
                                ->where('mois', $mois)
                                ->where('annee', $annee)
                                ->first();
                            $montantTranche = $deductionExistante
                                ? (float) $deductionExistante->montant
                                : (float) ($avance->montant_mensuel ?? 0);

                            if ($montantTranche <= 0) {
                                continue;
                            }

                            if ($salaireBrut < ($totalDeductionsAvances + $montantTranche)) {
                                if ($deductionExistante) {
                                    throw ValidationException::withMessages([
                                        'avances' => 'Les tranches sélectionnées dépassent le salaire brut de l’employé. Ajustez les retenues avant validation.',
                                    ]);
                                }
                                continue;
                            }

                            if (! $deductionExistante) {
                                DB::table('deductions_avances')->insert([
                                    'ulid' => strtolower((string) Str::ulid()),
                                    'employe_administratif_id' => $employe->id,
                                    'salary_advance_id' => $avance->id,
                                    'montant' => $montantTranche,
                                    'nombre_tranches' => 1,
                                    'mois' => $mois,
                                    'annee' => $annee,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }

                            $totalDeductionsAvances += $montantTranche;
                        }

                        $salaireNet = max(0, $salaireBrut - $totalDeductionsAvances - $autresRetenues);
                    }

                    if ($salaireExistant && in_array(strtolower($salaireExistant->statut), ['brouillon', 'rejete'], true)) {
                        $deductionsDuMois = DB::table('deductions_avances')
                            ->where('employe_administratif_id', $employe->id)
                            ->where('mois', $mois)
                            ->where('annee', $annee)
                            ->get();

                        if ($deductionsDuMois->isEmpty() && $totalDeductionsAvances > 0) {
                            $resteADeduire = (float) $totalDeductionsAvances;
                            $avancesEnCours = SalaryAdvance::where('employe_administratif_id', $employe->id)
                                ->where('statut', 'en_cours')
                                ->orderBy('id')
                                ->get();

                            foreach ($avancesEnCours as $avance) {
                                $montantTranche = (float) $avance->montant_mensuel;
                                if ($montantTranche <= 0 || $resteADeduire < $montantTranche) {
                                    continue;
                                }

                                $tranchesDejaDeduites = (int) $avance->tranches_payees;
                                $tranchesRestantes = max(0, $avance->nombre_tranches - $tranchesDejaDeduites);
                                $tranchesCeMois = min($tranchesRestantes, (int) floor($resteADeduire / $montantTranche));

                                if ($tranchesCeMois === 0) {
                                    continue;
                                }

                                $montantCeMois = $tranchesCeMois * $montantTranche;
                                DB::table('deductions_avances')->insert([
                                    'ulid' => strtolower((string) Str::ulid()),
                                    'employe_administratif_id' => $employe->id,
                                    'salary_advance_id' => $avance->id,
                                    'montant' => $montantCeMois,
                                    'nombre_tranches' => $tranchesCeMois,
                                    'mois' => $mois,
                                    'annee' => $annee,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $resteADeduire -= $montantCeMois;
                            }

                            if ($resteADeduire > 0.01) {
                                throw ValidationException::withMessages([
                                    'avances' => 'Les retenues enregistrées dans le brouillon ne correspondent plus aux tranches d’avances disponibles. Vérifiez le bulletin avant validation.',
                                ]);
                            }

                            $deductionsDuMois = DB::table('deductions_avances')
                                ->where('employe_administratif_id', $employe->id)
                                ->where('mois', $mois)
                                ->where('annee', $annee)
                                ->get();
                        }

                        if (abs((float) $deductionsDuMois->sum('montant') - (float) $totalDeductionsAvances) > 0.01) {
                            throw ValidationException::withMessages([
                                'avances' => 'Le montant des tranches sélectionnées ne correspond pas au brouillon enregistré. Vérifiez le bulletin avant validation.',
                            ]);
                        }

                    }

                    if ($totalDeductionsAvances + $autresRetenues > $salaireBrut) {
                        throw ValidationException::withMessages([
                            'retenues' => 'Le total des retenues ne peut pas dépasser le salaire brut de l’employé.',
                        ]);
                    }

                    // Stocker temporairement pour la 2e passe
                    $donneesSalaires[] = [
                        'employe_id'   => $employe->id,
                        'salaire_base' => $salaireBase,
                        'primes' => $primes,
                        'indemnites' => $indemnites,
                        'salaire_brut' => $salaireBrut,
                        'avances'      => $totalDeductionsAvances,
                        'autres_retenues' => $autresRetenues,
                        'salaire_net'  => $salaireNet,
                    ];
                }

                // Les bulletins sont préparés individuellement; la dépense est créée lors de chaque paiement.
                foreach ($donneesSalaires as $data) {
                    $salaireExistant = SalaireEmploye::where('employe_id', $data['employe_id'])
                        ->where('mois', $mois)
                        ->where('annee', $annee)
                        ->first();

                    if ($salaireExistant && in_array(strtolower($salaireExistant->statut), ['valide', 'paye'], true)) {
                        continue;
                    }

                    SalaireEmploye::updateOrCreate(
                        [
                            'employe_id' => $data['employe_id'],
                            'mois'       => $mois,
                            'annee'      => $annee,
                        ],
                        [
                            'ulid'               => optional($salaireExistant)->ulid ?? strtolower((string) Str::ulid()),
                            'depense_id'         => null,
                            'salaire_base'       => $data['salaire_base'],
                            'primes_totales'     => $data['primes'],
                            'indemnites_totales' => $data['indemnites'],
                            'salaire_brut'       => $data['salaire_brut'],
                            'avances_prets'      => $data['avances'],
                            'autres_retenues'    => $data['autres_retenues'],
                            'total_retenues'     => $data['avances'] + $data['autres_retenues'],
                            'salaire_net'        => $data['salaire_net'],
                            'statut'             => 'brouillon',
                            'created_by'         => auth()->id(),
                            'validated_by'       => null,
                            'validated_at'       => null,
                            'paid_by'            => null,
                            'paid_at'            => null,
                            'rejected_by'        => null,
                            'rejected_at'        => null,
                            'rejection_reason'   => null,
                        ]
                    );
                }
            });

            return redirect()->route('admin.payrolls.employes.index', [
                'mois'  => $mois,
                'annee' => $annee,
            ])->with('success', 'Les bulletins des employés ont été préparés. Aucun paiement ni mouvement de caisse n’a été enregistré.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la préparation des salaires employés.', [
                'periode' => $request->input('periode'),
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'La préparation des salaires employés a échoué. Aucune modification partielle n’a été conservée.');
        }
    }

    public function showValidated(string $ulid)
    {

        $salaire = SalaireEmploye::with(['employe', 'validator', 'creator', 'payer', 'rejecter', 'depense'])
            ->where('ulid', $ulid)
            ->firstOrFail();

        return view('admin.payrolls.employes.show', compact('salaire'));
    }

    public function approve(string $ulid, \App\Services\PayrollWorkflowService $workflow)
    {
        $workflow->approveSalary('employee', $ulid);

        return back()->with('success', 'Le bulletin a été approuvé. Le paiement peut maintenant être effectué par la caisse.');
    }

    public function reject(Request $request, string $ulid, \App\Services\PayrollWorkflowService $workflow)
    {
        $data = $request->validate([
            'motif_rejet' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $workflow->rejectSalary('employee', $ulid, $data['motif_rejet'] ?? null);

        return back()->with('success', 'Le bulletin a été rejeté.');
    }

    public function pay(string $ulid, \App\Services\PayrollWorkflowService $workflow)
    {
        $workflow->paySalary('employee', $ulid);

        return back()->with('success', 'Le salaire a été payé et la dépense ainsi que le mouvement de caisse ont été enregistrés.');
    }

    public function previewDetails(Request $request, $employeUlid)
    {
        $mois  = $request->input('mois', now()->subMonth()->month);
        $annee = $request->input('annee', now()->subMonth()->year);

        // Récupérer l'employé via son ULID avec sa fonction
        $employe = EmployeAdministratif::with('fonction')->where('ulid', $employeUlid)->firstOrFail();

        // Vérifier si un enregistrement existe déjà dans la table 'salaires_employe' pour ce mois
        $salaireEnregistré = SalaireEmploye::with(['creator', 'validator', 'payer', 'rejecter'])
            ->where('employe_id', $employe->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        $isEditable = ! $salaireEnregistré || in_array(strtolower($salaireEnregistré->statut), ['brouillon', 'rejete'], true);

        if ($salaireEnregistré) {
            // Existing payrolls are snapshots; only drafts remain editable.
            $salaireBase        = $salaireEnregistré->salaire_base;
            $primes             = $salaireEnregistré->primes_totales;
            $indemnites         = $salaireEnregistré->indemnites_totales;
            $salaireBrut        = $salaireEnregistré->salaire_brut;
            $totalAvanceDeduite = $salaireEnregistré->avances_prets;
            $autresRetenues     = $salaireEnregistré->autres_retenues;
            $salaireNet         = $salaireEnregistré->salaire_net;

            // Récupérer les avances et formater leurs tranches (même en mode figé pour l'affichage visuel)
            $avancesList = SalaryAdvance::where('employe_administratif_id', $employe->id)->get();
            foreach ($avancesList as $avance) {
                $this->formatterTranchesAvance($avance, $mois, $annee);
            }
            $avancesConcernes = $avancesList;
        } else {
            // --- CAS 2 : PRÉVISUALISATION OU CALCUL À LA VOLÉE ---
            $salaireBase = $employe->getSalaireBase();

            $primes     = 0;
            $indemnites = 0;

            $salaireBrut = $salaireBase + $primes + $indemnites;

            // 1. Récupérer toutes les avances en cours et générer leurs tranches formatées
            $avancesList = SalaryAdvance::where('employe_administratif_id', $employe->id)
                ->where('statut', 'en_cours')
                ->get();

            foreach ($avancesList as $avance) {
                $this->formatterTranchesAvance($avance, $mois, $annee);
            }

            // 2. Vérifier s'il existe des déductions personnalisées enregistrées pour ce mois/année précis
            $deductionsPersonnalisees = DeductionAvance::where('employe_administratif_id', $employe->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->get();

            // 3. Calcul du total des avances à déduire
            if ($deductionsPersonnalisees->isNotEmpty()) {
                $totalAvanceDeduite = $deductionsPersonnalisees->sum('montant');
            } else {
                // Par défaut, on somme les montants des tranches marquées comme sélectionnées pour ce mois
                $totalAvanceDeduite = 0;
                foreach ($avancesList as $avance) {
                    $tranchesMois        = collect($avance->tranches_formatees)->where('selectionne', true)->where('deja_paye', false);
                    $sommeAvance         = $tranchesMois->count() * $avance->montant_mensuel;
                    $totalAvanceDeduite += $sommeAvance;
                }
            }

            $autresRetenues    = 0;
            $totalRetenues     = $totalAvanceDeduite + $autresRetenues;
            $salaireNet        = max(0, $salaireBrut - $totalRetenues);
            $avancesConcernes  = $avancesList;
        }

        // Objet structuré pour la vue de détail / preview
        $payrollDetails = (object) [
            'employe'            => $employe,
            'salaire_base'       => $salaireBase,
            'primes_totales'     => $primes,
            'indemnites_totales' => $indemnites,
            'salaire_brut'       => $salaireBrut,
            'salaire_id'         => $salaireEnregistré ? $salaireEnregistré->id : null,
            'salaire_ulid'       => $salaireEnregistré ? $salaireEnregistré->ulid : null,
            'depense_id'         => $salaireEnregistré ? $salaireEnregistré->depense_id : null,
            'avance_deduite'     => $totalAvanceDeduite,
            'avances_concernes'  => $avancesConcernes,
            'autres_retenues'    => $autresRetenues ?? 0,
            'salaire_net'        => $salaireNet,
            'statut'             => $salaireEnregistré ? ucfirst($salaireEnregistré->statut) : 'En attente',
            'created_at'         => $salaireEnregistré?->created_at,
            'creator'            => $salaireEnregistré?->creator,
            'validator'          => $salaireEnregistré?->validator,
            'validated_at'       => $salaireEnregistré?->validated_at,
            'payer'              => $salaireEnregistré?->payer,
            'paid_at'            => $salaireEnregistré?->paid_at,
            'rejecter'           => $salaireEnregistré?->rejecter,
            'rejected_at'        => $salaireEnregistré?->rejected_at,
            'rejection_reason'   => $salaireEnregistré?->rejection_reason,
        ];

        $avancesList = $avancesConcernes;

        return view('admin.payrolls.employes.preview', compact('payrollDetails', 'mois', 'annee', 'employe', 'avancesList', 'isEditable'));
    }

    /**
     * Méthode utilitaire privée pour factoriser le calcul des tranches d'une avance
     */
    private function formatterTranchesAvance($avance, $mois, $annee)
    {
        $montantMensuel = $avance->montant_mensuel;
        $nombreTranches = $avance->nombre_tranches ?? 1;
        $dateDepart     = \Carbon\Carbon::parse($avance->created_at ?? now());

        // A. Compter le CUMUL TOTAL des tranches déjà payées sur cette avance lors des bulletins PRÉCÉDENTS
        $tranchesPasseesCount = DeductionAvance::where('salary_advance_id', $avance->id)
            ->where(function ($q) use ($mois, $annee) {
                $q->where('annee', '<', $annee)
                    ->orWhere(function ($sub) use ($mois, $annee) {
                        $sub->where('annee', $annee)->where('mois', '<', $mois);
                    });
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('salaires_employe')
                    ->whereColumn('salaires_employe.employe_id', 'deductions_avances.employe_administratif_id')
                    ->whereColumn('salaires_employe.mois', 'deductions_avances.mois')
                    ->whereColumn('salaires_employe.annee', 'deductions_avances.annee')
                    ->whereIn('salaires_employe.statut', ['valide', 'paye']);
            })
            ->sum('nombre_tranches');

        // B. Vérifier s'il y a un enregistrement spécifique pour le MOIS EN COURS
        $deductionMoisEnCours = DeductionAvance::where('salary_advance_id', $avance->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        $nbTranchesMoisActuel = $deductionMoisEnCours
            ? $deductionMoisEnCours->nombre_tranches
            : ($tranchesPasseesCount < $nombreTranches ? 1 : 0);

        $tranches = [];

        for ($i = 1; $i <= $nombreTranches; $i++) {
            $moisTranche        = $dateDepart->copy()->addMonths($i - 1);
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

    public function updateTranches(Request $request, $id)
    {
        $avance = SalaryAdvance::findOrFail($id);

        $periode = $request->validate([
            'mois' => ['required', 'integer', 'between:1,12'],
            'annee' => ['required', 'integer', 'between:2000,2100'],
            'employe_administratif_id' => ['required', 'integer', 'exists:employe_administratifs,id'],
            'tranches_ids' => ['sometimes', 'array'],
            'tranches_ids.*' => ['integer', 'distinct', 'between:1,' . max(1, (int) $avance->nombre_tranches)],
        ]);
        $mois = $periode['mois'];
        $annee = $periode['annee'];
        $employeId = $periode['employe_administratif_id'];

        abort_unless((int) $avance->employe_administratif_id === (int) $employeId, 404);

        $salaireValide = SalaireEmploye::where('employe_id', $employeId)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->whereIn('statut', ['valide', 'paye'])
            ->exists();

        if ($salaireValide) {
            return back()->with('error', 'Les retenues d’une paie validée sont figées.');
        }

        $tranchesSelectionnees = $periode['tranches_ids'] ?? [];

        // Si aucune case n'est cochée, on supprime l'enregistrement de déduction
        if (empty($tranchesSelectionnees)) {
            DeductionAvance::where('salary_advance_id', $avance->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->delete();
        } else {
            // 3. Calculer le montant d'une seule tranche
            $montantParTranche = $avance->nombre_tranches > 0
                ? $avance->montant_total / $avance->nombre_tranches
                : $avance->montant_total;

            // 4. Compter et calculer le montant total
            $nombreTranchesCochees = count($tranchesSelectionnees);
            $montantTotal          = $nombreTranchesCochees * $montantParTranche;

            // 5. Enregistrer ou mettre à jour la déduction
            DeductionAvance::updateOrCreate(
                [
                    'salary_advance_id' => $avance->id,
                    'mois'              => $mois,
                    'annee'             => $annee,
                ],
                [
                    'ulid'                     => strtolower((string) Str::ulid()),
                    'employe_administratif_id' => $employeId,
                    'nombre_tranches'          => $nombreTranchesCochees,
                    'montant'                  => $montantTotal,
                ]
            );
        }

        // 6. METTRE À JOUR LE SALAIRE ASSOCIÉ S'IL EXISTE DANS salaires_employe
        $salaireEmploye = \App\Models\SalaireEmploye::where('employe_id', $employeId)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        if ($salaireEmploye) {
            // Recalculer le total des avances déduites pour ce mois/année pour cet employé
            $avancesEnCours = SalaryAdvance::where('employe_administratif_id', $employeId)
                ->where('statut', 'en_cours')
                ->get();

            $idsAvances         = $avancesEnCours->pluck('id');
            $totalAvanceDeduite = \App\Models\DeductionAvance::whereIn('salary_advance_id', $idsAvances)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->sum('montant');

            // Calculer le nouveau salaire net
            $salaireBrut       = $salaireEmploye->salaire_base + $salaireEmploye->primes_totales + $salaireEmploye->indemnites_totales;
            $totalRetenues     = $totalAvanceDeduite + $salaireEmploye->autres_retenues;
            $nouveauSalaireNet = max(0, $salaireBrut - $totalRetenues);

            // Mettre à jour la ligne dans salaires_employe
            $salaireEmploye->update([
                'avances_prets' => $totalAvanceDeduite,
                'total_retenues' => $totalRetenues,
                'salaire_net'   => $nouveauSalaireNet,
            ]);
        }

        return redirect()->back()->with('success', 'Tranches de déduction et salaire mis à jour avec succès.');
    }
    public function storeBrouillon(Request $request, $ulid)
    {
        // 1. Récupérer l'employé via son ULID au lieu de son ID auto-incrémenté
        $employe = EmployeAdministratif::where('ulid', $ulid)->firstOrFail();

        $data = $request->validate([
            'mois' => ['required', 'integer', 'between:1,12'],
            'annee' => ['required', 'integer', 'between:2000,2100'],
            'primes_totales' => ['nullable', 'numeric', 'min:0'],
            'indemnites_totales' => ['nullable', 'numeric', 'min:0'],
            'autres_retenues' => ['nullable', 'numeric', 'min:0'],
        ]);
        $mois = (int) $data['mois'];
        $annee = (int) $data['annee'];

        $salaireExistant = SalaireEmploye::where('employe_id', $employe->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        if ($salaireExistant && ! in_array(strtolower($salaireExistant->statut), ['brouillon', 'rejete'], true)) {
            return back()->with('error', 'Un bulletin validé ne peut pas être modifié.');
        }

        // 2. Récupérer les valeurs saisies ou modifiées dans le formulaire
        $primesTotales = (float) ($data['primes_totales'] ?? 0);
        $indemnites = (float) ($data['indemnites_totales'] ?? 0);
        $autresRetenues = (float) ($data['autres_retenues'] ?? 0);

        // 3. Récupérer le salaire de base de l'employé
        $salaireBase = $salaireExistant ? $salaireExistant->salaire_base : $employe->getSalaireBase();

        // 4. Calculer le salaire brut
        $salaireBrut = $salaireBase + $primesTotales + $indemnites;

        DB::transaction(function () use ($employe, $mois, $annee, $salaireExistant, $salaireBase, $primesTotales, $indemnites, $autresRetenues, $salaireBrut) {
            $deductionsMois = DeductionAvance::where('employe_administratif_id', $employe->id)
                ->where('mois', $mois)
                ->where('annee', $annee)
                ->get();
            $totalAvanceDeduite = (float) $deductionsMois->sum('montant');

            if ($deductionsMois->isEmpty()) {
                $avancesEnCours = SalaryAdvance::where('employe_administratif_id', $employe->id)
                    ->where('statut', 'en_cours')
                    ->orderBy('id')
                    ->get();

                foreach ($avancesEnCours as $avance) {
                    $tranchesRestantes = max(0, (int) $avance->nombre_tranches - (int) $avance->tranches_payees);
                    $montantTranche = (float) ($avance->montant_mensuel ?? 0);

                    if ($tranchesRestantes === 0 || $montantTranche <= 0 || $salaireBrut < $totalAvanceDeduite + $autresRetenues + $montantTranche) {
                        continue;
                    }

                    DeductionAvance::create([
                        'salary_advance_id' => $avance->id,
                        'employe_administratif_id' => $employe->id,
                        'nombre_tranches' => 1,
                        'montant' => $montantTranche,
                        'mois' => $mois,
                        'annee' => $annee,
                    ]);
                    $totalAvanceDeduite += $montantTranche;
                }
            }

            $totalRetenues = $totalAvanceDeduite + $autresRetenues;
            if ($totalRetenues > $salaireBrut) {
                throw ValidationException::withMessages([
                    'retenues' => 'Le total des retenues ne peut pas dépasser le salaire brut de l’employé.',
                ]);
            }

            SalaireEmploye::updateOrCreate(
                [
                    'employe_id' => $employe->id,
                    'mois' => $mois,
                    'annee' => $annee,
                ],
                [
                    'ulid' => $salaireExistant->ulid ?? strtolower((string) \Illuminate\Support\Str::ulid()),
                    'salaire_base' => $salaireBase,
                    'primes_totales' => $primesTotales,
                    'indemnites_totales' => $indemnites,
                    'salaire_brut' => $salaireBrut,
                    'avances_prets' => $totalAvanceDeduite,
                    'autres_retenues' => $autresRetenues,
                    'total_retenues' => $totalRetenues,
                    'salaire_net' => max(0, $salaireBrut - $totalRetenues),
                    'statut' => 'brouillon',
                    'created_by' => auth()->id(),
                    'validated_by' => null,
                    'validated_at' => null,
                    'paid_by' => null,
                    'paid_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]
            );
        });

        return redirect()->back()->with('success', 'Le bulletin a été enregistré en tant que brouillon avec succès.');
    }
}
