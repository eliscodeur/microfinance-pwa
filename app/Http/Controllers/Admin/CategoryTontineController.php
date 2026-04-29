<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryTontine; 
use Illuminate\Http\Request;

class CategoryTontineController extends Controller
{
    // Affichage de la liste
    public function index()
    {
        $categories = CategoryTontine::orderBy('created_at', 'desc')->get();
        return view('admin.categorie-tontine.index', compact('categories'));
    }

    // Insertion (depuis le modal d'ajout)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'libelle' => 'required|string|max:255|unique:categories_tontine,libelle',
                'prix' => 'required|numeric|min:0',
                'nombre_cycles' => 'required|integer|min:1',
                'description' => 'nullable|string',
            ], [
                'libelle.unique' => 'Ce nom de catégorie existe déjà.',
                'libelle.required' => 'Le libellé est obligatoire.',
            ]);

            CategoryTontine::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Catégorie ajoutée avec succès.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation (doublon, champ vide)
            return response()->json([
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Erreur serveur grave (SQL, faute de frappe, etc.)
            Log::error("Erreur ajout catégorie: " . $e->getMessage());
            return response()->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Mise à jour (depuis le modal de modification)
    public function update(Request $request, $id)
    {
        try {
            // 1. Validation avec exception pour l'ID actuel
            $validated = $request->validate([
                // On dit à 'unique' d'ignorer l'ID de la catégorie qu'on modifie
                'libelle' => 'required|string|max:255|unique:categories_tontine,libelle,' . $id,
                'prix' => 'required|numeric|min:0',
                'nombre_cycles' => 'required|integer|min:1',
                'description' => 'nullable|string',
            ], [
                'libelle.unique' => 'Désolé, ce libellé est déjà utilisé par une autre catégorie.',
                'libelle.required' => 'Le libellé ne peut pas être vide.',
            ]);

            // 2. Récupération et mise à jour
            $category = CategoryTontine::findOrFail($id);
            $category->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Catégorie mise à jour avec succès !'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreurs de validation (ex: le libellé existe déjà ailleurs)
            return response()->json([
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Erreur fatale (ex: ID inexistant ou problème SQL)
            return response()->json([
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    // Suppression
    public function destroy($id)
    {
        try {
            $category = CategoryTontine::findOrFail($id);
            $category->delete();

            // On renvoie du JSON pour que le script AJAX sache que c'est bon
            return response()->json([
                'status' => 'success',
                'message' => 'Catégorie supprimée avec succès.'
            ], 200);

        } catch (\Exception $e) {
            // En cas d'erreur (ex: catégorie liée à des transactions existantes)
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer cette catégorie : ' . $e->getMessage()
            ], 500);
        }
    }
}