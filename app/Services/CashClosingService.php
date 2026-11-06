<?php
namespace App\Services;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\MouvementCaisse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashClosingService
{
    private const CASH_PAYMENT_MODES = [
        'especes', 'Especes', 'ESPECES',
        'espèces', 'Espèces', 'ESPÈCES',
        'espece', 'Espece', 'ESPECE',
        'espèce', 'Espèce', 'ESPÈCE',
    ];

    /**
     * Calcule le bilan théorique de la session active.
     */
    public function calculateSummary(CashSession $session): array
    {
        $query = MouvementCaisse::where('cash_session_id', $session->id);

        $cashQuery = (clone $query)->whereIn('mode_paiement', self::CASH_PAYMENT_MODES);
        $nonCashQuery = (clone $query)->whereNotIn('mode_paiement', self::CASH_PAYMENT_MODES);

        $totalEntrees = (clone $cashQuery)->where('sens', 'entree')->sum('montant');
        $totalSorties = (clone $cashQuery)->where('sens', 'sortie')->sum('montant');
        $totalEntreesHorsEspeces = (clone $nonCashQuery)->where('sens', 'entree')->sum('montant');
        $totalSortiesHorsEspeces = (clone $nonCashQuery)->where('sens', 'sortie')->sum('montant');

        $soldeTheorique = $session->fond_caisse_initial + $totalEntrees - $totalSorties;

        return [
            'fond_initial'               => $session->fond_caisse_initial,
            'total_entrees'              => $totalEntrees,
            'total_sorties'              => $totalSorties,
            'total_entrees_hors_especes' => $totalEntreesHorsEspeces,
            'total_sorties_hors_especes' => $totalSortiesHorsEspeces,
            'solde_theorique'            => $soldeTheorique,
        ];
    }

    public function assertSufficientCash(CashSession $session, float $amount): void
    {
        $summary = $this->calculateSummary($session);
        $available = (float) $summary['solde_theorique'];

        if ($amount > $available) {
            throw new \DomainException(
                'Fonds espèces insuffisants. Solde théorique : '
                . number_format($available, 0, ',', ' ')
                . ' FCFA. Approvisionnez la caisse avant cette opération.'
            );
        }
    }

    /**
     * Clôture définitivement la session et enregistre le bilan.
     */
    public function closeSession(CashSession $session, float $soldePhysique, ?string $commentaire = null): CashClosing
    {
        return DB::transaction(function () use ($session, $soldePhysique, $commentaire) {
            $session = CashSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->statut === 'fermee') {
                throw new \Exception("Cette session de caisse est déjà fermée.");
            }

            $summary = $this->calculateSummary($session);
            $ecart   = round($soldePhysique - $summary['solde_theorique'], 2);

            if ($ecart !== 0.0 && trim((string) $commentaire) === '') {
                throw new \InvalidArgumentException('Une justification est obligatoire lorsqu’un écart est constaté.');
            }

            $closing = CashClosing::create([
                'ulid'                => strtolower((string) Str::ulid()),
                'user_id'             => $session->user_id,
                'cash_session_id'     => $session->id,
                'fond_caisse_initial' => $summary['fond_initial'],
                'total_entrees'       => $summary['total_entrees'],
                'total_sorties'       => $summary['total_sorties'],
                'total_entrees_hors_especes' => $summary['total_entrees_hors_especes'],
                'total_sorties_hors_especes' => $summary['total_sorties_hors_especes'],
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
