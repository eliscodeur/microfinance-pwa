<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BonusController extends Controller
{
    /**
     * Affiche la liste des bonus avec filtres.
     */
    public function index(Request $request)
    {
        $query = Bonus::with(['agent', 'admin']);

        // Filtre par agent
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Filtre par plage de dates
        if ($request->filled('date_debut')) {
            $query->whereDate('date_attribution', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_attribution', '<=', $request->date_fin);
        }

        // Filtre par motif (recherche partielle)
        if ($request->filled('motif')) {
            $query->where('motif', 'like', '%' . $request->motif . '%');
        }

        // Pagination en conservant les filtres dans l'URL
        $bonuses = $query->orderBy('date_attribution', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        // Liste des agents pour le select des filtres et de la modale
        $agents = Agent::orderBy('nom')->get();

        return view('admin.bonuses.index', compact('bonuses', 'agents'));
    }

    /**
     * Enregistre un nouveau bonus (Attribution manuelle).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id'         => 'required|exists:agents,id',
            'montant'          => 'required|numeric|min:0',
            'motif'            => 'required|string|max:255',
            'date_attribution' => 'required|date',
        ]);

        // On associe l'administrateur connecté
        $validated['admin_id'] = Auth::id();

        try {
            Bonus::create($validated);
            return redirect()->route('admin.bonuses.index')
                             ->with('success', 'Le bonus a été attribué avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Erreur lors de l\'attribution : ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Supprimer un bonus (Optionnel, utile pour les erreurs de saisie).
     */
    public function destroy($id)
    {
        $bonus = Bonus::findOrFail($id);
        
        // Empêcher la suppression des commissions générées automatiquement par le système si besoin
        // if (str_contains($bonus->motif, 'Commission')) {
        //     return back()->with('error', 'Impossible de supprimer une commission automatique.');
        // }

        $bonus->delete();

        return redirect()->back()->with('success', 'Le bonus a été supprimé.');
    }

    // Les méthodes create, show, edit et update sont souvent inutiles pour des bonus 
    // car on préfère généralement supprimer et recréer en cas d'erreur.
}