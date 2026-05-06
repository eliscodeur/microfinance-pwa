<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Retrait;
use Illuminate\Support\Facades\DB;

class CreditSettlementService
{
    /**
     * Solder un crédit actif en utilisant les fonds disponibles des cycles de tontine terminés.
     *
     * @param Credit $credit Le crédit à solder
     * @return array Résumé de l'opération
     */
    public static function settleCreditWithAvailableFunds(Credit $credit): array
    {
        if ($credit->statut !== 'active') {
            return [
                'success' => false,
                'message' => 'Le crédit doit être en statut "active" pour être soldé.',
                'amount_used' => 0,
            ];
        }

        $carnet = $credit->carnet;
        if (!$carnet) {
            return [
                'success' => false,
                'message' => 'Le carnet associé au crédit est introuvable.',
                'amount_used' => 0,
            ];
        }

        // Montant restant à rembourser
        $totalDue = (float) $credit->montant_accorde + (float) $credit->interet_total;
        $alreadyPaid = (float) $credit->montant_rembourse;
        $remainingDue = max(0, $totalDue - $alreadyPaid);

        if ($remainingDue <= 0) {
            return [
                'success' => false,
                'message' => 'Le crédit est déjà entièrement remboursé.',
                'amount_used' => 0,
            ];
        }

        $amountUsed = 0;
        $cyclesUsed = [];

        return DB::transaction(function () use ($credit, $carnet, $remainingDue, &$amountUsed, &$cyclesUsed) {
            // Récupère tous les carnets liés (parent, enfants)
            $linkedCarnets = $carnet->allLinkedCarnets();

            // Parcourt les cycles de tontine terminés non encore retirés
            foreach ($linkedCarnets as $linkedCarnet) {
                $cycles = $linkedCarnet->cycles()
                    ->where('statut', 'termine')
                    ->whereNull('retire_at')
                    ->orderBy('completed_at', 'asc')
                    ->get();

                foreach ($cycles as $cycle) {
                    if ($amountUsed >= $remainingDue) {
                        break 2;
                    }

                    // Calcul du montant disponible : somme des collectes - 1 mise (commission)
                    $totalCollectes = (float) $cycle->collectes()->sum('montant');
                    $commission = (float) ($cycle->montant_journalier ?? 0);
                    $availableAmount = max(0, $totalCollectes - $commission);

                    if ($availableAmount <= 0) {
                        continue;
                    }

                    // Montant à utiliser pour ce cycle (ne pas dépasser le solde restant)
                    $amountToUse = min($availableAmount, $remainingDue - $amountUsed);

                    // Créer un enregistrement de retrait
                    Retrait::create([
                        'cycle_id' => $cycle->id,
                        'client_id' => $cycle->client_id,
                        'carnet_id' => $cycle->carnet_id,
                        'admin_id' => auth()->id(),
                        'montant_total' => $totalCollectes,
                        'commission' => $commission,
                        'montant_net' => $amountToUse,
                        'date_retrait' => now(),
                        'note' => 'Remboursement automatique du crédit #' . $credit->id,
                    ]);

                    // Marquer le cycle comme retiré
                    $cycle->update(['retire_at' => now()]);

                    $amountUsed += $amountToUse;
                    $cyclesUsed[] = [
                        'cycle_id' => $cycle->id,
                        'cycle_uid' => $cycle->cycle_uid,
                        'amount' => $amountToUse,
                        'carnet_id' => $linkedCarnet->id,
                        'carnet_numero' => $linkedCarnet->numero,
                    ];
                }
            }

            // Si des fonds ont été utilisés, mettre à jour le crédit
            if ($amountUsed > 0) {
                $newPaid = round($alreadyPaid + $amountUsed, 2);
                $credit->update([
                    'montant_rembourse' => $newPaid,
                ]);

                // Vérifier si le crédit est maintenant entièrement remboursé
                if ($newPaid >= $totalDue) {
                    $credit->update([
                        'statut' => 'solder',
                        'blocked_amount' => 0,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Crédit entièrement remboursé et soldé avec succès.',
                        'amount_used' => round($amountUsed, 2),
                        'cycles_used' => $cyclesUsed,
                        'credit_status' => 'solder',
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'Crédit partiellement remboursé avec succès.',
                    'amount_used' => round($amountUsed, 2),
                    'cycles_used' => $cyclesUsed,
                    'credit_status' => 'active',
                    'remaining_due' => round($totalDue - $newPaid, 2),
                ];
            }

            return [
                'success' => false,
                'message' => 'Aucun fonds disponible trouvé pour le remboursement.',
                'amount_used' => 0,
                'cycles_used' => [],
            ];
        });
    }
}
