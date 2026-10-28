<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Services\CashClosingService;
use Illuminate\Http\Request;

class CashClosingController extends Controller
{
    protected $closingService;

    public function __construct(CashClosingService $closingService)
    {
        $this->closingService = $closingService;
    }

    /**
     * Affiche l'interface de clôture avec le résumé théorique en temps réel.
     */
    public function index()
    {
        $session = CashSession::where('user_id', auth()->id())
            ->where('statut', 'ouverte')
            ->first();

        if (! $session) {
            return redirect()->route('admin.caisse.ouvrir')
                ->with('error', 'Aucune session de caisse active trouvée. Veuillez d\'abord ouvrir votre caisse.');
        }

        $summary = $this->closingService->calculateSummary($session);

        return view('admin.caisse.cloture', compact('summary', 'session'));
    }

    /**
     * Traite la clôture définitive de la caisse.
     */
    public function store(Request $request)
    {
        $request->validate([
            'solde_physique' => 'required|numeric|min:0',
            'commentaire'    => 'nullable|string|max:500',
        ]);

        $session = CashSession::where('user_id', auth()->id())
            ->where('statut', 'ouverte')
            ->first();

        if (! $session) {
            return redirect()->route('admin.caisse.ouvrir')
                ->with('error', 'Impossible de clôturer : aucune session active.');
        }

        try {
            $closing = $this->closingService->closeSession(
                $session,
                (float) $request->input('solde_physique'),
                $request->input('commentaire')
            );

            return redirect()->route('admin.caisse.journal')
                ->with('success', 'Clôture de caisse effectuée avec succès ! Écart constaté : ' . number_format($closing->ecart, 0, ',', ' ') . ' FCFA');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la clôture : ' . $e->getMessage())->withInput();
        }
    }
}
