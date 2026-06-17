<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Bonus;
use App\Models\Collecte;
use App\Models\Cycle;
use App\Models\SyncBatch;
use App\Models\SyncHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFinalizationService
{
    /**
     * Point d'entrée principal — orchestre toutes les étapes de finalisation.
     */
    public function handle(SyncBatch $batch, int $adminId): void
    {
        if ($batch->status !== 'pending_review') return;

        DB::transaction(function () use ($batch, $adminId) {
            $mappedCycleIds = $this->updateAgentPins($batch);
            $mappedCycleIds = $this->processCycles($batch, $mappedCycleIds);
            $this->processCollectes($batch, $mappedCycleIds);
            $this->createHistory($batch);
            $this->finalizeBatch($batch, $adminId);
        });
    }

    /**
     * Étape 1 — Met à jour les pin_hash des agents si modifiés côté mobile.
     */
    private function updateAgentPins(SyncBatch $batch): array
    {
        $agents = $batch->agents;

        if (empty($agents) || !is_array($agents)) return [];

        foreach ($agents as $agentData) {
            $id   = data_get($agentData, 'id');
            $hash = data_get($agentData, 'pin_hash');

            if ($id && $hash) {
                Agent::where('id', $id)->update([
                    'pin_hash'   => $hash,
                    'updated_at' => now(),
                ]);
            }
        }

        return [];
    }

    /**
     * Étape 2 — Upsert des cycles depuis SyncBatchCycles (source de vérité).
     * Retourne un map [cycle_uid => cycle_id] pour les collectes.
     */
    private function processCycles(SyncBatch $batch, array $mappedCycleIds): array
    {
        // ✅ On lit depuis la relation DB, pas depuis le JSON du batch
        $batchCycles = $batch->syncBatchCycles;

        if ($batchCycles->isEmpty()) return $mappedCycleIds;

        foreach ($batchCycles as $bCycle) {
            $cycleUid = $bCycle->cycle_uid;
            if (!$cycleUid) continue;

            $cycle = Cycle::updateOrCreate(
                ['cycle_uid' => $cycleUid],
                [
                    'carnet_id'             => $bCycle->carnet_id,
                    'agent_id'              => $bCycle->agent_id,
                    'client_id'             => $bCycle->client_id,
                    'montant_journalier'    => $bCycle->montant_journalier,
                    'nombre_jours_objectif' => $bCycle->nombre_jours_objectif,
                    'statut'                => $bCycle->statut,
                    'date_debut'            => Carbon::parse($bCycle->date_debut)->toDateString(),
                    'date_fin_prevue'       => $bCycle->payload['date_fin_prevue'] ?? null
                        ? Carbon::parse($bCycle->payload['date_fin_prevue'])->toDateString()
                        : null,
                    'date_cloture_reelle'   => $bCycle->payload['date_cloture_reelle'] ?? null
                        ? Carbon::parse($bCycle->payload['date_cloture_reelle'])->toDateString()
                        : null,
                ]
            );

            $mappedCycleIds[$cycleUid] = $cycle->id;

            $this->handleCommission($cycle);
        }

        return $mappedCycleIds;
    }

    /**
     * Étape 3 — Upsert des collectes depuis SyncBatchCollectes.
     */
    private function processCollectes(SyncBatch $batch, array $mappedCycleIds): void
    {
        // ✅ On lit depuis la relation DB
        $batchCollectes = $batch->syncBatchCollectes;

        if ($batchCollectes->isEmpty()) return;

        $insertData = [];
        $now = now();

        foreach ($batchCollectes as $bCol) {
            $cycleUid = $bCol->cycle_uid;

            // Cherche d'abord dans la map, puis en DB si nécessaire
            $cycleId = $mappedCycleIds[$cycleUid]
                ?? Cycle::where('cycle_uid', $cycleUid)->value('id');

            if (!$cycleId) {
                Log::warning("SyncFinalization: Cycle introuvable pour UID [{$cycleUid}] — collecte ignorée.", [
                    'collecte_uid'  => $bCol->collecte_uid,
                    'sync_batch_id' => $batch->id,
                ]);
                continue;
            }

            $insertData[] = [
                'collecte_uid' => $bCol->collecte_uid,
                'cycle_id'     => $cycleId,
                'cycle_uid'    => $cycleUid,
                'client_id'    => $bCol->client_id,
                'agent_id'     => $bCol->agent_id,
                'montant'      => $bCol->montant,
                'pointage'     => $bCol->pointage,
                'date_saisie'  => $bCol->date_saisie,
                'sync_uuid'    => $batch->sync_uuid,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if (!empty($insertData)) {
            Collecte::upsert(
                $insertData,
                ['collecte_uid'],
                ['montant', 'pointage', 'updated_at']
            );
        }
    }

    /**
     * Génère une commission si le cycle vient de se terminer.
     * Logique métier isolée et facilement modifiable.
     */
    private function handleCommission(Cycle $cycle): void
    {
        if ($cycle->statut !== 'termine' || $cycle->commission_genere) return;

        $cycle->loadMissing('carnet');

        if (!$cycle->carnet) return;

        Bonus::create([
            'agent_id'          => $cycle->agent_id,
            'cycle_id'          => $cycle->id,
            'montant'           => $cycle->calculerCommission(), // ✅ Logique dans le Model
            'statut'            => 'en_attente',
            'motif'             => "Commission automatique — Cycle #{$cycle->id}",
            'date_attribution'  => now(),
            'commission_genere' => false,
        ]);

        $cycle->update(['commission_genere' => true]);
    }

    /**
     * Étape 4 — Enregistre l'historique de synchronisation.
     */
    private function createHistory(SyncBatch $batch): void
    {
        SyncHistory::create([
            'agent_id'      => $batch->agent_id,
            'sync_uuid'     => $batch->sync_uuid,
            'nb_collectes'  => $batch->nb_collectes,
            'nb_cycles'     => $batch->nb_cycles,
            'total_montant' => $batch->total_montant,
            'status'        => 'success',
        ]);
    }

    /**
     * Étape 5 — Marque le batch comme approuvé et réactive l'agent.
     */
    private function finalizeBatch(SyncBatch $batch, int $adminId): void
    {
        $batch->update([
            'status'       => 'approved',
            'validated_at' => now(),
            'validated_by' => $adminId,
        ]);

        // Réactive l'agent pour sa prochaine synchronisation
        optional($batch->agent)->update(['can_sync' => true]);
    }
}