<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CashSessionService;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    protected $sessionService;

    public function __construct(CashSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
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

        $dernierSolde = 0;

        return view('admin.caisse.ouvrir', compact('dernierSolde'));
    }

    /**
     * Enregistre l'ouverture de la session de caisse.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fond_caisse_initial' => 'required|numeric|min:0',
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
}
