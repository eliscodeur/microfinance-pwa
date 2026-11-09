<?php

namespace App\Services;

use App\Models\Carnet;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\CreditSettlement;
use App\Models\CreditSettlementAllocation;
use App\Models\CreditSettlementSource;
use App\Models\Cycle;
use App\Models\PaymentTransaction;
use App\Models\Recette;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditSettlementService
{
    public static function settleCreditWithSavings(
        Credit $credit,
        string $idempotencyKey,
        ?string $note = null
    ): CreditSettlement {
        return DB::transaction(function () use ($credit, $idempotencyKey, $note) {
            $credit = Credit::whereKey($credit->id)->lockForUpdate()->firstOrFail();

            $existing = CreditSettlement::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->credit_id !== $credit->id || $existing->admin_id !== auth()->id()) {
                    throw new \DomainException('Cette clé de demande est déjà utilisée pour une autre opération.');
                }

                return $existing;
            }

            if (! in_array($credit->statut, ['active', 'in_arrears'], true)) {
                throw new \DomainException('Seul un crédit actif ou en retard peut recevoir une compensation d’épargne.');
            }

            if (! $credit->carnet_id) {
                throw new \DomainException('Aucun carnet de garantie n’est associé à ce crédit.');
            }

            $baseCarnet = Carnet::with(['parent.enfants', 'enfants'])
                ->whereKey($credit->carnet_id)
                ->first();
            if (! $baseCarnet || (int) $baseCarnet->client_id !== (int) $credit->client_id) {
                throw new \DomainException('Le carnet de garantie n’appartient pas au client de ce crédit.');
            }
            if (! in_array($baseCarnet->type, ['compte', 'tontine'], true)) {
                throw new \DomainException('Le type du carnet ne permet pas une compensation d’épargne.');
            }

            if ($baseCarnet->type === 'compte') {
                $carnets = collect([
                    Carnet::whereKey($baseCarnet->id)->lockForUpdate()->firstOrFail(),
                ]);
            } else {
                $carnets = $baseCarnet->allLinkedCarnets()
                    ->filter(function (Carnet $carnet) use ($credit) {
                        return $carnet->type === 'tontine'
                            && (int) $carnet->client_id === (int) $credit->client_id;
                    })
                    ->sortBy('id')
                    ->values();

                if ($carnets->isEmpty()) {
                    throw new \DomainException('Aucun carnet de tontine appartenant à ce client n’est lié au crédit.');
                }
            }

            $payments = CreditPayment::where('credit_id', $credit->id)
                ->whereIn('status', ['pending', 'partiel'])
                ->orderBy('due_date')
                ->orderBy('echeance')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $dueAllocations = [];
            $remainingDue = 0;
            $today = Carbon::today();

            foreach ($payments as $payment) {
                $penalty = (float) $payment->penalite;
                if ($payment->due_date && $payment->due_date->lt($today)) {
                    $daysLate = (int) $payment->due_date->diffInDays($today);
                    $penalty = max(
                        $penalty,
                        CreditCalculator::calculatePenalty((float) $payment->montant_total, $daysLate)
                    );
                }

                $outstanding = max(
                    0,
                    (int) round((float) $payment->montant_total + $penalty - (float) $payment->montant_paye)
                );
                if ($outstanding <= 0) {
                    continue;
                }

                $dueAllocations[] = [
                    'payment' => $payment,
                    'penalty' => (int) round($penalty),
                    'remaining' => $outstanding,
                ];
                $remainingDue += $outstanding;
            }

            if ($payments->isEmpty()) {
                $remainingDue = max(
                    0,
                    (int) round((float) $credit->montant_accorde
                        + (float) $credit->interet_total
                        - (float) $credit->montant_rembourse)
                );
                if ($remainingDue > 0) {
                    $dueAllocations[] = [
                        'payment' => null,
                        'penalty' => 0,
                        'remaining' => $remainingDue,
                    ];
                }
            }

            if ($remainingDue <= 0) {
                throw new \DomainException('Le crédit ne présente aucun montant exigible à rembourser.');
            }

            $sourcePlans = [];
            $amountAvailableToApply = $remainingDue;

            if ($baseCarnet->type === 'compte') {
                $savingsCarnet = $carnets->first();
                $totalDeposits = (float) $savingsCarnet->depots()->sum('montant');
                $totalWithdrawals = (float) $savingsCarnet->retraits()->sum('montant_net');
                $totalSettled = (float) $savingsCarnet->creditSettlementSources()->sum('montant_affecte');
                $available = max(0, (int) round($totalDeposits - $totalWithdrawals - $totalSettled));
                $amountToApply = min($available, $amountAvailableToApply);

                if ($amountToApply > 0) {
                    $sourcePlans[] = [
                        'cycle' => null,
                        'carnet' => $savingsCarnet,
                        'amount' => $amountToApply,
                    ];
                    $amountAvailableToApply -= $amountToApply;
                }
            }

            $cycles = $baseCarnet->type === 'tontine'
                ? Cycle::whereIn('carnet_id', $carnets->pluck('id'))
                ->where('client_id', $credit->client_id)
                ->where('statut', 'termine')
                ->whereNull('retire_at')
                ->orderBy('completed_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                : collect();

            foreach ($cycles as $cycle) {
                if ($amountAvailableToApply <= 0) {
                    break;
                }

                $totalCollected = (int) round((float) $cycle->collectes()->sum('montant'));
                $withdrawnNet = (int) round((float) $cycle->retraits()->sum('montant_net'));
                $withdrawnFees = (int) round((float) $cycle->retraits()->sum('commission'));
                $settledNet = (int) round((float) $cycle->creditSettlementSources()->sum('montant_affecte'));
                $settlementFees = (int) round((float) $cycle->creditSettlementSources()->sum('commission'));
                $available = max(
                    0,
                    $totalCollected - $withdrawnNet - $withdrawnFees - $settledNet - $settlementFees
                );
                $amountToApply = min($available, $amountAvailableToApply);

                if ($amountToApply <= 0) {
                    continue;
                }

                $sourcePlans[] = [
                    'cycle' => $cycle,
                    'carnet' => null,
                    'amount' => $amountToApply,
                    'remaining_after' => $available - $amountToApply,
                ];
                $amountAvailableToApply -= $amountToApply;
            }

            $amountUsed = $remainingDue - $amountAvailableToApply;
            if ($amountUsed <= 0) {
                throw new \DomainException('Aucun solde d’épargne disponible pour cette compensation.');
            }

            $settlement = CreditSettlement::create([
                'ulid' => strtolower((string) Str::ulid()),
                'reference' => 'SET-EP-' . strtoupper((string) Str::ulid()),
                'idempotency_key' => $idempotencyKey,
                'credit_id' => $credit->id,
                'client_id' => $credit->client_id,
                'admin_id' => auth()->id(),
                'montant' => $amountUsed,
                'date_operation' => now(),
                'note' => $note,
            ]);

            $dueIndex = 0;
            foreach ($sourcePlans as $sourcePlan) {
                /** @var Cycle $cycle */
                $cycle = $sourcePlan['cycle'];
                $source = CreditSettlementSource::create([
                    'credit_settlement_id' => $settlement->id,
                    'cycle_id' => $cycle?->id,
                    'carnet_id' => $sourcePlan['carnet']?->id,
                    'montant_affecte' => $sourcePlan['amount'],
                    'commission' => 0,
                ]);
                $sourceRemaining = $sourcePlan['amount'];

                while ($sourceRemaining > 0 && isset($dueAllocations[$dueIndex])) {
                    $due = &$dueAllocations[$dueIndex];
                    if ($due['remaining'] <= 0) {
                        $dueIndex++;
                        unset($due);
                        continue;
                    }

                    $allocated = min($sourceRemaining, $due['remaining']);
                    $payment = $due['payment'];
                    $allocation = CreditSettlementAllocation::create([
                        'credit_settlement_id' => $settlement->id,
                        'credit_settlement_source_id' => $source->id,
                        'credit_payment_id' => $payment?->id,
                        'echeance' => $payment?->echeance,
                        'date_echeance' => $payment?->due_date?->toDateString(),
                        'montant' => $allocated,
                    ]);

                    if ($payment) {
                        if ($due['penalty'] > (int) round((float) $payment->penalite)) {
                            $payment->penalite = $due['penalty'];
                        }

                        $newPaid = (int) round((float) $payment->montant_paye) + $allocated;
                        $totalDue = (int) round((float) $payment->montant_total + (float) $payment->penalite);
                        $payment->montant_paye = $newPaid;
                        $payment->status = $newPaid >= $totalDue ? 'paid' : 'partiel';
                        $payment->date_paye = $newPaid >= $totalDue ? now() : null;
                        $payment->admin_id = auth()->id();
                        $payment->save();

                        PaymentTransaction::create([
                            'credit_payment_id' => $payment->id,
                            'credit_id' => $credit->id,
                            'agent_id' => auth()->id(),
                            'montant' => $allocated,
                            'mode_paiement' => 'compensation_epargne',
                            'reference_externe' => $settlement->reference,
                            'notes' => 'Compensation interne depuis '
                                . ($cycle ? 'le cycle #' . $cycle->id : 'le carnet d’épargne #' . $sourcePlan['carnet']->id)
                                . ' — opération ' . $settlement->reference
                                . ' — affectation #' . $allocation->id,
                        ]);
                    }

                    $due['remaining'] -= $allocated;
                    $sourceRemaining -= $allocated;
                    unset($due);

                    if ($dueAllocations[$dueIndex]['remaining'] <= 0) {
                        $dueIndex++;
                    }
                }

                if ($cycle && $sourcePlan['remaining_after'] <= 0) {
                    $cycle->update(['retire_at' => now()]);
                }
            }

            $credit->montant_rembourse = (int) round((float) $credit->montant_rembourse) + $amountUsed;
            if ($amountUsed >= $remainingDue) {
                $credit->statut = 'solder';
                $credit->blocked_amount = 0;
            }
            $credit->save();

            Recette::create([
                'ulid' => strtolower((string) Str::ulid()),
                'reference' => 'REC-RMB-' . $settlement->reference,
                'type_recette' => 'remboursement_credit',
                'montant' => $amountUsed,
                'client_id' => $credit->client_id,
                'credit_id' => $credit->id,
                'user_id' => auth()->id(),
                'mode_paiement' => 'compensation_epargne',
                'date_recette' => $settlement->date_operation,
                'commentaire' => 'Remboursement par compensation interne d’épargne — opération '
                    . $settlement->reference
                    . ($note ? ' — ' . $note : ''),
            ]);

            return $settlement;
        }, 3);
    }
}
