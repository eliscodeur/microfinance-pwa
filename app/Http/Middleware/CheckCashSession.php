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
            $message = 'Opération bloquée : Votre caisse n\'est pas ouverte. Veuillez ouvrir une session de caisse.';

            if ($request->headers->has('X-Inertia')) {
                return redirect()->back()->with('error', $message);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'      => false,
                    'error_type'   => 'cash_closed',
                    'message'      => $message,
                    'redirect_url' => route('admin.caisse.ouvrir'),
                ], 403);
            }

            return redirect()->route('admin.caisse.ouvrir')
                ->with('error', $message);
        }

        return $next($request);
    }
}
