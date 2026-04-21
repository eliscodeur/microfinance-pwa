<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, \Closure $next, string $role)
    {
        // On vérifie si l'utilisateur est connecté et si son rôle correspond
        // Assure-toi que ton modèle User a une relation 'role' et que la table roles a un champ 'nom'
        if (auth()->check() && auth()->user()->role->nom === $role) {
            return $next($request);
        }

        abort(403, "Accès refusé : Vous n'êtes pas autorisé.");
    }
}
