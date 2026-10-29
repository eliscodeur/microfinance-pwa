<?php
namespace App\Http\Middleware;

use App\Services\CashSessionService;
use Closure;
use Illuminate\Http\Request;

class CheckCashSession
{
    protected $sessionService;

    public function __construct(CashSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && ! $this->sessionService->hasActiveSession(auth()->id())) {

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'      => false,
                    'error_type'   => 'cash_closed',
                    'message'      => 'Opération bloquée : Votre caisse n\'est pas ouverte. Veuillez ouvrir une session de caisse.',
                    'redirect_url' => route('admin.caisse.ouvrir'),
                ], 403);
            }

            return redirect()->route('admin.caisse.ouvrir')
                ->with('error', 'Vous devez ouvrir votre caisse avant d\'effectuer des opérations financières.');
        }

        return $next($request);
    }
}
