<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use App\Models\Client;               
use App\Models\Carnet;               
use App\Models\Agent;               
use App\Models\Cycle;
use App\Models\Collecte;
use App\Models\Bonus;
use App\Models\Paiement;
use App\Models\SyncHistory;

class PwaController extends Controller
{
    // Affiche le cockpit (Accueil)
    public function index()
    {
        return response()
            ->view('pwa.index') 
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // Affiche la page de collecte
    public function showCarnets($id = null)
    {
        // On protège aussi cette page contre le cache
        return response()
            ->view('pwa.carnets');
    }

    // Affiche la liste des clients
    public function clients()
    {
        return view('pwa.clients');
    }

    // API de synchronisation initiale
    public function getInitialData() 
    {
        try {
            $user = Auth::user();
            $agent = $user->agent; 

            if (!$agent) {
                return response()->json(['error' => 'Profil agent non trouvé'], 404);
            }

            // 1. VERIFICATION ADMIN
            if (!$agent->can_sync) {
                return response()->json([
                    'error' => 'Synchronisation non autorisée.',
                    'message' => 'Veuillez demander l\'activation à votre administrateur.'
                ], 403);
            }

            // 2. Récupération des clients (uniquement tontine active)
            $clients = Client::where('agent_id', $agent->id)
                ->whereHas('carnets', function($query) {
                    $query->where('type', 'tontine')->where('statut', 'actif');
                })->get();
            
            $clientIds = $clients->pluck('id');

            // 3. Récupération des carnets actifs
            $carnets = Carnet::whereIn('client_id', $clientIds)
                ->where('statut', 'actif')
                ->where('type', 'tontine')
                ->get();
            
            $carnetIds = $carnets->pluck('id');

            // 4. Récupération des cycles avec calcul du solde restant net
            $cycles = Cycle::whereIn('carnet_id', $carnetIds)
                ->where('agent_id', $agent->id)
                ->visibleForAgentSync()
                ->with(['collectes', 'retraits']) // Eager loading pour les calculs
                ->get()
                ->map(function ($cycle) {
                    $cycle->cycle_uid = $cycle->cycle_uid ?: (string) $cycle->id;
                    
                    // Calcul du solde net (Collectes - Commission - Retraits)
                    $totalColl = (float) $cycle->collectes->sum('montant');
                    $totalRetr = (float) $cycle->retraits->sum('montant_net');
                    $commission = (float) ($cycle->montant_journalier ?? 0);
                    
                    $cycle->solde_restant_net = max(0, $totalColl - $commission - $totalRetr);
                    
                    // On injecte le cycle_uid dans les retraits pour Dexie
                    $cycle->retraits->each(function($r) use ($cycle) {
                        $r->cycle_uid = $cycle->cycle_uid;
                        $r->synced = 1;
                    });

                    return $cycle;
                });

            // 5. Extraction des données à plat (Collectes et Retraits)
            $cycleUidMap = $cycles->pluck('cycle_uid', 'id');

            $collectes = $cycles->pluck('collectes')->flatten()->map(function ($c) use ($cycleUidMap) {
                $c->cycle_id = (string) ($cycleUidMap[$c->cycle_id] ?? $c->cycle_id);
                $c->synced = 1;
                return $c;
            });

            $retraits = $cycles->pluck('retraits')->flatten();
            $bonusEnAttente = Bonus::where('agent_id', $agent->id)
                ->where('statut', 'en_attente')
                ->orderBy('date_attribution', 'desc')
                ->get();

            // 🆕 7. Récupération de l'historique des paiements validés (Limité à 10, Nettoyés pour Dexie)
            $historiquePaiements = Paiement::where('agent_id', $agent->id)
                ->with(['bonuses']) // Charge les lignes associées
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            // 6. Historique de synchro
            $existingSync = SyncHistory::where('agent_id', $agent->id)
                ->where('sync_uuid', 'like', 'initial-sync-' . $agent->id . '-%')
                ->exists();

            if (!$existingSync) {
                SyncHistory::create([
                    'agent_id' => $agent->id,
                    'sync_uuid' => 'initial-sync-' . $agent->id . '-' . now()->timestamp,
                    'nb_collectes' => $collectes->count(),
                    'nb_cycles' => $cycles->count(),
                    'total_montant' => 0,
                    'status' => 'success',
                    'ip_address' => request()->ip(),
                ]);
            }

            // 7. Verrouillage et réponse
            $agent->update(['can_sync' => false]); 

            return response()->json([
                'success' => true,
                'agent' => [
                    'id' => $agent->id,
                    'nom' => $agent->nom ?? $user->name,
                    'matricule' => $user->username,
                    'pin_hash' => $agent->pin_hash, // CRITIQUE pour le mode offline
                    'photo' => $agent->image ? (filter_var($agent->image, FILTER_VALIDATE_URL) ? $agent->image : asset('storage/' . $agent->image)) : null,
                ],
                'clients' => $clients,
                'carnets' => $carnets,
                'cycles' => $cycles->makeHidden(['collectes', 'retraits']), 
                'collectes' => $collectes,
                'retraits' => $retraits, 
                'bonus_en_attente' => $bonusEnAttente->toArray(),
                'historique_paiements' => $historiquePaiements->toArray(),
                'server_date' => now()->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePinHash(Request $request)
    {
        $request->validate([
            'matricule' => 'required|string',
            'pin_hash' => 'required|string',
        ]);

        // On cherche l'utilisateur via son matricule (champ username)
        $user = \App\Models\User::where('username', $request->matricule)->first();

        if ($user && $user->agent) {
            $user->agent->update([
                'pin_hash' => $request->pin_hash
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }
    public function lockSync(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agent;

        if ($agent) {
            // On verrouille la synchro immédiatement après le succès
            $agent->update(['can_sync' => false]);
            return response()->json(['message' => 'Synchro verrouillée avec succès'], 200);
        }

        return response()->json(['error' => 'Agent non trouvé'], 404);
    }


        /**
     * Affiche la page de pointage pour un carnet spécifique
     * * @param int $carnetId
     */
    public function pointage($carnetId)
    {
        // 1. On récupère le carnet avec ses relations pour l'affichage initial (si en ligne)
        // Sinon, Blade affichera une structure vide que JS remplira via Dexie
        $carnet = Carnet::with(['client', 'cycles' => function($q) {
            $q->visibleForAgentSync();
        }])->find($carnetId);

        // 2. Préparation des données pour la vue
        // On passe $carnetId explicitement pour que le JS de la page sache quoi chercher dans Dexie
        return response()
            ->view('pwa.pointage', [
                'carnetId' => $carnetId,
                'carnet'   => $carnet // Optionnel : utile pour le premier chargement en ligne
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    public function pointageShell()
    {
        // On ne cherche rien en BDD. On renvoie la vue vide.
        // Le JS récupérera le carnet_id dans l'URL et chargera Dexie.
        return response()
            ->view('pwa.pointage', [
                'carnetId' => null, 
                'carnet'   => null 
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    
    public function showSyncPage()
    {
        return view('pwa.sync'); 
    }
    public function cyclesList()
    { 
        return view('pwa.cycles-list'); 
    }
    public function collectesList()
    { 
        return view('pwa.collectes-list'); 
    }

    public function checkAgentStatus($matricule)
    {
        try {
            // On cherche l'agent par son matricule
            $agent = Agent::where('code_agent', $matricule)->first();

            if (!$agent) {
                return response()->json([
                    'actif' => false,
                    'message' => 'Agent introuvable.'
                ], 404);
            }

            // On retourne l'état de la colonne 'is_active' ou 'status'
            // Adapte 'statut' selon le nom de ta colonne en base de données
            return response()->json([
                'actif' => (bool) $agent->actif, 
                'nom' => $agent->nom
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }


    public function checkSyncPermission(Request $request) 
    {
        // 1. Récupération du matricule depuis la query string (?matricule=...)
        $matricule = $request->query('matricule');
        // 2. Recherche de l'agent par son matricule unique
        $agent = $matricule ? Agent::where('code_agent', $matricule)->first() : null;

        // 3. Retour de la réponse au format JSON attendu par la PWA
        return response()->json([
            'can_sync' => $agent && (bool) $agent->can_sync,
            'debug' => [
                'matricule_recu' => $matricule ?? 'non_fourni',
                'agent_id'       => $agent ? $agent->id : null,
                'value_in_db'    => $agent ? $agent->can_sync : null,
            ],
            'time' => now()->timestamp 
        ]);
    }

    /**
     * Alias ou autre point d'entrée qui redirige vers la même vérification.
     */
    public function checkPermission(Request $request)
    {
        // On passe l'objet $request pour que la query string soit lue correctement
        return $this->checkSyncPermission($request);
    }

    public function showSecurityPin()
    {
        return view('pwa.pin');
    }
    
}
