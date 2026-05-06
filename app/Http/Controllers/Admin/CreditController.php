<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Retrait;
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
        $clients = Client::orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']);

        return Inertia::render('Credits/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $today = now()->toDateString();
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'carnet_id' => [
                Rule::requiredIf(in_array($request->input('type'), ['compte', 'quinzaine'])),
                'nullable',
                'exists:carnets,id',
            ],
            'montant_demande' => 'required|numeric|min:1000',
            'type' => 'required|string|in:compte,quinzaine,mensuel',
            'mode' => 'required|string|in:fixe,degressif',
            'periodicite' => 'required|string|in:quinzaine,mensuelle',
            'nombre_echeances' => 'required|integer|min:1|max:60',
            'taux' => 'required|numeric|min:0|max:100',
            'taux_manuelle' => 'nullable|numeric|min:0|max:100',
            'date_debut' => "required|date|after_or_equal:{$today}",
        ], [
            'carnet_id.required' => 'Un carnet est obligatoire pour un crédit sur compte ou un crédit quinzaine.',
            'date_debut.after_or_equal' => 'La date de début doit être aujourd’hui ou ultérieure.',
            'taux.max' => 'Le taux ne peut pas dépasser 100%.',
            'taux_manuelle.max' => 'Le taux manuel ne peut pas dépasser 100%.',
            'nombre_echeances.max' => 'Le nombre d’échéances ne peut pas dépasser 60.',
        ]);

        if ($request->filled('carnet_id')) {
            $carnet = Carnet::with('categoryTontine')
                ->where('id', $request->carnet_id)
                ->where('client_id', $request->client_id)
                ->where('statut', 'actif')
                ->first();

            if (!$carnet) {
                return back()->withInput()->with('error', 'Le carnet sélectionné est invalide ou n’appartient pas au client.');
            }

            if ($request->type === 'compte' && $carnet->type !== 'compte') {
                return back()->withInput()->with('error', 'Le carnet sélectionné doit être un compte actif.');
            }

            if ($request->type === 'quinzaine' && $carnet->type !== 'tontine') {
                return back()->withInput()->with('error', 'Le carnet sélectionné doit être une tontine active pour un crédit quinzaine.');
            }

            if ($carnet->type === 'tontine' && $request->type !== 'quinzaine') {
                return back()->withInput()->with('error', 'Ce carnet de tontine ne peut être utilisé que pour un crédit quinzaine.');
            }

            if ($request->type === 'quinzaine') {
                $category = $carnet->categoryTontine;
                if (!$category) {
                    return back()->withInput()->with('error', 'La catégorie de tontine du carnet est manquante.');
                }

                $requiredPointages = $category->minimumPointagesRequired();
                $currentPointages = $carnet->totalPointages();

                if ($currentPointages < $requiredPointages) {
                    session()->flash('warning', "Le carnet ne respecte pas encore le seuil recommandé ({$currentPointages}/{$requiredPointages} pointages). L'admin peut tout de même enregistrer le crédit.");
                }
            }

            $guaranteeBase = $carnet->guaranteeBase();
            if ($guaranteeBase <= 0) {
                session()->flash('warning', "Aucune épargne disponible n'a été détectée sur le carnet et ses comptes liés. Le prêt s'appuie uniquement sur la capacité d'emprunt.");
            } elseif ($request->montant_demande > $guaranteeBase) {
                session()->flash('warning', 'Le montant demandé dépasse l\'assiette de garantie disponible (' . number_format($guaranteeBase, 0, ',', ' ') . ' FCFA). Le crédit peut toujours être enregistré, mais la garantie d\'épargne est limitée.');
            }
        }

        $clientHasActive = Credit::where('client_id', $request->client_id)
            ->whereIn('statut', ['pending', 'approved', 'active', 'in_arrears'])
            ->exists();

        if ($clientHasActive) {
            return back()->with('error', 'Ce client a déjà un crédit actif ou en attente.');
        }

        DB::beginTransaction();

        try {
            $data = $request->only([
                'client_id',
                'carnet_id',
                'montant_demande',
                'type',
                'mode',
                'periodicite',
                'nombre_echeances',
                'taux',
                'taux_manuelle',
                'date_debut',
            ]);

            $schedule = CreditCalculator::buildSchedule($data);
            $interestTotal = CreditCalculator::totalInterest($schedule);
            $montantAccorde = $request->montant_demande;
            $dateFin = collect($schedule)->last()['date'] ?? $request->date_debut;
            $monthlyAmount = collect($schedule)->avg('total');
            $blockedAmount = $guaranteeBase ?? 0.0;

            $credit = Credit::create([
                'credit_uid' => Str::uuid(),
                'client_id' => $data['client_id'],
                'carnet_id' => $data['carnet_id'] ?? null,
                'montant_demande' => $data['montant_demande'],
                'montant_accorde' => $montantAccorde,
                'taux' => CreditCalculator::calculateRate($data['taux'], $data['taux_manuelle']),
                'taux_manuelle' => $data['taux_manuelle'],
                'type' => $data['type'],
                'mode' => $data['mode'],
                'periodicite' => $data['periodicite'],
                'nombre_echeances' => $data['nombre_echeances'],
                'montant_echeance' => round($monthlyAmount, 2),
                'interet_total' => round($interestTotal, 2),
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

            foreach ($schedule as $item) {
                CreditPayment::create([
                    'credit_id' => $credit->id,
                    'echeance' => $item['numero'],
                    'due_date' => $item['date'],
                    'montant_principal' => $item['principal'],
                    'montant_interets' => $item['interest'],
                    'montant_total' => $item['total'],
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return redirect()->route('admin.credits.index')->with('success', 'Demande de crédit enregistrée avec succès.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Credit store error: ' . $e->getMessage());
            return back()->with('error', 'Impossible de créer la demande de crédit.');
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
            $payment->computed_penalty = round($displayPenalty, 2);
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
            $credit->statut = 'in_arrears';
        }

        $credit->penalty_amount = round($totalPenalty, 2);
        $credit->payments = $payments;
        $credit->emergency_withdrawal_summary = $emergencyWithdrawals;

        return Inertia::render('Credits/Show', [
            'credit' => $credit,
        ]);
    }

    protected function applyEmergencyWithdrawal(Credit $credit, CreditPayment $payment)
    {
        if ($payment->status === 'paid') {
            return;
        }

        $amountDue = ((float) $payment->montant_total + (float) $payment->penalite) - (float) $payment->montant_paye;
        if ($amountDue <= 0) {
            return;
        }

        $carnet = $credit->carnet;
        if (!$carnet) {
            return;
        }

        $withdrawn = 0.0;
        foreach ($carnet->allLinkedCarnets() as $linkedCarnet) {
            $cycles = $linkedCarnet->cycles()
                ->where('statut', 'termine')
                ->whereNull('retire_at')
                ->orderBy('completed_at')
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
            return null;
        }

        $paidBefore = (float) $payment->montant_paye;
        $newPaid = min($paidBefore + $withdrawn, (float) $payment->montant_total + (float) $payment->penalite);
        $paidDiff = $newPaid - $paidBefore;

        $payment->update([
            'montant_paye' => round($newPaid, 2),
            'status' => $newPaid >= (float) $payment->montant_total + (float) $payment->penalite ? 'paid' : 'partiel',
            'date_paye' => $newPaid >= (float) $payment->montant_total + (float) $payment->penalite ? now() : null,
        ]);

        if ($paidDiff > 0) {
            $credit->increment('montant_rembourse', round($paidDiff, 2));
        }

        return [
            'payment_id' => $payment->id,
            'echeance' => $payment->echeance,
            'amount_withdrawn' => round($withdrawn, 2),
            'amount_applied' => round($paidDiff, 2),
            'note' => 'Prélèvement de secours automatique pour échéance en défaut',
        ];
    }

    public function updatePayment(Request $request, Credit $credit, CreditPayment $payment)
    {
        if ($payment->credit_id !== $credit->id) {
            abort(404);
        }

        if ($payment->status === 'paid') {
            return back()->with('error', 'Impossible de modifier une échéance déjà payée.');
        }

        $request->validate([
            'penalite' => 'nullable|numeric|min:0',
            'montant_paye' => 'nullable|numeric|min:0',
        ], [
            'penalite.numeric' => 'La pénalité doit être un nombre.',
            'penalite.min' => 'La pénalité ne peut pas être négative.',
            'montant_paye.numeric' => 'Le montant payé doit être un nombre.',
            'montant_paye.min' => 'Le montant payé ne peut pas être négatif.',
        ]);

        $updates = [];

        if ($request->has('penalite')) {
            $updates['penalite'] = $request->input('penalite');
        }

        if ($request->filled('montant_paye')) {
            $amountPaye = (float) $request->input('montant_paye');
            $currentPaid = (float) $payment->montant_paye;
            $penalite = $updates['penalite'] ?? (float) $payment->penalite;
            $totalDue = (float) $payment->montant_total + $penalite;
            $newPaid = round($currentPaid + $amountPaye, 2);

            $previousUnpaidExists = $credit->payments()
                ->where('echeance', '<', $payment->echeance)
                ->whereIn('status', ['pending', 'partiel'])
                ->exists();

            if ($previousUnpaidExists) {
                return back()->with('error', 'Impossible de payer cette échéance tant que les échéances antérieures ne sont pas entièrement réglées.');
            }

            if ($amountPaye <= 0) {
                return back()->with('error', 'Le montant payé doit être supérieur à zéro.');
            }

            $remainingDue = $totalDue - $currentPaid;
            $surplus = 0.0;
            $amountToApply = $amountPaye;
            $surplusNote = null;

            if ($amountPaye > $remainingDue) {
                $surplus = round($amountPaye - $remainingDue, 2);
                $amountToApply = $remainingDue;

                $client = $credit->client ?: $credit->load('client')->client;
                $compteCarnet = $client->carnets()
                    ->where('type', 'compte')
                    ->where('statut', 'actif')
                    ->first();

                if ($compteCarnet) {
                    $surplusNote = 'Le surplus de ' . number_format($surplus, 2, ',', ' ') . ' FCFA a été versé sur le compte épargne du client (' . $compteCarnet->numero . ').';
                    $metadata = $credit->metadata ?? [];
                    $metadata['surplus_deposits'] = array_merge($metadata['surplus_deposits'] ?? [], [[
                        'payment_id' => $payment->id,
                        'amount' => $surplus,
                        'carnet_id' => $compteCarnet->id,
                        'carnet_numero' => $compteCarnet->numero,
                        'type' => 'savings_deposit',
                        'created_at' => now()->toDateTimeString(),
                    ]]);
                    $credit->update(['metadata' => $metadata]);
                } else {
                    $surplusNote = 'Le montant exact de l\'échéance a été enregistré. Le reliquat de ' . number_format($surplus, 2, ',', ' ') . ' FCFA doit être remis au client.';
                    $metadata = $credit->metadata ?? [];
                    $metadata['surplus_returns'] = array_merge($metadata['surplus_returns'] ?? [], [[
                        'payment_id' => $payment->id,
                        'amount' => $surplus,
                        'note' => 'Reliquat remis au client',
                        'created_at' => now()->toDateTimeString(),
                    ]]);
                    $credit->update(['metadata' => $metadata]);
                }
            }

            $newPaid = round($currentPaid + $amountToApply, 2);
            $updates['montant_paye'] = $newPaid;

            if ($newPaid >= $totalDue) {
                $updates['status'] = 'paid';
                $updates['date_paye'] = now();
                $successMessage = 'Paiement enregistré, échéance réglée.';
            } else {
                $updates['status'] = 'partiel';
                $updates['date_paye'] = null;
                $remaining = number_format($totalDue - $newPaid, 2, ',', ' ');
                $successMessage = "Paiement partiel enregistré. Solde restant : {$remaining} FCFA.";
            }

            if ($surplusNote) {
                $successMessage .= ' ' . $surplusNote;
            }
        }

        if (empty($updates)) {
            return back()->with('error', 'Aucune modification à enregistrer.');
        }

        $payment->update($updates);

        if (!empty($amountToApply) && $amountToApply > 0) {
            $credit->increment('montant_rembourse', round($amountToApply, 2));
        }

        // Check if all payments are paid and mark credit as settled
        $allPaymentsPaid = $credit->payments()
            ->where('status', '!=', 'paid')
            ->doesntExist();

        if ($allPaymentsPaid) {
            $credit->update([
                'statut' => 'solder',
                'blocked_amount' => 0,
            ]);

            $successMessage = ($successMessage ?? 'Échéance mise à jour') . ' - Crédit entièrement remboursé et statut mis à jour.';
        }

        return back()->with('success', $successMessage ?? 'Échéance mise à jour avec succès.');
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

        return redirect()->route('admin.credits.show', $credit)->with('success', 'Crédit approuvé et activé.');
    }

    /**
     * Solder un crédit actif avec les fonds disponibles des cycles de tontine.
     */
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
