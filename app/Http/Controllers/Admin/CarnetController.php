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
    // On utilise try/catch pour éviter que la page blanche ne bloque tout
    try {
        $carnets = \App\Models\Carnet::with(['client', 'categoryTontine'])->latest()->get();
        $categories = \App\Models\CategoryTontine::all();
        $clients = \App\Models\Client::all();
    } catch (\Exception $e) {
        // Si la base de données a un souci, on envoie des collections vides
        $carnets = collect();
        $categories = collect();
        $clients = collect();
    }

    return view('admin.carnets.index', compact('carnets', 'clients', 'categories'));
}

    /**
     * Enregistre un nouveau carnet.
     * Le numéro est généré automatiquement dans le modèle Carnet (méthode booted).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'type'                => 'required|in:tontine,compte',
            'category_tontine_id' => 'required_if:type,tontine|nullable|exists:categories_tontine,id',
            'parent_id'           => 'nullable|exists:carnets,id',
            'date_debut'          => 'required|date',
        ]);

        try {
            Carnet::create($validated);
            return redirect()->route('admin.carnets.index')->with('success', 'Carnet créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Met à jour un carnet existant.
     */
    public function update(Request $request, Carnet $carnet)
    {
        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'type'                => 'required|in:tontine,compte',
            'category_tontine_id' => 'required_if:type,tontine|nullable|exists:categories_tontine,id',
            'parent_id'           => 'nullable|exists:carnets,id',
            'statut'              => 'required|in:actif,clôturé,suspendu',
            'date_debut'          => 'required|date',
        ]);

        $carnet->update($validated);

        return redirect()->route('admin.carnets.index')->with('success', 'Carnet mis à jour avec succès.');
    }

    /**
     * Supprime un carnet.
     */
    public function destroy(Carnet $carnet)
    {
        $carnet->delete();
        return redirect()->route('admin.carnets.index')->with('success', 'Carnet supprimé définitivement.');
    }
}