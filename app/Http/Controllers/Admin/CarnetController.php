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
    public function index()
    {
        try {
            // 1. Récupération des données avec relations pour éviter le problème N+1
            $carnets = Carnet::with(['client', 'categoryTontine'])->latest()->get();
            $categories = CategoryTontine::all();
            $clients = Client::orderBy('nom')->get();

            // 2. Récupérer les tontines actives pour le champ "parent_id" du formulaire
            // On suppose que le type est 'tontine' et le statut 'actif'
            $tontinesActives = Carnet::where('type', 'tontine')
                ->where('statut', 'actif') // Assure-toi que ce champ existe en base
                ->with('client')
                ->get();

        } catch (\Exception $e) {
            // Log de l'erreur pour le développeur (toi)
            \Log::error("Erreur base de données Carnets : " . $e->getMessage());

            // Initialisation de collections vides pour éviter les erreurs dans Blade
            $carnets = collect();
            $categories = collect();
            $clients = collect();
            $tontinesActives = collect();
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

        try {
            // Le statut est forcé ici, le numéro est géré par le modèle
            $validated['statut'] = 'actif';

            $carnet = Carnet::create($validated);

            return redirect()->route('admin.carnets.index')
                     ->with('success', "Carnet " . $carnet->numero . " créé avec succès.");

        } catch (\Exception $e) {
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

        try {
            $data = $request->all();
            // LOGIQUE DE SÉCURITÉ :
            if ($request->type === 'tontine') {
                $data['parent_id'] = null; // On casse le lien si c'est plus un compte épargne
            } else {
                $data['category_tontine_id'] = null; // On retire la catégorie si c'est un compte
            }
            $carnet->update($data);

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
}