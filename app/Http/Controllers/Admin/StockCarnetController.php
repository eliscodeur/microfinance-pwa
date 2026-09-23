<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryTontine;
use App\Models\MouvementStockCarnets;
use Illuminate\Http\Request;

class StockCarnetController extends Controller
{

    public function index()
    {
        $categories = CategoryTontine::all();

        // Récupérer uniquement les entrées
        $entrees = MouvementStockCarnets::with('categoryTontine')
            ->where('type', 'entree')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page_entrees');

        // Récupérer uniquement les sorties
        $sorties = MouvementStockCarnets::with('categoryTontine')
            ->where('type', 'sortie')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page_sorties');

        // --- CALCUL DES STOCKS DISPONIBLES (Basé sur quantite_restante) ---

        // 1. Stock des Tontines (là où categories_tontine_id n'est PAS NULL)
        $stockTontines = MouvementStockCarnets::whereNotNull('categories_tontine_id')
            ->sum('quantite_restante');

        // 2. Stock des Comptes (là où categories_tontine_id EST NULL)
        $stockComptes = MouvementStockCarnets::whereNull('categories_tontine_id')
            ->sum('quantite_restante');

        // 3. Total Général en réserve
        $stockTotal = $stockTontines + $stockComptes;

        return view('admin.stocks.index', compact(
            'categories',
            'entrees',
            'sorties',
            'stockTontines',
            'stockComptes',
            'stockTotal'
        ));
    }

    // Enregistrer une entrée (achat / arrivage)
    public function storeEntree(Request $request)
    {
        $request->validate([
            'type_carnet'           => 'required|in:compte,tontine',
            'categories_tontine_id' => 'required_if:type_carnet,tontine|nullable|exists:categories_tontine,id',
            'quantite'              => 'required|integer|min:1',
            'prix_unitaire_achat'   => 'required|numeric|min:0',
            'motif'                 => 'nullable|string|max:255',
        ]);

        $categorieId = $request->type_carnet === 'compte' ? null : $request->categories_tontine_id;

        MouvementStockCarnets::create([
            'categories_tontine_id' => $categorieId,
            'type'                  => 'entree',
            'quantite'              => $request->quantite,
            'prix_unitaire_achat'   => $request->prix_unitaire_achat,
            'motif'                 => $request->motif ?? 'Arrivage / Achat de carnets',
        ]);

        return redirect()->route('admin.stocks.index')->with('success', 'Entrée de stock enregistrée avec succès.');
    }

}
