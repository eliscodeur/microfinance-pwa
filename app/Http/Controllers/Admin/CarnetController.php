<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carnet;
use App\Models\Client;
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
            // 1. Initialisation de la requête avec le count des cycles pour le filtre "vierge"
            $query = Carnet::with(['client', 'categoryTontine', 'parent'])
                        ->withCount('cycles');

            // --- FILTRE RECHERCHE (Numéro ou Nom Client) ---
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero', 'LIKE', "%$search%")
                    ->orWhereHas('client', function($sq) use ($search) {
                        $sq->where('nom', 'LIKE', "%$search%")
                            ->orWhere('prenom', 'LIKE', "%$search%");
                    });
                });
            }

            // --- FILTRE TYPE ---
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // --- FILTRE ÉTAT (Basé sur les cycles pour l'instant) ---
            if ($request->filled('filter')) {
                if ($request->filter == 'vierge') {
                    $query->has('cycles', '=', 0);
                } elseif ($request->filter == 'actif') {
                    $query->has('cycles', '>', 0);
                }
            }

            // 2. Exécution avec pagination pour supporter les filtres
            $carnets = $query->latest()->paginate(20)->withQueryString();

            // 3. Récupération des données pour les formulaires (inchangé)
            $categories = CategoryTontine::all();
            $clients = Client::orderBy('nom')->get();
            
            $tontinesActives = Carnet::where('type', 'tontine')
                ->where('statut', 'actif')
                ->with('client')
                ->get();

        } catch (\Exception $e) {
            \Log::error("Erreur base de données Carnets : " . $e->getMessage());

            $carnets = collect();
            $categories = collect();
            $clients = collect();
            $tontinesActives = collect();
            
            return redirect()->back()->with('error', "Erreur : " . $e->getMessage());
        }

        return view('admin.carnets.index', compact(
            'carnets', 
            'clients', 
            'categories', 
            'tontinesActives'
        ));
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
                        ->where('statut', 'actif') // Vérifie bien si c'est 'statut' ou 'status'
                        ->get(['id', 'numero']);

        return response()->json($tontines); 
    }
    public function show($id)
    {
        $carnet = Carnet::with(['client', 'cycles.collectes', 'cycles.agent', 'cycles.retrait.admin'])->findOrFail($id);

        return view('admin.carnets.show', compact('carnet'));
    }
}