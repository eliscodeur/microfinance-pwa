<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditGuarantor;
use App\Models\CreditPayment;
use App\Models\CreditProduct;
use App\Models\Cycle;
use App\Models\PaymentTransaction;
use App\Models\Retrait;
use App\Services\CreditCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                    ->whereDoesntHave('credits', function ($q) {
                        $q->where('statut', 'active');
                    })
                                                  // --- AJOUT DU FILTRAGE TONTINE ---
                    ->when('tontine', function ($q) { // Si c'est une tontine
                        $q->where(function ($sub) {
                            $sub->where('type', '!=', 'tontine') // Garde les comptes normaux
                                ->orWhereHas('cycles', function ($c) {
                                    $c->where('statut', 'en_cours')
                                        ->orWhere(function ($cc) {
                                            $cc->where('statut', 'termine')->whereNull('retire_at');
                                        });
                                });
                        });
                    })
                // --------------------------------
                    ->with([
                        'categoryTontine',
                        'cycles' => function ($q) {$q->whereNull('retire_at')->with('collectes');},
                        'depots',
                        'retraits',
                        'credits',
                    ]);
            },
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
                        'category'           => $carnet->categoryTontine->libelle,
                        'solde'              => ($carnet->type === 'compte') ? $carnet->solde_disponible : $carnet->activeCycleSavings(),
                        'solde_bloque'       => $carnet->credits->sum('montant_demande'),
                        'solde_tontine'      => $carnet->solde_tontine_non_retire,
                        'mise'               => $carnet->cycles->first()->montant_journalier ?? 0,
                        'total_pointages'    => $carnet->totalPointages(),
                        'required_pointages' => $carnet->categoryTontine->minimumPointagesRequired() ?? 0,
                    ];
                });

                return $client;
            });

        return Inertia::render('Credits/Create', [
            'clients'        => $clients,
            'creditProducts' => CreditProduct::with('creditObjects')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $today = now()->toDateString();

        // 0. Anticipation : On cherche s'il existe déjà un brouillon "pending" pour ce carnet
        $existingPendingCredit = null;
        if ($request->filled('carnet_id')) {
            $existingPendingCredit = Credit::where('carnet_id', $request->carnet_id)
                ->where('statut', 'pending')
                ->first();
        }

        // 1. Validation stricte alignée sur ta nouvelle structure et le formulaire
        $request->validate([
            'client_id'         => 'required|exists:clients,id',
            'credit_product_id' => 'required|exists:credit_products,id',
            'credit_object_id'  => 'nullable|exists:credit_objects,id',
            'cycle_id'          => 'nullable',
            'type_support'      => 'required|string|in:compte,tontine',

            'carnet_id'         => [
                Rule::requiredIf(in_array($request->input('type_support'), ['compte', 'tontine'])),
                'nullable',
                'exists:carnets,id',
            ],

            'montant_demande'   => 'required|numeric|min:1000',
            'mode'              => 'required|string|in:fixe,degressif',
            'periodicite'       => 'required|string|in:journaliere,hebdomadaire,quinzaine,mensuelle',
            'nombre_echeances'  => 'required|integer|min:1|max:60',
            'differe'           => 'required|integer|min:0|max:12',
            'frais_dossier'     => 'required|numeric|min:0',
            'taux'              => 'required|numeric|min:0|max:100',
            'taux_manuel'       => 'nullable|numeric|min:0|max:100',
            'date_debut'        => "required|date|after_or_equal:{$today}",

            // Validation du bloc garant
            'nom_prenom'          => 'required|string|max:255',
            'telephone'           => 'required|string|max:50',
            'profession'          => 'nullable|string|max:255',
            'adresse'             => 'nullable|string|max:255',
            // La pièce est requise UNIQUEMENT si c'est une création (pas de brouillon)
            'piece_identite'      => [
                $existingPendingCredit ? 'nullable' : 'required',
                'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096',
            ],
            'justificatif_revenu' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ], [
            'carnet_id.required'        => 'Un carnet est obligatoire pour ce type de support.',
            'date_debut.after_or_equal' => 'La date de début doit être aujourd’hui ou ultérieure.',
            'piece_identite.required'   => 'La pièce d’identité du garant est obligatoire.',
        ]);

        $guaranteeBase = 0;

        // 2. Vérification et règles métiers sur le carnet
        if ($request->filled('carnet_id')) {
            $carnet = Carnet::with([
                'categoryTontine',
                'cycles.collectes',
                'depots',
                'retraits',
                'credits' => function ($q) {
                    $q->where('statut', 'active');
                },
            ])
                ->where('id', $request->carnet_id)
                ->where('client_id', $request->client_id)
                ->where('statut', 'actif')
                ->first();

            if (! $carnet) {
                throw ValidationException::withMessages(['carnet_id' => 'Le carnet sélectionné est invalide.']);
            }

            // On vérifie s'il y a un crédit ACTIF ou APPROUVÉ (On exclut 'pending' de cette vérification)
            $carnetHasActiveCredit = Credit::where('carnet_id', $request->carnet_id)
                ->whereIn('statut', ['approved', 'active', 'in_arrears'])
                ->exists();

            // 2.1 Vérification spécifique pour la Tontine (Cycle actif)
            if ($request->type_support === 'tontine') {
                $hasValidCycle = Cycle::where('carnet_id', $request->carnet_id)
                    ->where(function ($query) {
                        $query->where('statut', 'en_cours') // Cycle actif
                            ->orWhere(function ($q) {
                                $q->where('statut', 'termine')->whereNull('retire_at');
                            });
                    })
                    ->exists();

                if (! $hasValidCycle) {
                    throw ValidationException::withMessages(['type_support' => 'Pour un crédit tontine, un cycle en cours ou non retiré est requis.']);
                }
            }

            if ($carnetHasActiveCredit) {
                throw ValidationException::withMessages(['carnet_id' => 'Ce carnet a déjà un crédit en cours. Veuillez sélectionner un autre carnet.']);
            }

            // Vérification de la cohérence du support
            if ($request->type_support === 'compte' && $carnet->type !== 'compte') {
                throw ValidationException::withMessages(['carnet_id' => 'Le carnet sélectionné doit être un compte actif.']);
            }
            if ($request->type_support === 'tontine' && $carnet->type !== 'tontine') {
                throw ValidationException::withMessages(['carnet_id' => 'Le carnet sélectionné doit être une tontine active.']);
            }

            // Seuil recommandé
            if ($carnet->type === 'tontine' && $category = $carnet->categoryTontine) {
                $requiredPointages = $category->minimumPointagesRequired();
                $currentPointages  = $carnet->totalPointages();

                if ($currentPointages < $requiredPointages) {
                    session()->flash('warning', "Le carnet ne respecte pas encore le seuil recommandé ({$currentPointages}/{$requiredPointages} pointages). L'admin peut tout de même enregistrer le crédit.");
                }
            }

            $guaranteeBase = $carnet->guaranteeBase();
            if ($guaranteeBase <= 0) {
                session()->flash('warning', "Aucune épargne disponible n'a été détectée. Le prêt s'appuie uniquement sur la capacité d'emprunt.");
            } elseif ($request->montant_demande > $guaranteeBase) {
                session()->flash('warning', "Le montant demandé dépasse l'assiette de garantie disponible (" . number_format($guaranteeBase, 0, ',', ' ') . " FCFA).");
            }
        }

        // 3. Vérification globale au niveau du client (hors pending sur le carnet actuel)
        $clientHasActive = Credit::where('client_id', $request->client_id)
            ->whereIn('statut', ['approved', 'active', 'in_arrears'])
            ->exists();

        $clientHasOtherPending = Credit::where('client_id', $request->client_id)
            ->where('statut', 'pending')
            ->where('carnet_id', '!=', $request->carnet_id) // S'il a un pending sur un AUTRE carnet
            ->exists();

        if ($clientHasActive || $clientHasOtherPending) {
            throw ValidationException::withMessages(['client_id' => 'Ce client a déjà un crédit actif ou une demande en attente sur un autre support.']);
        }

        // 4. Lancement de la transaction DB
        DB::beginTransaction();
        try {
            $data = $request->only([
                'client_id', 'carnet_id', 'montant_demande', 'mode',
                'periodicite', 'nombre_echeances', 'taux', 'taux_manuel', 'date_debut', 'differe',
            ]);

            $scheduleArray = CreditCalculator::buildSchedule($data);
            $schedule      = collect($scheduleArray);

            $interestTotal  = CreditCalculator::totalInterest($scheduleArray);
            $montantAccorde = $request->montant_demande;
            $dateFin        = $schedule->last()['date'] ?? $request->date_debut;
            $blockedAmount  = (float) $guaranteeBase;

            $echeanceDiffere = $schedule->firstWhere('is_differe', true);
            $montantDiffere  = $echeanceDiffere ? $echeanceDiffere['total'] : 0;
            $echeanceNormale = $schedule->firstWhere('is_differe', false) ?? $schedule->first();
            $montantNormal   = $echeanceNormale ? $echeanceNormale['total'] : 0;

            // Préparation du Payload Crédit
            $creditPayload = [
                'client_id'                => $data['client_id'],
                'carnet_id'                => $data['carnet_id'] ?? null,
                'cycle_id'                 => $request->cycle_id ?? null,
                'admin_id'                 => auth()->id() ?? null,
                'credit_product_id'        => $request->credit_product_id,
                'credit_object_id'         => $request->credit_object_id,
                'type_support'             => $request->type_support,
                'montant_demande'          => $data['montant_demande'],
                'montant_accorde'          => $montantAccorde,
                'taux'                     => $data['taux'],
                'taux_manuel'              => $data['taux_manuel'],
                'mode'                     => $data['mode'],
                'periodicite'              => $data['periodicite'],
                'nombre_echeances'         => $data['nombre_echeances'],
                'differe'                  => $request->differe,
                'frais_dossier'            => $request->frais_dossier,
                'montant_echeance_differe' => $montantDiffere,
                'montant_echeance'         => $montantNormal,
                'interet_total'            => round($interestTotal, 0),
                'montant_rembourse'        => 0,
                'blocked_amount'           => $blockedAmount,
                'statut'                   => 'pending',
                'date_demande'             => now()->toDateString(),
                'date_debut'               => $data['date_debut'],
                'date_fin_prevue'          => $dateFin,
                'metadata'                 => [
                    'preview'        => true,
                    'guarantee_base' => $blockedAmount,
                ],
            ];

            // Étape A : Création OU Mise à jour du Crédit
            if ($existingPendingCredit) {
                $existingPendingCredit->update($creditPayload);
                $credit = $existingPendingCredit;
            } else {
                $creditPayload['credit_uid'] = (string) Str::uuid();
                $credit                      = Credit::create($creditPayload);
            }

            // Étape B : Gestion des fichiers et Enregistrement du Garant
            $pathPiece = $existingPendingCredit ? $existingPendingCredit->creditGuarantor->piece_identite : null;
            if ($request->hasFile('piece_identite')) {
                $pathPiece = $request->file('piece_identite')->store('guarantors/pieces', 'public');
            }

            $pathRevenu = $existingPendingCredit ? $existingPendingCredit->creditGuarantor->justificatif_revenu : null;
            if ($request->hasFile('justificatif_revenu')) {
                $pathRevenu = $request->file('justificatif_revenu')->store('guarantors/revenus', 'public');
            }

            $guarantorPayload = [
                'credit_id'           => $credit->id,
                'nom_prenom'          => $request->nom_prenom,
                'telephone'           => $request->telephone,
                'profession'          => $request->profession,
                'adresse'             => $request->adresse,
                'piece_identite'      => $pathPiece,
                'justificatif_revenu' => $pathRevenu,
            ];

            if ($existingPendingCredit && $existingPendingCredit->creditGuarantor) {
                $existingPendingCredit->creditGuarantor->update($guarantorPayload);
            } else {
                CreditGuarantor::create($guarantorPayload);
            }

            // Étape C : Recréation de l'Échéancier
            if ($existingPendingCredit) {
                CreditPayment::where('credit_id', $credit->id)->delete();
            }

            foreach ($scheduleArray as $item) {
                CreditPayment::create([
                    'credit_id'         => $credit->id,
                    'echeance'          => $item['numero'],
                    'due_date'          => $item['date'],
                    'montant_principal' => round($item['principal'], 0),
                    'montant_interets'  => round($item['interest'], 0),
                    'montant_total'     => round($item['total'], 0),
                    'status'            => 'pending',
                    'admin_id'          => auth()->id() ?? null,
                ]);
            }

            DB::commit();

            // Message dynamique selon l'action effectuée
            $successMessage = $existingPendingCredit
                ? 'Brouillon de crédit mis à jour avec succès.'
                : 'Demande de crédit enregistrée avec succès.';

            return redirect()->route('admin.credits.index')->with('success', $successMessage);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur crédit: ' . $e->getMessage());
            throw ValidationException::withMessages(['global' => "Une erreur interne est survenue lors de l'enregistrement."]);
        }
    }

    /**
     * Récupère les détails complets d'un carnet
     * Retourne différentes informations selon le type de carnet
     */
    public function getCarnetDetails(int $id)
    {
        $carnet = Carnet::findOrFail($id);
        try {
            $carnet->load(['cycles.collectes', 'cycles.retraits', 'depots', 'retraits']);
            if ($carnet->type === 'tontine') {
                // === CARNET TONTINE ===
                $cycles = $carnet->cycles->map(function (Cycle $cycle) {
                    $totalCollectes  = (float) $cycle->collectes->sum('montant');
                    $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
                    $commission      = (float) ($cycle->montant_journalier ?? 0);

                    $nombrePointages = (int) $cycle->collectes->sum('pointage');

                    // Calcul du retard :
                    $today         = Carbon::today();
                    $daysElapsed   = $cycle->date_debut ? $cycle->date_debut->diffInDays($today) : 0;
                    $isPastDueDate = $cycle->date_fin_prevue && $today->gt($cycle->date_fin_prevue);

                    $enRetard = ($cycle->statut !== 'termine') &&
                        ($nombrePointages < $daysElapsed || $isPastDueDate);

                    return [
                        'id'                  => $cycle->id,
                        'date_debut'          => optional($cycle->date_debut)->format('d/m/Y'),
                        'date_fin_prevue'     => optional($cycle->date_fin_prevue)->format('d/m/Y'),
                        'date_cloture_reelle' => optional($cycle->date_cloture_reelle)->format('d/m/Y'),
                        'mise'                => (int) $commission,
                        'statut'              => $cycle->statut,
                        'total_pointages'     => $nombrePointages,
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
                        'date'             => optional($depot->date_depot)->format('d/m/Y H:i'),
                        'date_ts'          => optional($depot->date_depot)->timestamp ?? 0,
                    ]);
                }

                // Ajouter les retraits
                foreach ($carnet->retraits as $retrait) {
                    $movements->push([
                        'type_transaction' => 'Retrait',
                        'montant'          => (int) $retrait->montant_net,
                        'date'             => optional($retrait->date_retrait)->format('d/m/Y H:i'),
                        'date_ts'          => optional($retrait->date_retrait)->timestamp ?? 0,
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
                    'success'    => true,
                    'type'       => 'compte',
                    'solde'      => (int) $solde,
                    'historique' => $movements,
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage() . ' à la ligne ' . $e->getLine(),
            ], 500);
        }
    }

    public function checkPending($carnetId)
    {
        // 1. On cherche le crédit en attente pour ce carnet
        $credit = Credit::where('carnet_id', $carnetId)
            ->where('statut', 'pending')
            ->with('creditGuarantor')
            ->first();

        // 2. Si un brouillon existe, on vérifie QUI l'a créé
        if ($credit) {
            $currentUser = auth()->user();

            // Sécurité : On compare l'ID de l'admin connecté avec celui stocké sur le crédit
            // (Adapte 'admin_id' ou 'agent_id' selon la colonne qui stocke le créateur dans ta base)
            if ($credit->admin_id !== $currentUser->id) {
                return response()->json([
                    'brouillon_bloque' => true,
                    'message'          => "Ce brouillon a été initié par un autre agent. Vous ne pouvez pas le modifier.",
                ]);
            }
        }

        // 3. Si c'est le bon admin (ou s'il n'y a pas de brouillon), on retourne les données normalement
        return response()->json($credit);
    }

    public function show(Credit $credit)
    {
        $credit->load(['client', 'carnet.parent', 'carnet.enfants']);

        $today                = Carbon::today();
        $totalPenalty         = 0;
        $latePaymentFound     = false;
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
            $isLate           = $payment->status !== 'paid' && $payment->due_date->lt($today);
            $automaticPenalty = 0;

            if ($isLate) {
                $daysLate         = $payment->due_date->diffInDays($today);
                $automaticPenalty = CreditCalculator::calculatePenalty((float) $payment->montant_total, $daysLate);
                $latePaymentFound = true;
            }

            $displayPenalty            = $payment->penalite > 0 ? (float) $payment->penalite : $automaticPenalty;
            $payment->computed_penalty = round($displayPenalty, 0); // Spécificité XAF
            $payment->display_status   = $payment->status === 'paid'
                ? 'paid'
                : ($payment->status === 'partiel' ? 'partiel' : ($isLate ? 'late' : 'pending'));

            $payment->can_pay = ! $credit->payments()
                ->where('echeance', '<', $payment->echeance)
                ->whereIn('status', ['pending', 'partiel'])
                ->exists();

            $totalPenalty += $displayPenalty;

            return $payment;
        }));

        if ($latePaymentFound && $credit->statut === 'active') {
            $credit->update(['statut' => 'in_arrears']);
        }

        $credit->penalty_amount               = round($totalPenalty, 0);
        $credit->payments                     = $payments;
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
        if (! $carnet) {
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
                    $commission     = (float) ($cycle->montant_journalier ?? 0);
                    $net            = max(0, $totalCollectes - $commission);

                    if ($net <= 0) {
                        continue;
                    }

                    Retrait::create([
                        'cycle_id'      => $cycle->id,
                        'client_id'     => $cycle->client_id,
                        'carnet_id'     => $cycle->carnet_id,
                        'admin_id'      => auth()->id(),
                        'montant_total' => $totalCollectes,
                        'commission'    => $commission,
                        'montant_net'   => $net,
                        'date_retrait'  => now(),
                        'note'          => 'Prélèvement de secours automatique pour échéance en défaut',
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
            $newPaid    = min($paidBefore + $withdrawn, (float) $payment->montant_total + (float) $payment->penalite);
            $paidDiff   = $newPaid - $paidBefore;

            $payment->update([
                'montant_paye' => round($newPaid, 0),
                'status'       => $newPaid >= ((float) $payment->montant_total + (float) $payment->penalite) ? 'paid' : 'partiel',
                'date_paye'    => $newPaid >= ((float) $payment->montant_total + (float) $payment->penalite) ? now() : null,
                'admin_id'     => auth()->id(),
            ]);

            if ($paidDiff > 0) {
                $credit->increment('montant_rembourse', round($paidDiff, 0));
            }

            DB::commit();

            return [
                'payment_id'       => $payment->id,
                'echeance'         => $payment->echeance,
                'amount_withdrawn' => round($withdrawn, 0),
                'amount_applied'   => round($paidDiff, 0),
                'note'             => 'Prélèvement de secours automatique pour échéance en défaut',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Emergency withdrawal error: ' . $e->getMessage());
            return null;
        }
    }

    // public function updatePayment(Request $request, Credit $credit, CreditPayment $payment)
    // {
    //     if ($payment->credit_id !== $credit->id) {
    //         abort(404);
    //     }

    //     $request->validate([
    //         'penalite'     => 'nullable|numeric|min:0',
    //         'montant_paye' => 'nullable|numeric|min:0',
    //     ], [
    //         'penalite.min'     => 'La pénalité ne peut pas être négative.',
    //         'montant_paye.min' => 'Le montant payé ne peut pas être négatif.',
    //     ]);

    //     // Utilisation d'une transaction globale avec lock direct en écriture pour éviter le double clic au guichet
    //     return DB::transaction(function () use ($request, $credit, $payment) {

    //         // Verrouiller la ligne de paiement pour empêcher une modification parallèle
    //         $payment = CreditPayment::where('id', $payment->id)->lockForUpdate()->first();

    //         if ($payment->status === 'paid') {
    //             return back()->with('error', 'Action annulée : cette échéance a déjà été encaissée ou soldée entre-temps.');
    //         }

    //         $updates        = [];
    //         $successMessage = 'Échéance mise à jour.';

    //         // 1. Traitement des pénalités forcées manuellement
    //         if ($request->has('penalite')) {
    //             $updates['penalite'] = round($request->input('penalite'), 0);
    //         }

    //         // 2. Traitement d'un versement financier au guichet
    //         if ($request->filled('montant_paye')) {
    //             $amountPaye  = round((float) $request->input('montant_paye'), 0);
    //             $currentPaid = (float) $payment->montant_paye;

    //             // Prendre la nouvelle pénalité soumise ou celle déjà présente en base
    //             $penalite     = array_key_exists('penalite', $updates) ? $updates['penalite'] : (float) $payment->penalite;
    //             $totalDue     = (float) $payment->montant_total + $penalite;
    //             $remainingDue = round($totalDue - $currentPaid, 0);

    //             // Contrôle strict de l'ordre d'amortissement
    //             $previousUnpaidExists = $credit->payments()
    //                 ->where('echeance', '<', $payment->echeance)
    //                 ->whereIn('status', ['pending', 'partiel'])
    //                 ->exists();

    //             if ($previousUnpaidExists) {
    //                 return back()->with('error', 'Opération impossible : des échéances antérieures ne sont pas encore soldées.');
    //             }

    //             if ($amountPaye <= 0) {
    //                 return back()->with('error', 'Le montant à encaisser doit être supérieur à zéro.');
    //             }

    //             if ($amountPaye > $remainingDue) {
    //                 return back()->with('error', 'Le montant saisi excède le reste exigible de cette échéance.');
    //             }

    //             $newPaid                 = round($currentPaid + $amountPaye, 0);
    //             $updates['montant_paye'] = $newPaid;

    //             if ($newPaid >= $totalDue) {
    //                 $updates['status']    = 'paid';
    //                 $updates['date_paye'] = now();
    //                 $successMessage       = "Encaissement de " . number_format($amountPaye, 0, ',', ' ') . " FCFA effectué. Échéance entièrement réglée.";
    //             } else {
    //                 $updates['status']    = 'partiel';
    //                 $updates['date_paye'] = null;
    //                 $remaining            = number_format($totalDue - $newPaid, 0, ',', ' ');
    //                 $successMessage       = "Encaissement partiel de " . number_format($amountPaye, 0, ',', ' ') . " FCFA enregistré. Reste à payer : {$remaining} FCFA.";
    //             }

    //             // Ajuster le cumulatif global remboursé sur la fiche de crédit principale
    //             $credit->increment('montant_rembourse', $amountPaye);
    //         }

    //         $updates['admin_id'] = auth()->id();
    //         $payment->update($updates);

    //         // 3. Vérification de clôture finale du dossier crédit
    //         $creditIsSettled = ! $credit->payments()->where('status', '!=', 'paid')->exists();

    //         if ($creditIsSettled) {
    //             $credit->update([
    //                 'statut'         => 'solder',
    //                 'blocked_amount' => 0,
    //             ]);
    //             $successMessage .= ' Le dossier de crédit est désormais entièrement soldé.';
    //         }

    //         return back()->with('success', $successMessage);
    //     });
    // }

    public function updatePayment(Request $request, Credit $credit, CreditPayment $payment)
    {
        if ($payment->credit_id !== $credit->id) {
            abort(404);
        }

        // Validation avec intégration des champs de la transaction et des tiers payeurs
        $request->validate([
            'penalite'          => 'nullable|numeric|min:0',
            'montant_paye'      => 'nullable|numeric|min:0',
            'mode_paiement'     => 'nullable|string|max:50',
            'reference_externe' => 'nullable|string|max:100',
            'payer_name'        => 'nullable|string|max:150',
            'payer_phone'       => 'nullable|string|max:30',
            'payer_relation'    => 'nullable|string|max:50',
            'notes'             => 'nullable|string|max:500',
        ], [
            'penalite.min'     => 'La pénalité ne peut pas être négative.',
            'montant_paye.min' => 'Le montant payé ne peut pas être négatif.',
        ]);

        // Transaction globale avec lock direct en écriture
        return DB::transaction(function () use ($request, $credit, $payment) {

            // Verrouiller la ligne de paiement pour empêcher une modification parallèle
            $payment = CreditPayment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === 'paid') {
                return back()->with('error', 'Action annulée : cette échéance a déjà été encaissée ou soldée entre-temps.');
            }

            $updates        = [];
            $successMessage = 'Échéance mise à jour.';

            // 1. Traitement des pénalités
            if ($request->has('penalite')) {
                $updates['penalite'] = round($request->input('penalite'), 0);
            }

            // 2. Traitement d'un versement financier au guichet
            if ($request->filled('montant_paye')) {
                $amountPaye  = round((float) $request->input('montant_paye'), 0);
                $currentPaid = (float) $payment->montant_paye;

                $penalite     = array_key_exists('penalite', $updates) ? $updates['penalite'] : (float) $payment->penalite;
                $totalDue     = (float) $payment->montant_total + $penalite;
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

                $newPaid                 = round($currentPaid + $amountPaye, 0);
                $updates['montant_paye'] = $newPaid;

                if ($newPaid >= $totalDue) {
                    $updates['status']    = 'paid';
                    $updates['date_paye'] = now();
                    $successMessage       = "Encaissement de " . number_format($amountPaye, 0, ',', ' ') . " FCFA effectué. Échéance entièrement réglée.";
                } else {
                    $updates['status']    = 'partiel';
                    $updates['date_paye'] = null;
                    $remaining            = number_format($totalDue - $newPaid, 0, ',', ' ');
                    $successMessage       = "Encaissement partiel de " . number_format($amountPaye, 0, ',', ' ') . " FCFA enregistré. Reste à payer : {$remaining} FCFA.";
                }

                // --- CRÉATION DE LA TRANSACTION D'ENCAISSEMENT ---
                PaymentTransaction::create([
                    'credit_payment_id' => $payment->id,
                    'credit_id'         => $credit->id,
                    'agent_id'          => auth()->id(),
                    'montant'           => $amountPaye,
                    'mode_paiement'     => $request->input('mode_paiement', 'especes'),
                    'reference_externe' => $request->input('reference_externe'),
                    'payer_name'        => $request->input('payer_name'),
                    'payer_phone'       => $request->input('payer_phone'),
                    'payer_relation'    => $request->input('payer_relation'),
                    'notes'             => $request->input('notes'),
                ]);

                // Ajuster le cumulatif global remboursé sur le dossier de crédit
                $credit->increment('montant_rembourse', $amountPaye);
            }

            $updates['admin_id'] = auth()->id();
            $payment->update($updates);

            // 3. Vérification de clôture finale du dossier crédit
            $creditIsSettled = ! $credit->payments()->where('status', '!=', 'paid')->exists();

            if ($creditIsSettled) {
                $credit->update([
                    'statut'         => 'solder',
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
            'statut'      => 'active',
            'admin_id'    => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.credits.show', $credit)->with('success', 'Crédit approuvé et activé avec succès.');
    }

    public function settleCreditWithTontine(Credit $credit)
    {
        $result = \App\Services\CreditSettlementService::settleCreditWithAvailableFunds($credit);

        $messageType = $result['success'] ? 'success' : 'warning';
        $message     = $result['message'];

        if (! empty($result['cycles_used'])) {
            $message .= ' - Fonds utilisés : ' . number_format($result['amount_used'], 0, ',', ' ') . ' FCFA de ' . count($result['cycles_used']) . ' cycle(s).';
        }

        return redirect()->route('admin.credits.show', $credit)->with($messageType, $message);
    }
}
