<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use App\Models\Client;               
use App\Models\Carnet;               
use App\Models\Cycle;
use App\Models\Collecte;
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
        // 1. On récupère l'agent associé
        $agent = $user->agent; 

        if (!$agent) {
            return response()->json(['error' => 'Profil agent non trouvé'], 404);
        }

        // 2. VERIFICATION ADMIN : L'agent a-t-il l'autorisation de synchroniser ?
        if (!$agent->can_sync) {
            return response()->json([
                'error' => 'Synchronisation non autorisée.',
                'message' => 'Veuillez demander l\'activation à votre administrateur.'
            ], 403);
        }

        // 3. Récupération des clients (uniquement ceux qui ont des carnets)
        $clients = Client::where('agent_id', $agent->id)
            ->whereHas('carnets')
            ->get();
        $clientIds = $clients->pluck('id');

        // 4. Récupération des carnets actifs
        $carnets = Carnet::whereIn('client_id', $clientIds)
            ->where('statut', 'actif')
            ->get();
        $carnetIds = $carnets->pluck('id');

        // 5. Récupération des cycles (Crucial pour tes calculs de PWA)
        $cycles = Cycle::whereIn('carnet_id', $carnetIds)
            ->where('agent_id', $agent->id)
            ->visibleForAgentSync()
            ->get()
            ->map(function ($cycle) {
                $cycle->cycle_uid = $cycle->cycle_uid ?: (string) $cycle->id;
                return $cycle;
            })
            ->values();
            
        $agent->update(['can_sync' => false]);  
        // 6. VERROUILLAGE AUTOMATIQUE ET HISTORIQUE DE SYNCHRO UNIQUEMENT POUR LA PREMIÈRE FOIS
        $existingSync = SyncHistory::where('agent_id', $agent->id)
            ->where('sync_uuid', 'like', 'initial-sync-' . $agent->id . '-%')
            ->exists();

        if (!$existingSync) {
            SyncHistory::create([
                'agent_id' => $agent->id,
                'sync_uuid' => 'initial-sync-' . $agent->id . '-' . now()->timestamp,
                'nb_collectes' => 0,
                'nb_cycles' => $cycles->count(),
                'total_montant' => 0,
                'status' => 'success',
                'ip_address' => request()->ip(),
            ]);

             
        }

        $cycleUidMap = $cycles->mapWithKeys(function ($cycle) {
            return [$cycle->id => $cycle->cycle_uid];
        });

        $collectes = Collecte::whereIn('cycle_id', $cycles->pluck('id'))
            ->get()
            ->map(function ($collecte) use ($cycleUidMap) {
                $collecte->cycle_id = (string) ($cycleUidMap[$collecte->cycle_id] ?? $collecte->cycle_id);
                return $collecte;
            })
            ->values();

        return response()->json([
            'success' => true,
            'agent' => [
                'nom' => $agent->nom ?? $user->name,
                'matricule' => $user->username,
                'photo' => $agent->image ? (filter_var($agent->image, FILTER_VALIDATE_URL) ? $agent->image : asset('storage/' . $agent->image))
                : null,
            ],
            'clients' => $clients,
            'carnets' => $carnets,
            'cycles' => $cycles,
            'collectes' => $collectes,
            'server_date' => now()->format('Y-m-d'),
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        
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
        // Sécurité : si l'agent a déjà synchronisé, on le renvoie au dashboard

        // L'écran de synchro reste accessible pour les envois manuels.
        return view('pwa.sync'); 
    }

    public function checkSyncPermission() 
    {
        $user = auth()->user();
        $agent = $user->agent;

        return response()->json([
            'can_sync' => $agent && (bool) $agent->can_sync,
            'debug' => [
                'user_id' => $user->id,
                'agent_id' => $agent ? $agent->id : 'null',
                'value_in_db' => $agent ? $agent->can_sync : 'null',
            ],
            'time' => now()->timestamp 
        ]);
    }

    public function checkPermission()
    {
        return $this->checkSyncPermission();
    }
    
}
