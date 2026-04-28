<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryTontine;
use Illuminate\Http\Request;

class CategoryTontineController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle'       => 'required|string|max:255|unique:categories_tontine,libelle',
            'nombre_cycles' => 'required|integer|min:1',
        ]);

        CategoryTontine::create($validated);
        return redirect()->back()->with('success', 'Catégorie créée !');
    }

    /**
     * Pour l'édition, on peut soit retourner une vue, 
     * soit simplement l'ID pour le gérer en JS (comme pour tes carnets).
     */
    public function edit(CategoryTontine $categoryTontine)
    {
        return response()->json($categoryTontine);
    }

    /**
     * Mise à jour de la catégorie.
     */
    public function update(Request $request, CategoryTontine $categoryTontine)
    {
        $validated = $request->validate([
            // On ignore l'ID actuel pour la règle "unique" lors de la modification
            'libelle'       => 'required|string|max:255|unique:categories_tontine,libelle,' . $categoryTontine->id,
            'nombre_cycles' => 'required|integer|min:1',
        ]);

        $categoryTontine->update($validated);

        return redirect()->back()->with('success', 'Catégorie mise à jour !');
    }

    /**
     * Suppression sécurisée.
     */
    public function destroy(CategoryTontine $categoryTontine)
    {
        // On empêche la suppression si la catégorie est déjà utilisée
        if ($categoryTontine->carnets()->count() > 0) {
            return redirect()->back()->with('error', 'Action impossible : Cette catégorie est utilisée par des carnets.');
        }

        $categoryTontine->delete();
        return redirect()->back()->with('success', 'Catégorie supprimée.');
    }
}