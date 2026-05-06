<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Collecte;
use App\Models\Cycle;
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
            'sync_uuid' => 'required|string',
            'cycles' => 'nullable|array',
            'collectes' => 'nullable|array',
        ]);

        $agent = auth()->user()->agent;
        if (!$agent || !$agent->can_sync) {
            return response()->json(['success' => false, 'message' => 'Sync interdite ou agent introuvable.'], 403);
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
        if ($batch->status !== 'pending_review') return;

        DB::transaction(function () use ($batch, $adminId) {
            $now = now();
            $mappedCycleIds = [];

            // --- ÉTAPE 1 : CYCLES ---
            foreach ($batch->cycles as $bCycle) {
                $cycle = Cycle::updateOrCreate(
                    ['cycle_uid' => $bCycle->cycle_uid],
                    [
                        'carnet_id' => $bCycle->carnet_id,
                        'agent_id' => $bCycle->agent_id,
                        'client_id' => $bCycle->client_id,
                        'montant_journalier' => $bCycle->montant_journalier,
                        'nombre_jours_objectif' => $bCycle->nombre_jours_objectif,
                        'statut' => $bCycle->statut,
                        'date_debut' => Carbon::parse($bCycle->date_debut)->toDateString(),
                        'date_fin_prevue' => $bCycle->date_fin_prevue ? Carbon::parse($bCycle->date_fin_prevue)->toDateString() : null,
                    ]
                );
                $mappedCycleIds[$bCycle->cycle_uid] = $cycle->id;
                // --- DÉTECTION ET GÉNÉRATION DE COMMISSION ---
                // On vérifie si le cycle vient de passer à 'termine' et n'a pas encore été traité
                // --- DÉTECTION ET GÉNÉRATION DE COMMISSION ---
                if ($cycle->statut === 'termine' && !$cycle->commission_genere) {
                    // On charge explicitement la relation carnet pour avoir accès au montant
                    $cycle->load('carnet'); 

                    if ($cycle->carnet) {
                        \App\Models\Bonus::create([
                            'agent_id'          => $cycle->agent_id,
                            'cycle_id'          => $cycle->id, 
                            'montant'           => $cycle->montant_journalier ?? 0, 
                            'motif'             => "Commission Automatique - Cycle #" . $cycle->id, 
                            'date_attribution'  => $now,
                            'commission_genere' => false,
                            'validated_by'      => null, 
                            'admin_id'          => null
                        ]);

                        $cycle->update(['commission_genere' => true]);
                    }
                }
            }

            // --- ÉTAPE 2 : COLLECTES ---
          
            
        // foreach ($batch->collectes as $bCol) {
        //     // 1. UTILISER LE cycle_uid DU DUMP (celui qui commence par f8db89b9...)
        //     $uidAchercher = $bCol->cycle_uid; 

        //     // 2. Chercher l'ID interne MySQL
        //     $cycleId = Cycle::where('cycle_uid', $uidAchercher)->value('id');

        //     // 3. LOG DE SÉCURITÉ (si ça ne marche toujours pas, tu verras pourquoi)
        //     if (!$cycleId) {
        //         \Log::error("Cycle introuvable pour l'UID : " . $uidAchercher);
        //         continue;
        //     }

        //     // 4. INSERTION FINALE
        //     Collecte::updateOrCreate(
        //         ['collecte_uid' => $bCol->collecte_uid],
        //         [
        //             'cycle_id'    => $cycleId, 
        //             'cycle_uid'   => $uidAchercher, 
        //             'client_id'   => $bCol->client_id,
        //             'agent_id'    => $bCol->agent_id,
        //             'montant'     => $bCol->montant,
        //             'pointage'    => $bCol->pointage,
        //             'date_saisie' => $bCol->date_saisie,
        //             'sync_uuid'   => $batch->sync_uuid,
        //         ]
        //     );
        // }
            // --- ÉTAPE 2 : COLLECTES (Optimisée en Bulk) ---
            $collectesData = [];
            $now = now();

            foreach ($batch->collectes as $bCol) {
                $uidAchercher = $bCol->cycle_uid;
                $cycleId = $mappedCycleIds[$uidAchercher] ?? Cycle::where('cycle_uid', $uidAchercher)->value('id');

                if (!$cycleId) {
                    Log::error("Cycle introuvable pour l'UID : " . $uidAchercher);
                    continue;
                }

                // On prépare le tableau pour l'insertion massive
                $collectesData[] = [
                    'collecte_uid' => $bCol->collecte_uid,
                    'cycle_id'     => $cycleId,
                    'cycle_uid'    => $uidAchercher,
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

            // Insertion en une seule fois (par paquets de 50 pour la sécurité)
            if (!empty($collectesData)) {
                Collecte::upsert(
                    $collectesData, 
                    ['collecte_uid'], // Colonne d'unicité
                    ['montant', 'pointage', 'updated_at'] // Colonnes à mettre à jour si doublon
                );
            }
            // --- ÉTAPE 3 : HISTORIQUE ET STATUT ---
            SyncHistory::create([
                'agent_id' => $batch->agent_id,
                'sync_uuid' => $batch->sync_uuid,
                'nb_collectes' => $batch->nb_collectes,
                'nb_cycles' => $batch->nb_cycles,
                'total_montant' => $batch->total_montant,
                'status' => 'success',
            ]);

            $batch->update([
                'status' => 'approved',
                'validated_at' => $now,
                'validated_by' => $adminId
            ]);

            $batch->agent->update(['can_sync' => true]);
        });
    }

    private function buildSyncPayload($agent): array
    {
        // 1. Récupérer les clients de l'agent
        $clients = Client::where('agent_id', $agent->id)
            ->whereHas('carnets', function($query) {
                $query->where('statut', 'actif');
            })
            ->get();

        $clientIds = $clients->pluck('id');

        // 2. Récupérer les carnets actifs (sans calcul de solde ici, Dexie s'en chargera)
        // $carnets = \App\Models\Carnet::whereIn('client_id', $clientIds)
        //     ->where('statut', 'actif')
        //     ->get();
        $carnets = Carnet::whereIn('client_id', $clientIds)
            ->where('statut', 'actif')
            ->withCount(['cycles as total_cycles_termines' => function($query) {
                $query->where('statut', 'termine');
            }])
            ->get();

        // 3. Récupération des cycles : "En cours" OU "Terminé sans retrait"
        $cycles = Cycle::whereIn('carnet_id', $carnets->pluck('id'))
            ->where(function($query) {
                $query->where('statut', 'en_cours')
                    ->orWhere(function($q) {
                        $q->where('statut', 'termine')
                            ->whereNull('retire_at'); // L'admin n'a pas encore validé le retrait
                    });
            })
            ->with('collectes') // On prend les collectes pour que Dexie les ait
            ->get();

        // 4. Extraction des collectes à plat pour Dexie
        $collectes = $cycles->pluck('collectes')->flatten()->map(function($col) {
            $col->synced = 1; 
            return $col;
        });

        return [
            'success' => true,
            'agent' => [
                'nom' => $agent->nom, 
                'id' => $agent->id
            ],
            'clients'     => $clients,
            'carnets'     => $carnets,
            // On masque la relation pour ne pas doubler le poids du JSON
            'cycles'      => $cycles->makeHidden('collectes'), 
            'collectes'   => $collectes,
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