<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Bonus;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Collecte;
use App\Models\Cycle;
use App\Models\Paiement;
use App\Models\SyncBatch;
use App\Models\SyncBatchCollecte;
use App\Models\SyncBatchCycle;
use App\Services\SyncFinalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    private SyncFinalizationService $finalizationService;

    public function __construct(SyncFinalizationService $finalizationService)
    {
        $this->finalizationService = $finalizationService;
    }
    /**
     * Point d'entrée synchronisation montante (Mobile → Serveur).
     */
    public function synchroniser(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Payload complet pour l'agent (Serveur → Mobile).
     * Utilisé à la première connexion et après chaque validation.
     */
    public function getInitialData(): JsonResponse
    {
        $agent = auth()->user()?->agent;

        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Agent non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->buildSyncPayload($agent),
        ]);
    }

    /**
     * Reçoit et stocke les données offline de l'agent.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'matricule' => 'required|string',
            'sync_uuid' => 'required|string',
            'cycles'    => 'nullable|array',
            'collectes' => 'nullable|array',
        ]);

        $agent = Agent::where('code_agent', $request->matricule)->first();

        if (!$agent || !$agent->can_sync) {
            return response()->json([
                'success' => false,
                'message' => 'Synchronisation interdite ou agent introuvable.',
            ], 403);
        }

        // Idempotence — on retourne le batch existant si déjà reçu
        $existingBatch = SyncBatch::where('sync_uuid', $request->sync_uuid)->first();
        if ($existingBatch) {
            return response()->json([
                'success' => true,
                'message' => $this->batchMessage($existingBatch),
                'batch'   => $this->serializeBatch($existingBatch),
                'data'    => $existingBatch->status === 'approved'
                    ? $this->buildSyncPayload($agent)
                    : null,
            ]);
        }

        DB::beginTransaction();
        try {
            $collectes = $request->input('collectes', []);
            $cycles    = $request->input('cycles', []);

            $batch = SyncBatch::create([
                'agent_id'      => $agent->id,
                'sync_uuid'     => $request->sync_uuid,
                'agents'        => $request->input('agents'),
                'status'        => 'pending_review',
                'nb_collectes'  => count($collectes),
                'nb_cycles'     => count($cycles),
                'total_montant' => collect($collectes)->sum('montant'),
            ]);

            // ✅ On bloque l'agent dès réception pour éviter les doubles envois
            $agent->update(['can_sync' => false]);

            $cycleMap = $this->storeBatchCycles($batch, $cycles, $agent->id);
            $this->storeBatchCollectes($batch, $collectes, $agent->id, $cycleMap);

            DB::commit();

            return response()->json([
                'success' => true,
                'batch'   => $this->serializeBatch($batch),
            ], 202);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SyncController@store — Erreur: ' . $e->getMessage(), [
                'agent'     => $request->matricule,
                'sync_uuid' => $request->sync_uuid,
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Délègue la finalisation au service dédié.
     * Appelée par l'admin depuis le back-office.
     */
    public function finalizeBatch(SyncBatch $batch, ?int $adminId = null): void
    {
        $this->finalizationService->handle($batch, $adminId ?? auth()->id());
    }

    /**
     * Statut d'un batch — polling depuis le mobile.
     */
    public function batchStatus(string $syncUuid): JsonResponse
    {
        $batch = SyncBatch::where('sync_uuid', $syncUuid)->first();

        if (!$batch) {
            return response()->json(['status' => 'not_found'], 200);
        }

        $response = [
            'status' => $batch->status,
            'batch'  => $this->serializeBatch($batch),
        ];

        if ($batch->status === 'approved') {
            $response['data'] = $this->buildSyncPayload($batch->agent);
        }

        return response()->json($response);
    }

    // ─────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────

    /**
     * Enregistre les cycles temporaires et retourne le map [cycle_uid => batch_cycle_id].
     */
    private function storeBatchCycles(SyncBatch $batch, array $cycles, int $agentId): array
    {
        $cycleMap = [];

        foreach ($cycles as $cycleData) {
            $uid = $cycleData['cycle_uid'] ?? $cycleData['id'] ?? null;
            if (!$uid) continue;

            $batchCycle = SyncBatchCycle::create([
                'sync_batch_id'         => $batch->id,
                'cycle_uid'             => $uid,
                'carnet_id'             => $cycleData['carnet_id'],
                'client_id'             => $cycleData['client_id'],
                'agent_id'              => $agentId,
                'montant_journalier'    => $cycleData['montant_journalier'],
                'nombre_jours_objectif' => $cycleData['nombre_jours_objectif'] ?? 31,
                'statut'                => $cycleData['statut'] ?? 'en_cours',
                'date_debut'            => Carbon::parse($cycleData['date_debut'] ?? now())->toDateTimeString(),
                'payload'               => $cycleData,
            ]);

            $cycleMap[$uid] = $batchCycle->id;
        }

        return $cycleMap;
    }

    /**
     * Enregistre les collectes temporaires.
     */
    private function storeBatchCollectes(SyncBatch $batch, array $collectes, int $agentId, array $cycleMap): void
    {
        foreach ($collectes as $colData) {
            $cycleRef = $colData['cycle_uid'] ?? $colData['cycle_id'] ?? null;

            SyncBatchCollecte::create([
                'sync_batch_id'       => $batch->id,
                'sync_batch_cycle_id' => $cycleMap[$cycleRef] ?? null,
                'collecte_uid'        => $colData['collecte_uid'],
                'cycle_uid'           => $colData['cycle_uid'] ?? null,
                'cycle_ref'           => $colData['cycle_id'] ?? null,
                'client_id'           => $colData['client_id'],
                'agent_id'            => $agentId,
                'montant'             => $colData['montant'],
                'pointage'            => $colData['pointage'] ?? 1,
                'date_saisie'         => Carbon::parse($colData['date_saisie'] ?? now())->toDateTimeString(),
                'payload'             => $colData,
            ]);
        }
    }

    /**
     * Construit le payload complet pour l'agent (initial data + après approbation).
     */
    private function buildSyncPayload(Agent $agent): array
    {
        $clients = Client::where('agent_id', $agent->id)
            ->whereHas('carnets', fn($q) => $q->where('statut', 'actif')->where('type', 'tontine'))
            ->get();

        $clientIds = $clients->pluck('id');

        $carnets = Carnet::whereIn('client_id', $clientIds)
            ->where('statut', 'actif')
            ->where('type', 'tontine')
            ->withCount(['cycles as total_cycles_termines' => fn($q) => $q->where('statut', 'termine')])
            ->get();

        $cycles = Cycle::whereIn('carnet_id', $carnets->pluck('id'))
            ->where(fn($q) => $q
                ->where('statut', 'en_cours')
                ->orWhere(fn($q2) => $q2->where('statut', 'termine')->whereNull('retire_at'))
            )
            ->with(['collectes', 'retraits'])
            ->get()
            ->map(function ($cycle) {
                $totalCollectes   = (float) $cycle->collectes->sum('montant');
                $totalDejaRetire  = (float) $cycle->retraits->sum('montant_net');
                $commission       = (float) ($cycle->montant_journalier ?? 0);

                $cycle->solde_restant_net = max(0, $totalCollectes - $commission - $totalDejaRetire);

                // Injecte cycle_uid dans chaque retrait pour faciliter le filtrage Dexie
                $cycle->retraits->each(function ($retrait) use ($cycle) {
                    $retrait->cycle_uid = $cycle->cycle_uid;
                    $retrait->synced    = 1;
                });

                return $cycle;
            });

        // ✅ filter() protège contre les relations null
        $collectes = $cycles->pluck('collectes')->filter()->flatten()->map(function ($col) {
            $col->synced = 1;
            return $col;
        });

        $retraits = $cycles->pluck('retraits')->filter()->flatten();

        $bonusEnAttente = Bonus::where('agent_id', $agent->id)
            ->where('statut', 'en_attente')
            ->orderBy('date_attribution', 'desc')
            ->get();

        $historiquePaiements = Paiement::where('agent_id', $agent->id)
            ->with(['bonuses'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'success' => true,
            'agent'   => [
                'id'       => $agent->id,
                'nom'      => $agent->nom,
                'pin_hash' => $agent->pin_hash,
            ],
            'clients'               => $clients,
            'carnets'               => $carnets,
            'cycles'                => $cycles->makeHidden(['collectes', 'retraits']),
            'collectes'             => $collectes,
            'retraits'              => $retraits,
            'bonus_en_attente'      => $bonusEnAttente,
            'historique_paiements'  => $historiquePaiements,
            'server_date'           => now()->toDateString(),
        ];
    }

    private function serializeBatch(SyncBatch $batch): array
    {
        return [
            'id'        => $batch->id,
            'sync_uuid' => $batch->sync_uuid,
            'status'    => $batch->status,
        ];
    }

    private function batchMessage(SyncBatch $batch): string
    {
        return match ($batch->status) {
            'approved' => 'Batch déjà validé.',
            'rejected' => 'Batch rejeté.',
            default    => 'Batch en attente de validation.',
        };
    }
}