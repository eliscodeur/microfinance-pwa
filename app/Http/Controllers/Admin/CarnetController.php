<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carnet;
use App\Models\Client;
use App\Models\Depot;
use App\Models\Retrait;
use App\Models\Cycle;
use App\Models\CategoryTontine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarnetController extends Controller
{
    /**
     * Affiche la liste des carnets et les données pour le formulaire.
     */
    public function index(Request $request)
    {
        try {
            $query = Carnet::with([
                'client', 
                'categoryTontine', 
                'parent', 
                'cycles.collectes', 
                'credits' => function($q) {
                    $q->where('statut', 'active');
                }          
            ])->withCount('cycles');
            
            // --- RECHERCHE ---
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero', 'LIKE', "%$search%")
                    ->orWhereHas('client', function($sq) use ($search) {
                        $sq->where('nom', 'LIKE', "%$search%")
                            ->orWhere('prenom', 'LIKE', "%$search%")
                            ->orWhere('telephone', 'LIKE', "%$search%")
                            ->orWhere(\DB::raw("CONCAT(nom, ' ', prenom)"), 'LIKE', "%$search%");
                    });
                });
            }

            // --- FILTRE ÉTAT ---
            if ($request->filled('filter')) {
                if ($request->filter == 'vierge') {
                    $query->has('cycles', '=', 0);
                } elseif ($request->filter == 'actif') {
                    $query->has('cycles', '>', 0);
                }
            }

            // --- GESTION DES TOTAUX & ONGLETS ---
            // On récupère le type actuel pour savoir quel onglet afficher (tontine par défaut)
            $currentType = $request->input('type', 'tontine');

            // On clone la requête pour obtenir les comptes totaux par type AVANT de paginer
            $totalTontines = (clone $query)->where('type', 'tontine')->count();
            $totalComptes = (clone $query)->where('type', 'compte')->count();
            $totalGeneral = $totalTontines + $totalComptes;

            // On applique le filtre de type pour la liste paginée
            $query->where('type', $currentType);

            // Exécution de la pagination
            $carnets = $query->latest()->paginate(20)->withQueryString();

            // Données pour les formulaires
            $categories = CategoryTontine::all();
            $clients = Client::select('id', 'nom', 'prenom')->orderBy('nom')->get();
            
            $tontinesActives = Carnet::where('type', 'tontine')
                ->where('statut', 'actif')
                ->with('client')
                ->get();

            return view('admin.carnets.index', compact(
                'carnets', 
                'clients', 
                'categories', 
                'tontinesActives',
                'currentType',
                'totalTontines',
                'totalComptes',
                'totalGeneral'
            ));

        } catch (\Exception $e) {
            \Log::error("Erreur base de données Carnets : " . $e->getMessage());
            return redirect()->back()->with('error', "Une erreur est survenue.");
        }
    }

    /**
     * Enregistre un nouveau carnet.
     * Le numéro est généré automatiquement dans le modèle Carnet (méthode booted).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:tontine,compte',
            'date_debut' => 'required|date',
            'category_tontine_id' => 'required_if:type,tontine',
            'parent_id'           => 'nullable|exists:carnets,id',
        ], [
            'client_id.required' => 'Le client est obligatoire.',
            'category_tontine_id.required_if' => 'Veuillez choisir une catégorie pour la tontine.',
        ]);
        \DB::beginTransaction();
        try {
            // Le statut est forcé ici, le numéro est géré par le modèle
            $validated['statut'] = 'actif';

            $carnet = Carnet::create($validated);
                \DB::commit();
            return redirect()->route('admin.carnets.index')
                     ->with('success', "Carnet " . $carnet->numero . " créé avec succès.");

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un carnet existant.
     */
    public function update(Request $request, $id)
    {
        $carnet = Carnet::findOrFail($id);
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:tontine,compte',
            'date_debut' => 'required|date',
            'category_tontine_id' => 'required_if:type,tontine',
            'parent_id'           => 'nullable|exists:carnets,id',
        ], [
            'client_id.required' => 'Le client est obligatoire.',
            'category_tontine_id.required_if' => 'Veuillez choisir une catégorie pour la tontine.',
        ]);

        \DB::beginTransaction();
        try {
            $data = $request->all();
            // LOGIQUE DE SÉCURITÉ :
            if ($request->type === 'tontine') {
                $data['parent_id'] = null; // On casse le lien si c'est plus un compte épargne
            } else {
                $data['category_tontine_id'] = null; // On retire la catégorie si c'est un compte
            }
            $carnet->update($data);

            \DB::commit();
            return redirect()->route('admin.carnets.index')->with('success', 'Carnet mis à jour');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Supprime un carnet.
     */
    public function destroy(Carnet $carnet)
    {
        try {
            $carnet->delete();
            return redirect()->back()->with('success', 'Le carnet vierge a été supprimé.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    public function getTontinesByClient($clientId)
    {
        $id = (int) $clientId;
        $tontines = Carnet::where('client_id', $id)
                        ->where('type', 'tontine')
                        ->where('statut', 'actif')
                        ->get(['id', 'numero']);

        return response()->json($tontines); 
    }

    public function getCarnetsByClient($clientId)
    {
        try {
            $id = (int) $clientId;
            $allCarnets = Carnet::where('client_id', $id)->where('statut', 'actif')->count();
            \Log::info("Client {$id} has {$allCarnets} active carnets total");

            $carnets = Carnet::with([
                'categoryTontine',
                'parent',
                'cycles.collectes',
                'depots',
                'retraits'
            ])
                            ->where('client_id', $id)
                            ->where('statut', 'actif')
                            ->whereDoesntHave('credits', function ($query) {
                                $query->where('statut', 'active');
                            })
                            ->get();

            \Log::info("Client {$id} has " . $carnets->count() . " eligible carnets for credit");

            $carnets = $carnets->map(function (Carnet $carnet) {
                $category = $carnet->categoryTontine;
                $requiredPointages = $category ? $category->minimumPointagesRequired() : null;
                $totalPointages = $carnet->totalPointages();
                $availableSavings = $carnet->availableSavings();
                $guaranteeBase = $carnet->guaranteeBase();

                return [
                    'id' => $carnet->id,
                    'numero' => $carnet->numero,
                    'type' => $carnet->type,
                    'category' => $category ? $category->libelle : null,
                    'nombre_cycles' => $category ? $category->nombre_cycles : null,
                    'required_pointages' => $requiredPointages,
                    'total_pointages' => $totalPointages,
                    'available_savings' => $availableSavings,
                    'guarantee_base' => $guaranteeBase,
                    'linked_tontine' => $carnet->parent ? [
                        'id' => $carnet->parent->id,
                        'numero' => $carnet->parent->numero,
                    ] : null,
                ];
            });

            return response()->json($carnets);
        } catch (\Exception $e) {
            \Log::error('Error in getCarnetsByClient: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $carnet = Carnet::with([
            'client', 
            // Pour les Tontines
            'cycles.collectes', 
            'cycles.agent', 
            'cycles.retraits.admin', 
            // Pour les Comptes Épargne (Dépôts libres)
            'depots.user', 
            // Pour les Retraits globaux (si applicables)
            'retraits.admin',
            // Pour les Crédits
            'credits'
        ])->findOrFail($id);

        return view('admin.carnets.show', compact('carnet'));
    }

    public function storeDepot(Request $request)
    {
        $validated = $request->validate([
            'carnet_id'   => 'required|exists:carnets,id',
            'client_id'   => 'required|exists:clients,id',
            'montant'     => 'required|numeric|min:1',
            'date_depot'  => 'required|date',
            'commentaire' => 'nullable|string',
        ]);

        // On récupère le carnet pour vérification
        $carnet = Carnet::findOrFail($request->carnet_id);

        // Création du dépôt
        Depot::create([
            'client_id'   => $validated['client_id'],
            'carnet_id'   => $validated['carnet_id'],
            'user_id'     => auth()->id(), // L'admin connecté
            'montant'     => $validated['montant'],
            'date_depot'  => $validated['date_depot'],
            'commentaire' => $validated['commentaire'],
            'cycle_id'    => null, // Toujours null car c'est un dépôt d'épargne (compte)
        ]);

        return redirect()->back()->with('success', 'Le dépôt d\'épargne a été enregistré avec succès.');
    }

    public function storeRetrait(Request $request)
    {
        // 1. Validation : cycle_id est bien nullable pour l'épargne
        $request->validate([
            'carnet_id'     => 'required|exists:carnets,id',
            'client_id'     => 'required|exists:clients,id',
            'cycle_id'      => 'nullable|exists:cycles,id', 
            'montant_total' => 'required|numeric|min:0',
            'date_retrait'  => 'required|date',
            'type_retrait'  => 'required|in:partiel,total',
            'note'          => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                
                $carnet = Carnet::findOrFail($request->carnet_id);
                $montantNetSaisi = (float) $request->montant_total;
                $commissionAppliquee = 0;
                $soldeDisponible = 0;
                $cycle = null;

                // 2. Logique différenciée selon le type de carnet
                if ($carnet->type === 'tontine') {
                    // Pour la tontine, le cycle_id est obligatoire
                    if (!$request->cycle_id) {
                        throw new \Exception("Veuillez sélectionner un cycle pour un carnet de tontine.");
                    }

                    $cycle = Cycle::with(['collectes', 'retraits'])->findOrFail($request->cycle_id);
                    
                    $totalCollecte = (float) $cycle->collectes->sum('montant');
                    $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
                    $commissionFixe = (float) ($cycle->montant_journalier ?? 0);
                    
                    // Commission prélevée uniquement au premier retrait du cycle
                    $commissionAppliquee = ($cycle->retraits->count() == 0) ? $commissionFixe : 0;
                    $soldeDisponible = $totalCollecte - $totalDejaRetire - $commissionAppliquee;

                } else {
                    // LOGIQUE COMPTE ÉPARGNE
                    // On utilise le solde global calculé du carnet (Somme Collectes - Somme Retraits)
                    // Assure-toi que l'accessor 'solde_epargne' ou 'solde_disponible' existe dans Carnet.php
                    $soldeDisponible = (float) $carnet->solde_disponible; 
                    $commissionAppliquee = 0; 
                }

                // Vérification de sécurité (marge de 1F pour les arrondis)
                if ($montantNetSaisi > ($soldeDisponible + 1)) {
                    throw new \Exception("Fonds insuffisants. Solde dispo : " . number_format($soldeDisponible, 0, ',', ' ') . " F.");
                }

                // 3. Insertion du retrait (Propre et sécurisée)
                Retrait::create([
                    'carnet_id'     => $request->carnet_id,
                    'client_id'     => $request->client_id,
                    'cycle_id'      => ($carnet->type === 'tontine') ? $request->cycle_id : null,
                    'admin_id'      => auth()->id(),
                    'montant_total' => $montantNetSaisi + $commissionAppliquee,
                    'commission'    => $commissionAppliquee,
                    'montant_net'   => $montantNetSaisi,
                    'date_retrait'  => $request->date_retrait,
                    'note'          => $request->note,
                ]);

                // 4. Gestion de la clôture automatique (Uniquement pour la Tontine)
                if ($carnet->type === 'tontine' && $cycle) {
                    $netTotalAttendu = $totalCollecte - $commissionFixe;
                    $cumulRetraitsNet = $totalDejaRetire + $montantNetSaisi;
                    
                    // On clôture si c'est un retrait total ou si le solde devient quasi nul
                    if ($request->type_retrait === 'total' || $cumulRetraitsNet >= ($netTotalAttendu - 5)) {
                        $cycle->update(['retire_at' => $request->date_retrait]);
                    }
                }

                return redirect()->back()->with('success', "Retrait de " . number_format($montantNetSaisi, 0, ',', ' ') . " F enregistré avec succès.");
            });

        } catch (\Exception $e) {
            // Log de l'erreur pour le debug en cas de besoin
            \Log::error("Erreur Retrait: " . $e->getMessage());
            return redirect()->back()->with('error', "Échec : " . $e->getMessage())->withInput();
        }
    }
}