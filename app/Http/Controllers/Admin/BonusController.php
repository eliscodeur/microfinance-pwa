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
        // 1. Récupération des bonus/commissions non traités
        // On filtre sur statut 'en_attente' et paiement_id NULL pour la sécurité
        $bonusesByAgent = Bonus::where('statut', 'en_attente')
            ->whereNull('paiement_id')
            ->with(['agent', 'cycle']) // Eager loading pour éviter le problème N+1
            ->get()
            ->groupBy('agent_id')
            ->map(function ($group) {
                // Extraction des informations par agent
                return (object) [
                    'agent_id'          => $group->first()->agent_id,
                    'agent'             => $group->first()->agent,
                    'items'             => $group,
                    
                    // Utilisation de cycle_id pour différencier Commissions et Bonus
                    'total_commissions' => $group->whereNotNull('cycle_id')->sum('montant'),
                    'total_manuels'     => $group->whereNull('cycle_id')->sum('montant'),
                    
                    'total_global'      => $group->sum('montant'),
                    'nb_items'          => $group->count()
                ];
            })
            ->sortByDesc('total_global'); // On affiche les plus gros montants en premier

        // 2. Calcul des statistiques globales pour les badges d'en-tête
        $stats = (object) [
            'montant_total_attente' => $bonusesByAgent->sum('total_global'),
            'nombre_agents_concernes' => $bonusesByAgent->count(),
            'nombre_lignes_total'     => $bonusesByAgent->sum('nb_items'),
        ];

        // 3. Liste exhaustive des agents (pour un formulaire d'ajout manuel de bonus par exemple)
        $agents = Agent::orderBy('nom')->get();

        // 4. Retour vers la vue avec toutes les données nécessaires
        return view('admin.bonuses.index', [
            'bonusesByAgent' => $bonusesByAgent,
            'agents'         => $agents,
            'stats'          => $stats,
            'pageTitle'      => 'Gestion des Commissions et Bonus'
        ]);
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
        $validated['statut'] = 'en_attente'; // Sécurité pour forcer le statut initial

        try {
            Bonus::create($validated);

            // 💡 Ajustement crucial pour SweetAlert2 (Requête AJAX / Fetch)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Le bonus a été enregistré avec succès et mis en attente.'
                ]);
            }

            // Fallback classique (si tu as encore un formulaire normal quelque part)
            return redirect()->route('bonuses.index') // Route corrigée selon ton Route::resource
                            ->with('success', 'Le bonus a été enregistré en attente de paiement.');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()
                ], 500);
            }

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
                    'type'          => 'deboursement',
                    'reference'     => 'PAY-' . strtoupper(Str::random(8)) . '-' . date('Ymd'),
                    'validated_by'  => Auth::id(),
                ]);

                // 2. Lier les bonus au paiement et marquer la date
                Bonus::whereIn('id', $bonusesToPay->pluck('id'))->update([
                    'paiement_id'  => $paiement->id,
                    'statut'       => 'valide',
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
        // On récupère le bonus avec un verrou de ligne (optional mais conseillé en tontine)
        $bonus = Bonus::whereNull('paiement_id')->findOrFail($id);

        try {
            DB::transaction(function () use ($bonus) {
                // 1. Créer le paiement
                $paiement = Paiement::create([
                    'agent_id'      => $bonus->agent_id,
                    'montant_total' => $bonus->montant,
                    'type'          => 'deboursement', 
                    'reference'     => 'PAY-S-' . strtoupper(Str::random(5)) . '-' . date('Ymd'),
                    'validated_by'  => Auth::id(),
                ]);

                // Vérification de sécurité : si l'id n'est pas généré, on stoppe
                if (!$paiement->id) {
                    throw new \Exception("Le paiement n'a pas pu être généré.");
                }

                // 2. Mise à jour du bonus avec l'ID tout juste créé
                // On utilise update sur l'instance ou directement via la requête pour être sûr
                $bonus->paiement_id = $paiement->id;
                $bonus->statut = 'valide'; // Changement d'état : en_attente -> valide
                $bonus->validated_at = now();
                $bonus->validated_by = Auth::id();
                
                // save() est souvent plus explicite que update() quand l'objet est déjà chargé
                $bonus->save();
            });

            return back()->with('success', 'Paiement effectué et bonus mis à jour.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la validation : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un bonus en attente.
     */
    public function rejectSingle($id)
    {
        // On récupère le bonus qui n'a pas encore de paiement associé
        $bonus = Bonus::whereNull('paiement_id')->findOrFail($id);

        try {
            DB::transaction(function () use ($bonus) {
                // 1. Créer une ligne de "trace" dans la table paiements
                // On met le montant à 0 car aucun argent n'est réellement décaissé
                $paiement = Paiement::create([
                    'agent_id'      => $bonus->agent_id,
                    'montant_total' => 0, 
                    'type'          => 'rejet',
                    'reference'     => 'REJ-S-' . strtoupper(Str::random(5)) . '-' . date('Ymd'),
                    'validated_by'  => Auth::id(),
                ]);

                // 2. Mise à jour du bonus au lieu de la suppression
                $bonus->update([
                    'paiement_id'  => $paiement->id,
                    'statut'       => 'refuse', // Changement d'état : en_attente -> refuse
                    'validated_at' => now(),
                    'validated_by' => Auth::id(),
                ]);
            });

            return back()->with('info', 'Le bonus a été marqué comme refusé (archivé).');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du refus : ' . $e->getMessage());
        }
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