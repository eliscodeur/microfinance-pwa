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
use Illuminate\Support\Str;

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
        $employes = EmployeAdministratif::with('fonction')->get();
        $payrolls = [];

        foreach ($employes as $employe) {
            // Vérifier si un enregistrement existe déjà dans la table 'salaires_employe' pour ce mois
            $salaireEnregistré = SalaireEmploye::where('employe_id', $employe->id)
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

                $avancesConcernes = SalaryAdvance::where('employe_id', $employe->id)->get();
            } else {
                // --- CAS 2 : PRÉVISUALISATION (CALCUL À LA VOLÉE) ---

                // Utilisation de la méthode du modèle pour récupérer le salaire (propre ou fonction)
                $salaireBase = $employe->getSalaireBase();

                $primes     = 0;
                $indemnites = 0;

                $salaireBrut = $salaireBase + $primes + $indemnites;

                // Récupérer les avances en cours (1 tranche)
                $avancesEnCours = SalaryAdvance::where('employe_administratif_id', $employe->id)
                    ->where('statut', 'en_cours')
                    ->get();

                $totalAvanceDeduite = 0;
                foreach ($avancesEnCours as $avance) {
                    $montantTranche = $avance->montant_mensuel ?? 0;
                    if ($montantTranche > 0 && $salaireBrut >= ($totalAvanceDeduite + $montantTranche)) {
                        $totalAvanceDeduite += $montantTranche;
                    }
                }

                $autresRetenues  = 0;
                $totalRetenues   = $totalAvanceDeduite + $autresRetenues;

                $salaireNet       = max(0, $salaireBrut - $totalRetenues);
                $avancesConcernes = $avancesEnCours;
            }

            $payrolls[] = (object) [
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
            ];
        }

        return view('admin.payrolls.employes.index', compact('payrolls', 'mois', 'annee'));
    }

    /**
     * Valide, enregistre les bulletins, gère les avances et comptabilise la dépense pour tous les employés.
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

            $mois     = $dateDebut->month;
            $annee    = $dateDebut->year;
            $employes = EmployeAdministratif::all();

            // 1. Récupérer en amont la catégorie de charge liée aux salaires
            $categorieSalaire = DB::table('categories_charges')
                ->where('libelle', 'LIKE', '%salaire%')
                ->orWhere('code_analytique', 'LIKE', '66%')
                ->first();

            $categorieId = $categorieSalaire ? $categorieSalaire->id : null;

            DB::transaction(function () use ($employes, $dateDebut, $dateFin, $mois, $annee, $categorieId) {
                foreach ($employes as $employe) {
                    // 2. Calcul du brut
                    $salaireBase = $employe->salaire_base ?? 0;
                    $primes      = 0;
                    $indemnites  = 0;

                    $salaireBrut = $salaireBase + $primes + $indemnites;

                    // 3. GESTION DES AVANCES SUR SALAIRE POUR CET EMPLOYÉ
                    $avancesList = SalaryAdvance::where('employe_id', $employe->id)
                        ->where('statut', 'en_cours')
                        ->get();

                    $totalDeductionsAvances = 0;

                    foreach ($avancesList as $avance) {
                        $montantTranche = $avance->montant_mensuel ?? 0;

                        if ($montantTranche > 0 && $salaireBrut >= ($totalDeductionsAvances + $montantTranche)) {$deductionExistante = DB::table('deductions_avances')
                                ->where('salary_advance_id', $avance->id)
                                ->where('mois', $mois)
                                ->where('annee', $annee)
                                ->first();

                            if (! $deductionExistante) {
                                DB::table('deductions_avances')->insert([
                                    'ulid'              => (string) Str::ulid(),
                                    'employe_id'        => $employe->id,
                                    'salary_advance_id' => $avance->id,
                                    'montant'           => $montantTranche,
                                    'nombre_tranches'   => 1,
                                    'mois'              => $mois,
                                    'annee'             => $annee,
                                    'created_at'        => now(),
                                    'updated_at'        => now(),
                                ]);
                            }

                            $montantCeMois = DB::table('deductions_avances')
                                ->where('salary_advance_id', $avance->id)
                                ->where('mois', $mois)
                                ->where('annee', $annee)
                                ->sum('montant');

                            $totalDeductionsAvances += $montantCeMois;

                            // Recalculer le total des tranches payées
                            $totalTranchesPayees = DB::table('deductions_avances')
                                ->where('salary_advance_id', $avance->id)
                                ->sum('nombre_tranches');

                            $updateData = ['tranches_payees' => $totalTranchesPayees];
                            if ($totalTranchesPayees >= $avance->nombre_tranches) {$updateData['statut'] = 'soldee';}

                            SalaryAdvance::where('id', $avance->id)->update($updateData);}
                    }

                    // Le montant net final
                    $salaireNet = max(0, $salaireBrut - $totalDeductionsAvances); $autresRetenues = 0;

                    // 4. GESTION DE LA DÉPENSE ASSOCIÉE (Comptabilité / Trésorerie)
                    $salaireExistant = SalaireEmploye::where('employe_id', $employe->id)
                        ->where('mois', $mois)
                        ->where('annee', $annee)
                        ->first();

                    $depenseId    = $salaireExistant ? $salaireExistant->depense_id : null;
                    $motifDepense = 'Paiement du salaire administratif (Mois : ' . $mois . '/' . $annee . ')';

                    if ($salaireNet > 0) {
                        if ($depenseId) {
                            // Mise à jour de la dépense existante
                            DB::table('depenses')->where('id', $depenseId)->update([
                                'categories_charge_id' => $categorieId,
                                'montant'              => $salaireNet,
                                'motif'                => $motifDepense,
                                'updated_at'           => now(),
                            ]);
                        } else {
                            // Création d'une nouvelle ligne de décaissement
                            $depenseId = DB::table('depenses')->insertGetId([
                                'ulid'                 => (string) Str::ulid(),
                                'categories_charge_id' => $categorieId,
                                'montant'              => $salaireNet,
                                'date_depense'         => now(),
                                'mode_paiement'        => 'Virement Bancaire',
                                'reference_piece'      => 'SAL-EMP-' . $annee . '-' . str_pad($mois, 2, '0', STR_PAD_LEFT) . '-' . $employe->id,
                                'motif'                => $motifDepense,
                                'user_id'              => auth()->id(),
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ]);
                        }
                    } else {
                        // Si le net est à 0, suppression de la dépense éventuelle
                        if ($depenseId) {
                            DB::table('depenses')->where('id', $depenseId)->delete(); $depenseId = null;
                        }
                    }

                    // 5. Enregistrer ou mettre à jour le bulletin dans la table `salaires_employe`
                    SalaireEmploye::updateOrCreate(
                        [
                            'employe_id' => $employe->id,
                            'mois'       => $mois,
                            'annee'      => $annee,
                        ],
                        [
                            'ulid'               => $salaireExistant->ulid ?? (string) Str::ulid(),
                            'depense_id'         => $depenseId,
                            'salaire_base'       => $salaireBase,
                            'primes_totales'     => $primes,
                            'indemnites_totales' => $indemnites,
                            'salaire_brut'       => $salaireBrut,
                            'avances_prets'      => $totalDeductionsAvances,
                            'autres_retenues'    => $autresRetenues,
                            'salaire_net'        => $salaireNet,
                            'statut'             => 'valide',
                            'validated_by'       => auth()->id(),
                            'validated_at'       => now(),
                        ]
                    );
                }
            });

            return redirect()->route('admin.payrolls.employes.index', [
                'mois'  => $mois,
                'annee' => $annee,
            ])->with('success', 'Les salaires des employés administratifs ont été validés et comptabilisés avec succès !');

        } catch (\Exception $e) {
            dd("ERREUR CATCHÉE : " . $e->getMessage(), $e->getTraceAsString());
        }
    }

    public function previewDetails(Request $request, $employeUlid)
    {
        $mois  = $request->input('mois', now()->subMonth()->month);
        $annee = $request->input('annee', now()->subMonth()->year);

        // Récupérer l'employé via son ULID avec sa fonction
        $employe = EmployeAdministratif::with('fonction')->where('ulid', $employeUlid)->firstOrFail();

        // Vérifier si un enregistrement existe déjà dans la table 'salaires_employe' pour ce mois
        $salaireEnregistré = SalaireEmploye::where('employe_id', $employe->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->first();

        // Condition : Si le salaire existe ET qu'il est au statut "brouillon" (ou enregistré)
        if ($salaireEnregistré && strtolower($salaireEnregistré->statut) === 'brouillon') {
            // --- CAS 1 : BULLETIN ENREGISTRÉ EN STATUT BROUILLON (DONNÉES FIGÉES) ---
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
        ];

        // On passe $avancesList (ou $avancesConcernes) à la vue pour alimenter les modales de tranches
        $avancesList = $avancesConcernes;

        return view('admin.payrolls.employes.preview', compact('payrollDetails', 'mois', 'annee', 'employe', 'avancesList'));
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
}
