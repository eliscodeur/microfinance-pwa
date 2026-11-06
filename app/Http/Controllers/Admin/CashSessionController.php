<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Services\CashClosingService;
use App\Services\CashSessionService;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    protected $sessionService;
    protected $closingService;

    public function __construct(CashSessionService $sessionService, CashClosingService $closingService)
    {
        $this->sessionService = $sessionService;
        $this->closingService = $closingService;
    }

    /**
     * Affiche le formulaire d'ouverture de caisse.
     */
    public function create()
    {
        if ($this->sessionService->hasActiveSession(auth()->id())) {
            return redirect()->route('admin.caisse.journal')
                ->with('info', 'Vous avez déjà une session de caisse active.');
        }

        return view('admin.caisse.ouvrir');
    }

    /**
     * Enregistre l'ouverture de la session de caisse.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fond_caisse_initial' => 'required|integer|min:0',
        ]);

        try {
            $this->sessionService->openSession(
                auth()->id(),
                (float) $request->input('fond_caisse_initial')
            );

            return redirect()->route('admin.caisse.journal')
                ->with('success', 'Session de caisse ouverte avec succès !');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function replenish()
    {
        $session = CashSession::where('user_id', auth()->id())
            ->where('statut', 'ouverte')
            ->firstOrFail();
        $summary = $this->closingService->calculateSummary($session);

        return view('admin.caisse.approvisionner', compact('session', 'summary'));
    }

    public function storeReplenishment(Request $request)
    {
        $validated = $request->validate([
            'montant' => 'required|integer|min:1',
            'origine' => 'required|string|max:120',
            'motif' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $movement = $this->sessionService->replenishCash(
                auth()->id(),
                (int) $validated['montant'],
                $validated['origine'],
                $validated['motif'],
                $validated['reference'] ?? null
            );

            return redirect()->route('admin.caisse.journal')
                ->with('success', 'Approvisionnement enregistré. Référence : ' . $movement->reference);
        } catch (\DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }
}
