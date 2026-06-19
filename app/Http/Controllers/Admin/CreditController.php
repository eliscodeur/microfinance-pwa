<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\CreditProduct;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Retrait;
use App\Models\Cycle;
use App\Models\Collecte;
use App\Models\Depot;
use App\Services\CreditCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CreditController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Admin', 'no-cache']);
    }

    public function index(Request $request)
    {
        $credits = Credit::with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->appends($request->query());

        return Inertia::render('Credits/Index', [
            'credits' => $credits,
        ]);
    }

    public function create()
    {
        $clients = Client::with([
            'carnets' => function ($query) {
                $query->where('statut', 'actif')
                    ->whereDoesntHave('credits', function($q) {
                        $q->where('statut', 'active');
                    })
                    ->with([
                        'categoryTontine',
                        'cycles' => function($q) { $q->whereNull('retire_at')->with('collectes'); },
                        'depots',
                        'retraits',
                        'credits' 
                    ]);
            }
        ])
        ->orderBy('nom')
        ->orderBy('prenom')
        ->get()
        ->map(function ($client) {
            $client->carnets = $client->carnets->map(function ($carnet) {
                  return [
                    'id'                 => $carnet->id,
                    'numero'             => $carnet->numero,
                    'type'               => $carnet->type,
                    'category'           => $carnet->categoryTontine?->libelle,
                    'solde'              => ($carnet->type === 'compte') ? $carnet->solde_disponible : $carnet->activeCycleSavings(),
                    'solde_bloque'       => $carnet->credits->sum('montant_demande'),
                    'solde_tontine' => $carnet->solde_tontine_non_retire,
                    'mise'               => $carnet->cycles->first()?->montant_journalier ?? 0,
                    'total_pointages'    => $carnet->totalPointages(),
                    'required_pointages' => $carnet->categoryTontine?->minimumPointagesRequired() ?? 0,
                ];
            });
           
            return $client;
        });

        return Inertia::render('Credits/Create', [
            'clients' => $clients,
            'creditProducts' => CreditProduct::with('creditObjects')->get()
        ]);
    }

    public function store(Request $request)
    {
        $today = now()->toDateString();
        
        // 1. Validation stricte alignée sur ta nouvelle structure et le formulaire
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'credit_product_id' => 'required|exists:credit_products,id',
            'credit_object_id' => 'nullable|exists:credit_objects,id',
            'cycle_id' => 'nullable', // Clé optionnelle pour la trace du cycle tontine terrain
            'type_support' => 'required|string|in:compte,tontine',
            
            'carnet_id' => [
                Rule::requiredIf(in_array($request->input('type_support'), ['compte', 'tontine'])),
                'nullable',
                'exists:carnets,id',
            ],
            
            'montant_demande' => 'required|numeric|min:1000',
            'mode' => 'required|string|in:fixe,degressif',
            'periodicite' => 'required|string|in:journaliere,hebdomadaire,quinzaine,mensuelle',
            'nombre_echeances' => 'required|integer|min:1|max:60',
            'differe' => 'required|integer|min:0|max:12',
            'frais_dossier' => 'required|numeric|min:0',
            'taux' => 'required|numeric|min:0|max:100',
            'taux_manuel' => 'nullable|numeric|min:0|max:100', // Correction orthographique
            'date_debut' => "required|date|after_or_equal:{$today}",

            // Validation du bloc garant / caution solidaire (Table séparée)
            'guarantor_nom_prenom' => 'required|string|max:255',
            'guarantor_telephone' => 'required|string|max:50',
            'guarantor_profession' => 'nullable|string|max:255',
            'guarantor_adresse' => 'nullable|string|max:255',
            'guarantor_piece_identite' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096', // 4Mo max
            'guarantor_justificatif_revenu' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ], [
            'carnet_id.required' => 'Un carnet est obligatoire pour ce type de support.',
            'date_debut.after_or_equal' => 'La date de début doit être aujourd’hui ou ultérieure.',
            'taux.max' => 'Le taux ne peut pas dépasser 100%.',
            'taux_manuel.max' => 'Le taux manuel ne peut pas dépasser 100%.',
            'nombre_echeances.max' => 'Le nombre d’échéances ne peut pas dépasser 60.',
            'guarantor_nom_prenom.required' => 'Le nom et prénom du garant sont obligatoires.',
            'guarantor_telephone.required' => 'Le numéro de téléphone du garant est obligatoire.',
            'guarantor_piece_identite.required' => 'La pièce d’identité du garant est obligatoire.',
        ]);

        $guaranteeBase = 0;

        // 2. Vérification et règles métiers sur le carnet
        if ($request->filled('carnet_id')) {
            $carnet = Carnet::with([
                'categoryTontine',
                'cycles.collectes',
                'depots',
                'retraits',
                'credits' => function($q) {
                    $q->where('statut', 'active');
                }
            ])
            ->where('id', $request->carnet_id)
            ->where('client_id', $request->client_id)
            ->where('statut', 'actif')
            ->first();

            if (!$carnet) {
                return back()->withInput()->with('error', 'Le carnet sélectionné est invalide ou n’appartient pas au client.');
            }
            
            $carnetHasActiveCredit = Credit::where('carnet_id', $request->carnet_id)
                ->whereIn('statut', ['pending', 'approved', 'active', 'in_arrears'])
                ->exists();

            if ($carnetHasActiveCredit) {
                return back()->withInput()->with('error', 'Ce carnet a déjà un crédit en cours. Veuillez sélectionner un autre carnet.');
            }

            // Vérification de la cohérence du support choisi
            if ($request->type_support === 'compte' && $carnet->type !== 'compte') {
                return back()->withInput()->with('error', 'Le carnet sélectionné doit être un compte actif.');
            }

            if ($request->type_support === 'tontine' && $carnet->type !== 'tontine') {
                return back()->withInput()->with('error', 'Le carnet sélectionné doit être une tontine active.');
            }

            // Seuil recommandé de pointages pour les carnets de tontine
            if ($carnet->type === 'tontine') {
                $category = $carnet->categoryTontine;
                if ($category) {
                    $requiredPointages = $category->minimumPointagesRequired();
                    $currentPointages = $carnet->totalPointages();

                    if ($currentPointages < $requiredPointages) {
                        session()->flash('warning', "Le carnet ne respecte pas encore le seuil recommandé ({$currentPointages}/{$requiredPointages} pointages). L'admin peut tout de même enregistrer le crédit.");
                    }
                }
            }

            $guaranteeBase = $carnet->guaranteeBase();
            if ($guaranteeBase <= 0) {
                session()->flash('warning', "Aucune épargne disponible n'a été détectée sur le carnet et ses comptes liés. Le prêt s'appuie uniquement sur la capacité d'emprunt.");
            } elseif ($request->montant_demande > $guaranteeBase) {
                session()->flash('warning', 'Le montant demandé dépasse l\'assiette de garantie disponible (' . number_format($guaranteeBase, 0, ',', ' ') . ' FCFA). Le crédit peut toujours être enregistré, mais la garantie d\'épargne est limitée.');
            }
        }

        // 3. Vérification globale au niveau du client
        $clientHasActive = Credit::where('client_id', $request->client_id)
            ->whereIn('statut', ['pending', 'approved', 'active', 'in_arrears'])
            ->exists();

        if ($clientHasActive) {
            return back()->withInput()->with('error', 'Ce client a déjà un crédit actif ou en attente.');
        }

        // 4. Lancement de la transaction pour préserver l'intégrité des données
        DB::beginTransaction();
        try {
            // Préparation des données pour le calculateur d'échéances (inclut le différé)
            $data = $request->only([
                'client_id', 'carnet_id', 'montant_demande', 'mode', 
                'periodicite', 'nombre_echeances', 'taux', 'taux_manuel', 'date_debut', 'differe'
            ]);

            // Génération du tableau d'amortissement
            $schedule = CreditCalculator::buildSchedule($data);
            $interestTotal = CreditCalculator::totalInterest($schedule);
            $montantAccorde = $request->montant_demande;
            $dateFin = collect($schedule)->last()['date'] ?? $request->date_debut;
            $monthlyAmount = collect($schedule)->avg('total');
            $blockedAmount = (float) $guaranteeBase;

            // Étape A : Création de l'enregistrement de Crédit principal
            $credit = Credit::create([
                'credit_uid' => (string) Str::uuid(),
                'client_id' => $data['client_id'],
                'carnet_id' => $data['carnet_id'] ?? null,
                'cycle_id' => $request->cycle_id ?? null, // Suivi tontine
                'admin_id' => auth()->id(),
                'credit_product_id' => $request->credit_product_id,
                'credit_object_id' => $request->credit_object_id,
                'type_support' => $request->type_support,
                
                'montant_demande' => $data['montant_demande'],
                'montant_accorde' => $montantAccorde,
                'taux' => CreditCalculator::calculateRate($data['taux'], $data['taux_manuel']),
                'taux_manuel' => $data['taux_manuel'],
                'mode' => $data['mode'],
                'periodicite' => $data['periodicite'],
                'nombre_echeances' => $data['nombre_echeances'],
                'differe' => $request->differe,
                'frais_dossier' => $request->frais_dossier,
                
                'montant_echeance' => round($monthlyAmount, 0),
                'interet_total' => round($interestTotal, 0),
                'montant_rembourse' => 0,
                'blocked_amount' => $blockedAmount,
                'statut' => 'pending',
                'date_demande' => now()->toDateString(),
                'date_debut' => $data['date_debut'],
                'date_fin_prevue' => $dateFin,
                'metadata' => [
                    'preview' => true,
                    'guarantee_base' => $blockedAmount,
                ],
            ]);

            // Étape B : Upload des pièces justificatives et enregistrement du Garant
            $pathPiece = $request->file('guarantor_piece_identite')->store('guarantors/pieces', 'public');
            $pathRevenu = $request->hasFile('guarantor_justificatif_revenu') 
                ? $request->file('guarantor_justificatif_revenu')->store('guarantors/revenus', 'public') 
                : null;

            $credit->guarantor()->create([
                'nom_prenom' => $request->guarantor_nom_prenom,
                'telephone' => $request->guarantor_telephone,
                'profession' => $request->guarantor_profession,
                'adresse' => $request->guarantor_adresse,
                'piece_identite' => $pathPiece,
                'justificatif_revenu' => $pathRevenu,
            ]);

            // Étape C : Génération des lignes de l'échéancier (CreditPayment)
            foreach ($schedule as $item) {
                CreditPayment::create([
                    'credit_id' => $credit->id,
                    'echeance' => $item['numero'],
                    'due_date' => $item['date'],
                    'montant_principal' => round($item['principal'], 0),
                    'montant_interets' => round($item['interest'], 0),
                    'montant_total' => round($item['total'], 0),
                    'status' => 'pending',
                    'admin_id' => auth()->id(),
                ]);
            }

            DB::commit();
            return redirect()->route('admin.credits.index')->with('success', 'Demande de crédit enregistrée avec succès.');
            
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Credit store error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Impossible de créer la demande de crédit : ' . $e->getMessage());
        }
    }

    /**
     * Récupère les détails complets d'un carnet
     * Retourne différentes informations selon le type de carnet
     */
    public function getCarnetDetails(Carnet $carnet)
    {
        try {
            $carnet->load(['cycles.collectes', 'depots', 'retraits']);

            if ($carnet->type === 'tontine') {
                // === CARNET TONTINE ===
                $cycles = $carnet->cycles->map(function (Cycle $cycle) {
                    $totalCollectes = (float) $cycle->collectes->sum('montant');
                    $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
                    $commission = (float) ($cycle->montant_journalier ?? 0);
                    
                    $nombrePointages = (int) $cycle->collectes->sum('pointage');
                    
                    // Calcul du retard :
                    // En retard si : statut !== 'termine' ET (pointages < jours écoulés OU date actuelle dépasse date_fin_prevue)
                    $today = Carbon::today();
                    $daysElapsed = $cycle->date_debut ? $cycle->date_debut->diffInDays($today) : 0;
                    $isPastDueDate = $cycle->date_fin_prevue && $today->gt($cycle->date_fin_prevue);
                    
                    $enRetard = ($cycle->statut !== 'termine') && 
                               ($nombrePointages < $daysElapsed || $isPastDueDate);
                    
                    return [
                        'id'                  => $cycle->id,
                        'date_debut'          => $cycle->date_debut?->format('d/m/Y'),
                        'date_fin_prevue'     => $cycle->date_fin_prevue?->format('d/m/Y'),
                        'date_cloture_reelle' => $cycle->date_cloture_reelle?->format('d/m/Y'),
                        'mise'                => (int) $commission,
                        'statut'              => $cycle->statut,
                        'total_pointages'    => $nombrePointages,
                        'en_retard'           => $enRetard,
                        'total_collectes'     => (int) $totalCollectes,
                        'total_deja_retire'   => (int) $totalDejaRetire,
                    ];
                })->toArray();

                return response()->json([
                    'success' => true,
                    'type'    => 'tontine',
                    'cycles'  => $cycles,
                ]);

            } else {
                // === CARNET COMPTE ÉPARGNE ===
                $solde = (float) $carnet->solde_disponible;
                
                // Fusion et tri des dépôts et retraits (10 derniers mouvements)
                $movements = collect();
                
                // Ajouter les dépôts
                foreach ($carnet->depots as $depot) {
                    $movements->push([
                        'type_transaction' => 'Dépôt',
                        'montant'          => (int) $depot->montant,
                        'date'             => $depot->date_depot?->format('d/m/Y H:i'),
                        'date_ts'          => $depot->date_depot?->timestamp ?? 0,
                    ]);
                }
                
                // Ajouter les retraits
                foreach ($carnet->retraits as $retrait) {
                    $movements->push([
                        'type_transaction' => 'Retrait',
                        'montant'          => (int) $retrait->montant_net,
                        'date'             => $retrait->date_retrait?->format('d/m/Y H:i'),
                        'date_ts'          => $retrait->date_retrait?->timestamp ?? 0,
                    ]);
                }
                
                // Tri par date décroissante et limite aux 10 derniers
                $movements = $movements
                    ->sortByDesc('date_ts')
                    ->slice(0, 10)
                    ->values()
                    ->map(function ($item) {
                        unset($item['date_ts']);
                        return $item;
                    })
                    ->toArray();
                
                return response()->json([
                    'success'   => true,
                    'type'      => 'compte',
                    'solde'     => (int) $solde,
                    'historique' => $movements,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('getCarnetDetails Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Impossible de récupérer les détails du carnet.',
            ], 500);
        }
    }

    public function show(Credit $credit)
    {
        $credit->load(['client', 'carnet.parent', 'carnet.enfants']);

        $today = Carbon::today();
        $totalPenalty = 0;
        $latePaymentFound = false;
        $emergencyWithdrawals = [];

        if ($credit->statut === 'active') {
            $overduePayments = $credit->payments()
                ->whereIn('status', ['pending', 'partiel'])
                ->where('due_date', '<', $today)
                ->orderBy('echeance')
                ->get();

            foreach ($overduePayments as $payment) {
                $event = $this->applyEmergencyWithdrawal($credit, $payment);
                if ($event) {
                    $emergencyWithdrawals[] = $event;
                }
            }

            $credit->refresh();

            $hasRemainingOverdue = $credit->payments()
                ->whereIn('status', ['pending', 'partiel'])
                ->where('due_date', '<', $today)
                ->exists();

            if ($hasRemainingOverdue) {
                $credit->update(['statut' => 'in_arrears']);
            }
        }

        $payments = $credit->payments()
            ->orderBy('echeance')
            ->paginate(10)
            ->appends(request()->query());

        $payments->setCollection($payments->getCollection()->map(function (CreditPayment $payment) use ($today, &$totalPenalty, &$latePaymentFound, $credit) {
            $isLate = $payment->status !== 'paid' && $payment->due_date->lt($today);
            $automaticPenalty = 0;

            if ($isLate) {
                $daysLate = $payment->due_date->diffInDays($today);
                $automaticPenalty = CreditCalculator::calculatePenalty((float) $payment->montant_total, $daysLate);
                $latePaymentFound = true;
            }

            $displayPenalty = $payment->penalite > 0 ? (float) $payment->penalite : $automaticPenalty;
            $payment->computed_penalty = round($displayPenalty, 0); // Spécificité XAF
            $payment->display_status = $payment->status === 'paid'
                ? 'paid'
                : ($payment->status === 'partiel' ? 'partiel' : ($isLate ? 'late' : 'pending'));
            
            $payment->can_pay = !$credit->payments()
                ->where('echeance', '<', $payment->echeance)
                ->whereIn('status', ['pending', 'partiel'])
                ->exists();
                
            $totalPenalty += $displayPenalty;

            return $payment;
        }));

        if ($latePaymentFound && $credit->statut === 'active') {
            $credit->update(['statut' => 'in_arrears']);
        }

        $credit->penalty_amount = round($totalPenalty, 0);
        $credit->payments = $payments;
        $credit->emergency_withdrawal_summary = $emergencyWithdrawals;

        return Inertia::render('Credits/Show', [
            'credit' => $credit,
        ]);
    }

    protected function applyEmergencyWithdrawal(Credit $credit, CreditPayment $payment)
    {
        if ($payment->status === 'paid') {
            return null;
        }

        $amountDue = ((float) $payment->montant_total + (float) $payment->penalite) - (float) $payment->montant_paye;
        if ($amountDue <= 0) {
            return null;
        }

        $carnet = $credit->carnet;
        if (!$carnet) {
            return null;
        }

        $withdrawn = 0.0;
        
        DB::beginTransaction();
        try {
            foreach ($carnet->allLinkedCarnets() as $linkedCarnet) {
                $cycles = $linkedCarnet->cycles()
                    ->where('statut', 'termine')
                    ->whereNull('retire_at')
                    ->orderBy('completed_at')
                    ->lockForUpdate() // Sécurité verrous concurrents
                    ->get();

                foreach ($cycles as $cycle) {
                    $totalCollectes = (float) $cycle->collectes()->sum('montant');
                    $commission = (float) ($cycle->montant_journalier ?? 0);
                    $net = max(0, $totalCollectes - $commission);

                    if ($net <= 0) {
                        continue;
                    }

                    Retrait::create([
                        'cycle_id' => $cycle->id,
                        'client_id' => $cycle->client_id,
                        'carnet_id' => $cycle->carnet_id,
                        'admin_id' => auth()->id(),
                        'montant_total' => $totalCollectes,
                        'commission' => $commission,
                        'montant_net' => $net,
                        'date_retrait' => now(),
                        'note' => 'Prélèvement de secours automatique pour échéance en défaut',
                    ]);

                    $cycle->update(['retire_at' => now()]);
                    $withdrawn += $net;

                    if ($withdrawn >= $amountDue) {
                        break 2;
                    }
                }
            }

            if ($withdrawn <= 0) {
                DB::rollBack();
                return null;
            }

            $paidBefore = (float) $payment->montant_paye;
            $newPaid = min($paidBefore + $withdrawn, (float) $payment->montant_total + (float) $payment->penalite);
            $paidDiff = $newPaid - $paidBefore;

            $payment->update([
                'montant_paye' => round($newPaid, 0),
                'status' => $newPaid >= ((float) $payment->montant_total + (float) $payment->penalite) ? 'paid' : 'partiel',
                'date_paye' => $newPaid >= ((float) $payment->montant_total + (float) $payment->penalite) ? now() : null,
                'admin_id' => auth()->id(),
            ]);

            if ($paidDiff > 0) {
                $credit->increment('montant_rembourse', round($paidDiff, 0));
            }

            DB::commit();

            return [
                'payment_id' => $payment->id,
                'echeance' => $payment->echeance,
                'amount_withdrawn' => round($withdrawn, 0),
                'amount_applied' => round($paidDiff, 0),
                'note' => 'Prélèvement de secours automatique pour échéance en défaut',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Emergency withdrawal error: ' . $e->getMessage());
            return null;
        }
    }

    public function updatePayment(Request $request, Credit $credit, CreditPayment $payment)
    {
        if ($payment->credit_id !== $credit->id) {
            abort(404);
        }

        $request->validate([
            'penalite' => 'nullable|numeric|min:0',
            'montant_paye' => 'nullable|numeric|min:0',
        ], [
            'penalite.min' => 'La pénalité ne peut pas être négative.',
            'montant_paye.min' => 'Le montant payé ne peut pas être négatif.',
        ]);

        // Utilisation d'une transaction globale avec lock direct en écriture pour éviter le double clic au guichet
        return DB::transaction(function () use ($request, $credit, $payment) {
            
            // Verrouiller la ligne de paiement pour empêcher une modification parallèle
            $payment = CreditPayment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === 'paid') {
                return back()->with('error', 'Action annulée : cette échéance a déjà été encaissée ou soldée entre-temps.');
            }

            $updates = [];
            $successMessage = 'Échéance mise à jour.';

            // 1. Traitement des pénalités forcées manuellement
            if ($request->has('penalite')) {
                $updates['penalite'] = round($request->input('penalite'), 0);
            }

            // 2. Traitement d'un versement financier au guichet
            if ($request->filled('montant_paye')) {
                $amountPaye = round((float) $request->input('montant_paye'), 0);
                $currentPaid = (float) $payment->montant_paye;
                
                // Prendre la nouvelle pénalité soumise ou celle déjà présente en base
                $penalite = array_key_exists('penalite', $updates) ? $updates['penalite'] : (float) $payment->penalite;
                $totalDue = (float) $payment->montant_total + $penalite;
                $remainingDue = round($totalDue - $currentPaid, 0);

                // Contrôle strict de l'ordre d'amortissement
                $previousUnpaidExists = $credit->payments()
                    ->where('echeance', '<', $payment->echeance)
                    ->whereIn('status', ['pending', 'partiel'])
                    ->exists();

                if ($previousUnpaidExists) {
                    return back()->with('error', 'Opération impossible : des échéances antérieures ne sont pas encore soldées.');
                }

                if ($amountPaye <= 0) {
                    return back()->with('error', 'Le montant à encaisser doit être supérieur à zéro.');
                }

                if ($amountPaye > $remainingDue) {
                    return back()->with('error', 'Le montant saisi excède le reste exigible de cette échéance.');
                }

                $newPaid = round($currentPaid + $amountPaye, 0);
                $updates['montant_paye'] = $newPaid;

                if ($newPaid >= $totalDue) {
                    $updates['status'] = 'paid';
                    $updates['date_paye'] = now();
                    $successMessage = "Encaissement de " . number_format($amountPaye, 0, ',', ' ') . " FCFA effectué. Échéance entièrement réglée.";
                } else {
                    $updates['status'] = 'partiel';
                    $updates['date_paye'] = null;
                    $remaining = number_format($totalDue - $newPaid, 0, ',', ' ');
                    $successMessage = "Encaissement partiel de " . number_format($amountPaye, 0, ',', ' ') . " FCFA enregistré. Reste à payer : {$remaining} FCFA.";
                }

                // Ajuster le cumulatif global remboursé sur la fiche de crédit principale
                $credit->increment('montant_rembourse', $amountPaye);
            }

            $updates['admin_id'] = auth()->id();
            $payment->update($updates);

            // 3. Vérification de clôture finale du dossier crédit
            $creditIsSettled = !$credit->payments()->where('status', '!=', 'paid')->exists();

            if ($creditIsSettled) {
                $credit->update([
                    'statut' => 'solder',
                    'blocked_amount' => 0,
                ]);
                $successMessage .= ' Le dossier de crédit est désormais entièrement soldé.';
            }

            return back()->with('success', $successMessage);
        });
    }

    public function approve(Request $request, Credit $credit)
    {
        if ($credit->statut !== 'pending') {
            return back()->with('error', 'Ce crédit ne peut pas être approuvé.');
        }

        $credit->update([
            'statut' => 'active',
            'admin_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.credits.show', $credit)->with('success', 'Crédit approuvé et activé avec succès.');
    }

    public function settleCreditWithTontine(Credit $credit)
    {
        $result = \App\Services\CreditSettlementService::settleCreditWithAvailableFunds($credit);

        $messageType = $result['success'] ? 'success' : 'warning';
        $message = $result['message'];

        if (!empty($result['cycles_used'])) {
            $message .= ' - Fonds utilisés : ' . number_format($result['amount_used'], 0, ',', ' ') . ' FCFA de ' . count($result['cycles_used']) . ' cycle(s).';
        }

        return redirect()->route('admin.credits.show', $credit)->with($messageType, $message);
    }
}