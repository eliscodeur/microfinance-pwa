<?php
namespace App\Services;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\MouvementCaisse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashClosingService
{
    /**
     * Calcule le bilan théorique de la session active.
     */
    public function calculateSummary(CashSession $session): array
    {
        $query = MouvementCaisse::where('user_id', $session->user_id)
            ->where('date_mouvement', '>=', $session->date_ouverture);

        if ($session->date_fermeture) {
            $query->where('date_mouvement', '<=', $session->date_fermeture);
        }

        $totalEntrees = (clone $query)->where('sens', 'entree')->sum('montant');
        $totalSorties = (clone $query)->where('sens', 'sortie')->sum('montant');

        $soldeTheorique = $session->fond_caisse_initial + $totalEntrees - $totalSorties;

        return [
            'fond_initial'    => $session->fond_caisse_initial,
            'total_entrees'   => $totalEntrees,
            'total_sorties'   => $totalSorties,
            'solde_theorique' => $soldeTheorique,
        ];
    }

    /**
     * Clôture définitivement la session et enregistre le bilan.
     */
    public function closeSession(CashSession $session, float $soldePhysique, ?string $commentaire = null): CashClosing
    {
        if ($session->statut === 'fermee') {
            throw new \Exception("Cette session de caisse est déjà fermée.");
        }

        return DB::transaction(function () use ($session, $soldePhysique, $commentaire) {

            $summary = $this->calculateSummary($session);
            $ecart   = round($soldePhysique - $summary['solde_theorique'], 2);

            $closing = CashClosing::create([
                'ulid'                => strtolower((string) Str::ulid()),
                'user_id'             => $session->user_id,
                'fond_caisse_initial' => $summary['fond_initial'],
                'total_entrees'       => $summary['total_entrees'],
                'total_sorties'       => $summary['total_sorties'],
                'solde_theorique'     => $summary['solde_theorique'],
                'solde_physique'      => $soldePhysique,
                'ecart'               => $ecart,
                'commentaire'         => $commentaire,
                'date_cloture'        => now(),
            ]);

            // Fermeture de la session
            $session->update([
                'statut'         => 'fermee',
                'date_fermeture' => now(),
            ]);

            return $closing;
        });
    }
}
