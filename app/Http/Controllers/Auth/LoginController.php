<?php

namespace App\Http\Controllers\Auth; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // Affiche le login Admin
    public function showAdminLogin() {
        return view('auth.admin-login');
    }

    // Affiche le login Agent
    public function showAgentLogin() {
        return view('auth.agent-login');
    }

    // Connexion Admin (Email)
    public function adminLogin(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            if (Auth::user()->type === 'admin') {
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard'); 
            }
            Auth::logout();
            return back()->withErrors(['email' => 'Accès réservé aux administrateurs.']);
        }
        return back()->withErrors(['email' => 'Identifiants incorrects.']);
    }

    // Connexion Agent (Matricule)
   // Connexion Agent (Matricule)
public function agentLogin(Request $request) {
    $credentials = $request->validate([
        'username' => ['required'], 
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->remember)) {
        $user = Auth::user();
        
        if ($user->type === 'agent') {
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                // 1. Récupérer l'agent
                $agent = \App\Models\Agent::where('user_id', $user->id)->first();
                
                // 2. Récupérer les clients de cet agent
                $clients = \App\Models\Client::where('agent_id', $user->id)->get();
                $clientIds = $clients->pluck('id');

                // 3. Récupérer les carnets de ces clients
                $carnets = \App\Models\Carnet::whereIn('client_id', $clientIds)->get();
                
                // 4. RÉCUPÉRER LES CYCLES ACTIFS (Crucial pour le pointage rapide !)
                $cycles = \App\Models\Cycle::whereIn('carnet_id', $carnets->pluck('id'))
                                        ->where('statut', 'actif')
                                        ->get();

                return response()->json([
                    'agent' => [
                        'id' => $agent->id,
                        'nom' => $agent->nom,
                        'matricule' => $agent->code_agent,
                        'photo' => $agent->image,
                        'actif' => $agent->actif,
                        'sync' => $agent->can_sync
                    ],
                    'clients' => $clients,
                    'carnets' => $carnets,
                    'cycles'  => $cycles // On envoie les cycles pour que Dexie sache quoi afficher
                ], 200);
            }

            return redirect()->route('pwa.index');
        }
        Auth::logout();
        return $request->expectsJson() 
            ? response()->json(['message' => 'Accès réservé aux agents.'], 403)
            : back()->withErrors(['username' => 'Accès réservé aux agents.']);
    }

    return $request->expectsJson()
        ? response()->json(['message' => 'Matricule ou mot de passe incorrect.'], 422)
        : back()->withErrors(['username' => 'Matricule ou mot de passe incorrect.']);
}

    // Déconnexion
    public function logout(Request $request) {
        $type = Auth::user() ? Auth::user()->type : 'admin';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = ($type === 'agent') 
            ? redirect()->route('agent.login') 
            : redirect()->route('admin.login');

        // Ajouter des headers pour empêcher le cache
        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
}