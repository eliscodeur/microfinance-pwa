<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\Collecte;
use App\Models\Cycle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollecteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cycle_id' => 'required|exists:cycles,id',
            'montant'  => 'required|numeric|min:1',
            'pointage' => 'required|integer|min:1',
        ]);

        // Récupérer le cycle actif concerné
        $cycle = Cycle::where('id', $request->cycle_id)
            ->where('statut', 'en_cours')
            ->first();

        if (! $cycle) {
            return back()->with('error', 'Aucun cycle actif trouvé pour cette collecte.');
        }

        // 1. Contrôle : Vérifier si le cumul des pointages dépasse l'objectif
        $totalPointagesActuels = $cycle->collectes()->sum('pointage');
        $nouveauTotal          = $totalPointagesActuels + $request->pointage;
        $objectifMax           = $cycle->nombre_jours_objectif ?? 31;

        if ($nouveauTotal > $objectifMax) {
            $restants = max(0, $objectifMax - $totalPointagesActuels);
            return back()->with('error', "Impossible d'enregistrer cette collecte. Il ne reste que {$restants} pointage(s) possible(s) pour ce cycle.");
        }

        // 2. Enregistrement de la collecte
        Collecte::create([
            'collecte_uid' => Str::uuid(),
            'cycle_id'     => $cycle->id,
            'client_id'    => $cycle->client_id,
            'agent_id'     => $cycle->agent_id,
            'user_id'      => auth()->id(),
            'pointage'     => $request->pointage,
            'montant'      => $request->montant,
            'date_saisie'  => now(),
            'cycle_uid'    => $cycle->cycle_uid,
        ]);

        // 3. Gestion de la commission et du bonus (créé une seule fois pour la première collecte du cycle)
        $dejaUnBonus = Bonus::where('cycle_id', $cycle->id)->exists();

        if (! $dejaUnBonus) {
            $montantCommission = $cycle->calculerCommission();

            $cycle->update([
                'commission_genere' => $montantCommission,
            ]);

            // Créer le bonus pour l'agent (uniquement si aucun n'existe encore pour ce cycle)
            Bonus::create([
                'agent_id' => $cycle->agent_id,
                'cycle_id' => $cycle->id,
                'montant'  => $montantCommission,
                'statut'   => 'en_attente',
                'motif'    => "Commission collecte — Cycle #{$cycle->id}",
                'date_attribution' => now(),
            ]);
        }
        if ($nouveauTotal == $objectifMax) {
            $cycle->update([
                'statut'              => 'termine',
                'completed_at'        => now(),
                'date_cloture_reelle' => now(),
            ]);
        }

        return back()->with('swal_success', 'Collecte enregistrée avec succès !');
    }
}
