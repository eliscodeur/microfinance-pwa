<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Collecte;
use App\Models\Cycle;
use App\Models\Bonus;
use App\Models\Paiement;
use App\Models\SyncBatch;
use App\Models\SyncBatchCollecte;
use App\Models\SyncBatchCycle;
use App\Models\SyncHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Point d'entrée pour la synchronisation montante (Mobile -> Serveur)
     */
    public function synchroniser(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Prépare le payload complet pour l'agent (Serveur -> Mobile)
     * Utile pour la première connexion et après chaque validation.
     */
    public function getInitialData()
    {
        $agent = auth()->user()->agent;
        if (!$agent) return response()->json(['success' => false, 'message' => 'Agent non trouvé'], 404);

        return response()->json([
            'success' => true,
            'data' => $this->buildSyncPayload($agent)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
                'matricule' => 'required|string',
                'sync_uuid' => 'required|string',
                'cycles' => 'nullable|array',
                'collectes' => 'nullable|array',
            ]);

        $agent = Agent::where('code_agent', $request->input('matricule'))->first();

        if (!$agent || !$agent->can_sync) {
            return response()->json([
                'success' => false, 
                'message' => 'Synchronisation interdite ou agent introuvable.'
            ], 403);
        }

        $syncUuid = $request->sync_uuid;

        // Éviter les doublons si l'agent clique deux fois
        $existingBatch = SyncBatch::where('sync_uuid', $syncUuid)->first();
        if ($existingBatch) {
            return response()->json([
                'success' => true,
                'message' => $this->batchMessage($existingBatch),
                'batch' => $this->serializeBatch($existingBatch),
                'data' => $existingBatch->status === 'approved' ? $this->buildSyncPayload($agent) : null,
            ]);
        }

        DB::beginTransaction();
        try {
            $batch = SyncBatch::create([
                'agent_id' => $agent->id,
                'sync_uuid' => $syncUuid,
                'agents' => $request->input('agents'),
                'status' => 'pending_review',
                'nb_collectes' => count($request->collectes ?? []),
                'nb_cycles' => count($request->cycles ?? []),
                'total_montant' => collect($request->collectes)->sum('montant'),
            ]);

            $cycleMap = [];

            // 1. Enregistrement des cycles temporaires
            foreach ($request->input('cycles', []) as $cycleData) {
                $batchCycle = SyncBatchCycle::create([
                    'sync_batch_id' => $batch->id,
                    'cycle_uid' => $cycleData['cycle_uid'] ?? $cycleData['id'],
                    'carnet_id' => $cycleData['carnet_id'],
                    'client_id' => $cycleData['client_id'],
                    'agent_id' => $agent->id,
                    'montant_journalier' => $cycleData['montant_journalier'],
                    'nombre_jours_objectif' => $cycleData['nombre_jours_objectif'] ?? 31,
                    'statut' => $cycleData['statut'] ?? 'en_cours',
                    'date_debut' => $this->normalizeDateTime($cycleData['date_debut'] ?? now()),
                    'payload' => $cycleData,
                ]);
                $cycleMap[$batchCycle->cycle_uid] = $batchCycle->id;
            }

            // 2. Enregistrement des collectes temporaires
            foreach ($request->input('collectes', []) as $colData) {
                
                $cleMapping = $colData['cycle_uid'] ?? $colData['cycle_id'];

                SyncBatchCollecte::create([
                    'sync_batch_id' => $batch->id,
                    // On récupère l'ID du cycle de batch grâce à notre map renforcée
                    'sync_batch_cycle_id' => $cycleMap[$cleMapping] ?? null,
                    'collecte_uid' => $colData['collecte_uid'],
                    'cycle_uid'    => $colData['cycle_uid'] ?? null, // Nouveau champ MySQL
                    'cycle_ref'    => $colData['cycle_id'],         // On garde l'ID local pour historique
                    'client_id'    => $colData['client_id'],
                    'agent_id'     => $agent->id,
                    'montant'      => $colData['montant'],
                    'pointage'     => $colData['pointage'] ?? 1,
                    'date_saisie'  => $this->normalizeDateTime($colData['date_saisie'] ?? now()),
                    'payload'      => $colData,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'batch' => $this->serializeBatch($batch)], 202);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sync Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    public function finalizeBatch(SyncBatch $batch, ?int $adminId = null): void
 {
        $adminId = $adminId ?: auth()->id();
        
        // Sécurité : on ne traite que les batches en attente
        if ($batch->status !== 'pending_review') return;

        DB::transaction(function () use ($batch, $adminId) {
            $now = now();
            $mappedCycleIds = [];

            // --- MISE À JOUR DU PIN_HASH APRÈS APPROBATION ---
            if (!empty($batch->agents) && is_array($batch->agents)) {
                foreach ($batch->agents as $agentData) {
                    $id = data_get($agentData, 'id');
                    $hash = data_get($agentData, 'pin_hash');

                    if ($id && $hash) {
                        Agent::where('id', $id)->update([
                            'pin_hash' => $hash,
                            'updated_at' => $now
                        ]);
                    }
                }
            }
            // --- 2. TRAITEMENT DES CYCLES ---
            $cyclesDataFromBatch = data_get($batch, 'cycles', []);
            if (!empty($cyclesDataFromBatch) && is_iterable($cyclesDataFromBatch)) {
                foreach ($cyclesDataFromBatch as $bCycle) {
                    $cycleUid = data_get($bCycle, 'cycle_uid');
                    if (!$cycleUid) continue;

                    $cycle = Cycle::updateOrCreate(
                        ['cycle_uid' => $cycleUid],
                        [
                            'carnet_id'             => data_get($bCycle, 'carnet_id'),
                            'agent_id'              => data_get($bCycle, 'agent_id'),
                            'client_id'             => data_get($bCycle, 'client_id'),
                            'montant_journalier'    => data_get($bCycle, 'montant_journalier'),
                            'nombre_jours_objectif' => data_get($bCycle, 'nombre_jours_objectif'),
                            'statut'                => data_get($bCycle, 'statut'),
                            'date_debut'            => Carbon::parse(data_get($bCycle, 'date_debut'))->toDateString(),
                            'date_fin_prevue'       => data_get($bCycle, 'date_fin_prevue') 
                                                        ? Carbon::parse(data_get($bCycle, 'date_fin_prevue'))->toDateString() 
                                                        : null,
                        ]
                    );

                    // On stocke l'ID interne pour l'étape des collectes
                    $mappedCycleIds[$cycleUid] = $cycle->id;

                    // --- DÉTECTION ET GÉNÉRATION DE COMMISSION ---
                    if ($cycle->statut === 'termine' && !$cycle->commission_genere) {
                        $cycle->load('carnet'); 
                        if ($cycle->carnet) {
                            Bonus::create([
                                'agent_id'         => $cycle->agent_id,
                                'cycle_id'         => $cycle->id, 
                                'montant'          => $cycle->montant_journalier ?? 0, 
                                'statut'           => 'en_attente',
                                'motif'            => "Commission Automatique - Cycle #" . $cycle->id, 
                                'date_attribution' => $now,
                                'commission_genere'=> false,
                            ]);
                            $cycle->update(['commission_genere' => true]);
                        }
                    }
                }
            }

            // --- 3. TRAITEMENT DES COLLECTES (Bulk Upsert) ---
            $collectesDataFromBatch = data_get($batch, 'collectes', []);
            $insertData = [];

            if (!empty($collectesDataFromBatch) && is_iterable($collectesDataFromBatch)) {
                foreach ($collectesDataFromBatch as $bCol) {
                    $uidAchercher = data_get($bCol, 'cycle_uid');
                    
                    // On cherche l'ID soit dans la map qu'on vient de créer, soit en DB
                    $cycleId = $mappedCycleIds[$uidAchercher] ?? Cycle::where('cycle_uid', $uidAchercher)->value('id');

                    if (!$cycleId) {
                        \Log::error("Cycle introuvable pour l'UID : " . $uidAchercher);
                        continue;
                    }

                    $insertData[] = [
                        'collecte_uid' => data_get($bCol, 'collecte_uid'),
                        'cycle_id'     => $cycleId,
                        'cycle_uid'    => $uidAchercher,
                        'client_id'    => data_get($bCol, 'client_id'),
                        'agent_id'     => data_get($bCol, 'agent_id'),
                        'montant'      => data_get($bCol, 'montant'),
                        'pointage'     => data_get($bCol, 'pointage'),
                        'date_saisie'  => data_get($bCol, 'date_saisie'),
                        'sync_uuid'    => $batch->sync_uuid,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }
            }

            if (!empty($insertData)) {
                // Upsert gère les doublons automatiquement sur la colonne 'collecte_uid'
                Collecte::upsert(
                    $insertData, 
                    ['collecte_uid'], 
                    ['montant', 'pointage', 'updated_at']
                );
            }

            // --- 4. HISTORIQUE ET FINALISATION ---
            SyncHistory::create([
                'agent_id'      => $batch->agent_id,
                'sync_uuid'     => $batch->sync_uuid,
                'nb_collectes'  => $batch->nb_collectes,
                'nb_cycles'     => $batch->nb_cycles,
                'total_montant' => $batch->total_montant,
                'status'        => 'success',
            ]);

            $batch->update([
                'status'       => 'approved',
                'validated_at' => $now,
                'validated_by' => $adminId
            ]);

            // On libère l'agent pour sa prochaine synchronisation
            if ($batch->agent) {
                $batch->agent->update(['can_sync' => true]);
            }
        });
    }

    private function buildSyncPayload($agent): array
    {
        // 1. Récupération des clients (Optimisé avec Eager Loading)
        $clients = Client::where('agent_id', $agent->id)
            ->whereHas('carnets', function($query) {
                $query->where('statut', 'actif')->where('type', 'tontine');
            })
            ->get();

        $clientIds = $clients->pluck('id');

        // 2. Récupération des carnets
        $carnets = Carnet::whereIn('client_id', $clientIds)
            ->where('statut', 'actif')
            ->where('type', 'tontine')
            ->withCount(['cycles as total_cycles_termines' => function($query) {
                $query->where('statut', 'termine');
            }])
            ->get();

        // 3. Récupération des cycles avec relations nécessaires
        $cycles = Cycle::whereIn('carnet_id', $carnets->pluck('id'))
            ->where(function($query) {
                $query->where('statut', 'en_cours')
                    ->orWhere(function($q) {
                        $q->where('statut', 'termine')
                        ->whereNull('retire_at'); 
                    });
            })
            ->with(['collectes', 'retraits'])
            ->get()
            ->map(function($cycle) {
                // Calcul du Net disponible
                $totalCollectes = (float) $cycle->collectes->sum('montant');
                $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
                $commission = (float) ($cycle->montant_journalier ?? 0);

                $cycle->solde_restant_net = max(0, $totalCollectes - $commission - $totalDejaRetire);
                
                // INDISPENSABLE : On injecte le cycle_uid dans chaque retrait 
                // pour faciliter le filtrage côté JavaScript dans Dexie
                $cycle->retraits->each(function($retrait) use ($cycle) {
                    $retrait->cycle_uid = $cycle->cycle_uid;
                    $retrait->synced = 1; 
                });

                return $cycle;
            });
            // 4. Bonus et Commissions en attente (Pas encore associés à un paiement)
            $bonusEnAttente = Bonus::where('agent_id', $agent->id)
                ->where('statut', 'en_attente')
                ->orderBy('date_attribution', 'desc')
                ->get();


            $historiquePaiements = Paiement::where('agent_id', $agent->id)
                ->with(['bonuses']) // Relation pour charger les lignes de bonus payées
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            // 6. Extraction à plat pour Dexie
            $collectes = $cycles->pluck('collectes')->flatten()->map(function($col) {
                $col->synced = 1; 
                return $col;
            });

            // On récupère les retraits déjà marqués avec synced et cycle_uid
            $retraits = $cycles->pluck('retraits')->flatten(); 

            return [
                'success' => true,
                'agent' => [
                    'id'       => $agent->id,
                    'nom'      => $agent->nom,
                    'pin_hash' => $agent->pin_hash,
                ],
                'clients'     => $clients,
                'carnets'     => $carnets,
                // makeHidden libère de la bande passante en évitant les doublons imbriqués
                'cycles'      => $cycles->makeHidden(['collectes', 'retraits']), 
                'collectes'   => $collectes,
                'retraits'    => $retraits, 
                'bonus_en_attente' => $bonusEnAttente->toArray(),
                'historique_paiements' => $historiquePaiements->toArray(),
                'server_date' => now()->format('Y-m-d'),
            ];
        }

    private function normalizeDateTime($value): string
    {
        return Carbon::parse($value)->toDateTimeString();
    }

    private function serializeBatch($batch) {
        return ['id' => $batch->id, 'sync_uuid' => $batch->sync_uuid, 'status' => $batch->status];
    }

    private function batchMessage($batch) {
        return $batch->status === 'approved' ? 'Validé' : 'En attente';
    }

    public function batchStatus($syncUuid)
    {
        $batch = SyncBatch::query()
            ->where('sync_uuid', $syncUuid)
            ->first();

        if (!$batch) {
            return response()->json(['status' => 'not_found'], 200);
        }

        $batch = $batch->fresh(); 
        $agent = $batch->agent;

        // Préparation de la réponse de base
        $response = [
            'batch' => [
                'status' => $batch->status,
                'sync_uuid' => $batch->sync_uuid
            ],
            'status' => $batch->status
        ];

        // CRITIQUE : Si c'est approuvé, on ajoute les données fraîches pour le téléphone
        if ($batch->status === 'approved') {
            $response['data'] = $this->buildSyncPayload($agent);
        }

        return response()->json($response, 200);
    }
}