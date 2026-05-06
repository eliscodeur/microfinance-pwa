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

class CarnetController extends Controller
{
    /**
     * Affiche la liste des carnets et les données pour le formulaire.
     */
    public function index(Request $request)
    {
        try {
            // 1. Initialisation de la requête
            $query = Carnet::with([
                'client', 
                'categoryTontine', 
                'parent', 
                'cycles.collectes', 
                'credits' => function($q) {
                    $q->where('statut', 'active');
                }          
            ])->withCount('cycles');
            
            // --- FILTRE RECHERCHE (Numéro, Nom, Prénom ou Téléphone) ---
            if ($request->filled('search')) {
                $search = $request->search;
                
                $query->where(function($q) use ($search) {
                    // Recherche par numéro de carnet
                    $q->where('numero', 'LIKE', "%$search%")
                    // Recherche dans la relation Client
                    ->orWhereHas('client', function($sq) use ($search) {
                        $sq->where('nom', 'LIKE', "%$search%")
                            ->orWhere('prenom', 'LIKE', "%$search%")
                            ->orWhere('telephone', 'LIKE', "%$search%")
                            // Optionnel : Recherche combinée "Nom Prénom"
                            ->orWhere(\DB::raw("CONCAT(nom, ' ', prenom)"), 'LIKE', "%$search%");
                    });
                });
            }

            // --- FILTRE TYPE ---
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // --- FILTRE ÉTAT (Basé sur les cycles) ---
            if ($request->filled('filter')) {
                if ($request->filter == 'vierge') {
                    $query->has('cycles', '=', 0);
                } elseif ($request->filter == 'actif') {
                    $query->has('cycles', '>', 0);
                }
            }

            // 2. Exécution avec pagination et conservation des paramètres de filtrage
            $carnets = $query->latest()->paginate(20)->withQueryString();

            // 3. Récupération des données pour les formulaires
            $categories = CategoryTontine::all();
            $clients = Client::orderBy('nom')->get();
            
            $tontinesActives = Carnet::where('type', 'tontine')
                ->where('statut', 'actif')
                ->with('client')
                ->get();

            return view('admin.carnets.index', compact(
                'carnets', 
                'clients', 
                'categories', 
                'tontinesActives'
            ));

        } catch (\Exception $e) {
            \Log::error("Erreur base de données Carnets : " . $e->getMessage());
            return redirect()->back()->with('error', "Une erreur est survenue lors de la récupération des données.");
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
            $carnets = Carnet::with([
                'categoryTontine',
                'parent',
                'cycles.collectes',
                'depots',
                'retraits'
            ])
                            ->where('client_id', $id)
                            ->where('statut', 'actif')
                            ->get();

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
            'cycles.retrait.admin', 
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
        $request->validate([
            'carnet_id' => 'required|exists:carnets,id',
            'montant_total' => 'required|numeric|min:1',
            'date_retrait' => 'required|date',
        ]);

        // Calcul du montant net
        $commission = $request->input('commission', 0);
        $montantNet = $request->montant_total - $commission;

        // Création du retrait
        $retrait = Retrait::create([
            'carnet_id' => $request->carnet_id,
            'client_id' => $request->client_id,
            'cycle_id'  => $request->input('cycle_id'), // Sera null pour un compte épargne
            'admin_id'  => auth()->id(),
            'montant_total' => $request->montant_total,
            'commission'    => $commission,
            'montant_net'   => $montantNet,
            'date_retrait'  => $request->date_retrait,
            'note'          => $request->note,
        ]);

        // Si c'est une tontine liée à un cycle, on marque le cycle comme "retiré"
        if ($request->filled('cycle_id')) {
            $cycle = Cycle::find($request->cycle_id);
            $cycle->update(['retire_at' => $request->date_retrait, 'statut' => 'termine']);
        }

        return redirect()->back()->with('success', 'Retrait effectué et comptabilisé.');
    }
}