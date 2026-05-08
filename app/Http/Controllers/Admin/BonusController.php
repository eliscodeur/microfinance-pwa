<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\Agent;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BonusController extends Controller
{
    /**
     * Affiche la liste des bonus groupés par agent (Non payés).
     */
    public function index(Request $request)
    {
        // On récupère uniquement ce qui n'est pas lié à un paiement (paiement_id est NULL)
        $bonusesByAgent = Bonus::whereNull('paiement_id')
            ->with('agent')
            ->get()
            ->groupBy('agent_id')
            ->map(function ($group) {
                return (object) [
                    'agent_id'          => $group->first()->agent_id,
                    'agent'             => $group->first()->agent,
                    'items'             => $group,
                    'total_commissions' => $group->where('commission_genere', 1)->sum('montant'),
                    'total_manuels'     => $group->where('commission_genere', 0)->sum('montant'),
                    'total_global'      => $group->sum('montant'),
                    'nb_items'          => $group->count()
                ];
            });

        $agents = Agent::orderBy('nom')->get();

        return view('admin.bonuses.index', compact('bonusesByAgent', 'agents'));
    }

    /**
     * Enregistre un nouveau bonus manuel (Attente de paiement).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id'         => 'required|exists:agents,id',
            'montant'          => 'required|numeric|min:0',
            'motif'            => 'required|string|max:255',
            'date_attribution' => 'required|date',
        ]);

        $validated['admin_id'] = Auth::id();
        $validated['commission_genere'] = 0; 

        try {
            Bonus::create($validated);
            return redirect()->route('admin.bonuses.index')
                             ->with('success', 'Le bonus a été enregistré en attente de paiement.');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Erreur : ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Valider TOUTES les commissions d'un agent et générer UN reçu de paiement.
     */
    public function bulkApprove(Request $request)
    {
        $request->validate(['agent_id' => 'required|exists:agents,id']);

        // Récupérer les bonus non payés
        $bonusesToPay = Bonus::where('agent_id', $request->agent_id)
            ->whereNull('paiement_id')
            ->get();

        if ($bonusesToPay->isEmpty()) {
            return back()->with('error', 'Aucun élément à payer pour cet agent.');
        }

        $totalMontant = $bonusesToPay->sum('montant');

        try {
            DB::transaction(function () use ($request, $totalMontant, $bonusesToPay) {
                // 1. Créer le reçu de Paiement global
                $paiement = Paiement::create([
                    'agent_id'      => $request->agent_id,
                    'montant_total' => $totalMontant,
                    'reference'     => 'PAY-' . strtoupper(Str::random(8)) . '-' . date('Ymd'),
                    'validated_by'  => Auth::id(),
                ]);

                // 2. Lier les bonus au paiement et marquer la date
                Bonus::whereIn('id', $bonusesToPay->pluck('id'))->update([
                    'paiement_id'  => $paiement->id,
                    'validated_at' => now(),
                    'validated_by' => Auth::id(),
                ]);
            });

            return back()->with('success', "Paiement de " . number_format($totalMontant, 0, ',', ' ') . " F validé avec succès.");

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du paiement : ' . $e->getMessage());
        }
    }

    /**
     * Valider un seul élément et générer un reçu spécifique.
     */
    public function approveSingle($id)
    {
        $bonus = Bonus::whereNull('paiement_id')->findOrFail($id);

        try {
            DB::transaction(function () use ($bonus) {
                // 1. Créer le reçu pour cette ligne unique
                $paiement = Paiement::create([
                    'agent_id'      => $bonus->agent_id,
                    'montant_total' => $bonus->montant,
                    'reference'     => 'PAY-S-' . strtoupper(Str::random(5)) . '-' . date('Ymd'),
                    'validated_by'  => Auth::id(),
                ]);

                // 2. Mise à jour du bonus
                $bonus->update([
                    'paiement_id'  => $paiement->id,
                    'validated_at' => now(),
                    'validated_by' => Auth::id(),
                ]);
            });

            return back()->with('success', 'Élément payé individuellement.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un bonus en attente.
     */
    public function rejectSingle($id)
    {
        $bonus = Bonus::findOrFail($id);
        
        // Sécurité : Interdire la suppression si c'est déjà payé
        if ($bonus->paiement_id) {
            return back()->with('error', 'Impossible de supprimer un bonus déjà payé.');
        }

        $bonus->delete();
        return back()->with('info', 'L\'élément a été supprimé.');
    }
    /**
     * Affiche l'historique des paiements effectués.
     */
    public function history()
    {
        $paiements = Paiement::with(['agent', 'bonuses'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.bonuses.history', compact('paiements'));
    }
}