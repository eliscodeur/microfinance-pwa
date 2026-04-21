<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Gère une requête entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  (C'est ici que 'admin' ou 'agent' arrive)
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Vérifier si l'utilisateur est bien connecté
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        // 2. Vérifier si le type de l'utilisateur correspond au rôle requis
        // (On compare avec la colonne 'type' de ta table users)
        if (strtolower(Auth::user()->type) === strtolower($role)) {
            return $next($request);
        }

        // 3. Si le rôle ne correspond pas, on renvoie un HTTP 403
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Accès non autorisé : rôle incompatible.'], 403);
        }

        abort(403, 'Accès non autorisé : rôle incompatible.');
    }
}