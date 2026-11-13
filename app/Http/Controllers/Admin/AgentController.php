<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Carnet;
use App\Models\CarnetAgentHistory;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource with filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $agents = Agent::all();
        return view('admin.agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.agents.form');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->merge([
            'telephone' => str_replace([' ', '+228'], '', $request->telephone),
        ]);

        $request->validate([
            'nom'       => 'required|string|max:255',
            'telephone' => 'required|digits:8',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:4',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('agents', 'public');
        }
        // 2. Génération du code NEC automatique
        $code = Agent::generateNecCode();
        // 3. Création de l'Utilisateur (Authentification)
        DB::transaction(function () use ($request, $code, $imagePath) {

            // Création du compte utilisateur (Accès PWA)
            $user = User::create([
                'name'      => $request->nom,
                'email'     => $request->email,
                'username'  => $code,
                'password'  => Hash::make($request->password),
                'type'      => 'agent',
                'can_sync'  => true,
                'is_active' => 1,
            ]);

            // Création du profil agent
            Agent::create([
                'user_id'    => $user->id,
                'code_agent' => $code,
                'nom'        => $request->nom,
                'telephone'  => $request->telephone,
                'actif'      => 1,
                'image'      => $imagePath, // si tu gères l'upload ici
            ]);
        });

        return redirect()->route('admin.agents.index')->with('success', "Agent créé ! Identifiant : $code");
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $ulid
     * @return \Illuminate\Http\Response
     */
    public function show(string $ulid)
    {
        $agent = Agent::where('ulid', $ulid)->firstOrFail();

        $carnetsCount = Carnet::where('agent_id', $agent->id)->count();
        $history      = CarnetAgentHistory::with(['carnet.client'])
            ->where('agent_id', $agent->id)
            ->whereNull('unassigned_at')
            ->orderBy('assigned_at', 'desc')
            ->get();
        $avancesList = SalaryAdvance::where('agent_id', $agent->id)
            ->latest()
            ->get();

        return view('admin.agents.show', compact('agent', 'carnetsCount', 'history', 'avancesList'));
    }

    public function getAgentsExceptCurrent(string $historyUlid)
    {
        $history        = CarnetAgentHistory::where('ulid', $historyUlid)->firstOrFail();
        $currentAgentId = $history->agent_id;

        $agents = Agent::where('id', '!=', $currentAgentId)
            ->select('ulid', 'nom', 'code_agent')
            ->orderBy('nom')
            ->get();

        return response()->json($agents);
    }

    // méthode AJAX pour actualiser le graphique sans recharger la page
    public function getChartData(Request $request, int $id)
    {
        $agent  = Agent::findOrFail($id);
        $filter = $request->input('filter', '7_days');

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        switch ($filter) {
            case 'this_month':
                $start = now()->startOfMonth();
                $end   = now()->endOfMonth();
                break;
            case '12_months':
                $start = now()->subMonths(11)->startOfMonth();
                $end   = now()->endOfMonth();
                break;
            case 'custom':
                $start = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subDays(6);
                $end   = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
                break;
            case '7_days':
            default:
                $start = now()->subDays(6);
                $end   = now()->endOfDay();
                break;
        }

        $period = \Carbon\CarbonPeriod::create($start, $end);

        $dates              = [];
        $collectesAgentData = [];
        $gainsAgentData     = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $dates[]       = $date->format('d M');

            $collectesAgentData[] = \App\Models\Collecte::where('agent_id', $agent->id)
                ->whereDate('created_at', $formattedDate)
                ->sum('montant');

            $gainsAgentData[] = \App\Models\Bonus::where('agent_id', $agent->id)
                ->where('statut', 'valide')
                ->whereDate('created_at', $formattedDate)
                ->sum('montant');
        }

        return response()->json([
            'dates'    => $dates,
            'collecte' => $collectesAgentData,
            'gains'    => $gainsAgentData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $agent = Agent::where('ulid', $id)->firstOrFail();

        return view('admin.agents.form', compact('agent'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        $user  = $agent->user;

        $request->validate([
            'nom'       => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'telephone' => 'required',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = $agent->image;
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($agent->image) {
                Storage::disk('public')->delete($agent->image);
            }
            $imagePath = $request->file('image')->store('agents', 'public');
        }

        // Mise à jour de la table User
        DB::transaction(function () use ($request, $agent, $user, $imagePath) {
            $user->update([
                'name'  => $request->nom,
                'email' => $request->email,
            ]);

            $agent->update([
                'nom'       => $request->nom,
                'telephone' => $request->telephone,
                'email'     => $request->email,
                'image'     => $imagePath,
            ]);
        });
        return redirect()->route('admin.agents.index')->with('success', 'Agent modifié');
    }

    public function toggleStatus($id)
    {
        $agent = Agent::findOrFail($id);

        // Bascule de l'état
        $agent->actif = ! $agent->actif;

        // Synchronisation avec l'utilisateur
        if ($agent->user) {
            $agent->user->is_active = $agent->actif;
            $agent->user->save();
        }

        $agent->save();

        $status = $agent->actif ? 'activé' : 'désactivé';

        // SI LA REQUETE EST AJAX (via fetch)
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'actif'   => (bool) $agent->actif,
                'nom'     => $agent->nom,
                'message' => "Agent $status avec succès.",
            ]);
        }

        return redirect()->back()->with('success', "Agent $status avec succès.");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);

        // 1. Vérification : A-t-il des clients ACTUELS ?
        $hasCurrentClients = $agent->clients()->exists();

        // 2. Vérification : A-t-il un HISTORIQUE (collectes ou anciennes attributions) ?
        // On suppose que tu as une table 'collectes' ou 'attributions_history'
        $hasHistory = DB::table('collectes')->where('agent_id', $id)->exists();

        if ($hasCurrentClients || $hasHistory) {
            return response()->json([
                'success' => false,
                'message' => "Interdit : Cet agent a un historique d'activité (collectes ou clients). Vous pouvez seulement le désactiver.",
            ], 422);
        }

        // Si 0 client et 0 historique -> Suppression autorisée
        if ($agent->user) {
            $agent->user->delete();
        }
        $agent->delete();

        return response()->json([
            'success' => true,
            'message' => "L'agent a été supprimé définitivement.",
        ]);
    }

    public function resetPin(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        $actor = $request->user();

        DB::transaction(function () use ($agent, $actor, $request) {
            $hadPin = filled($agent->pin_hash);

            Agent::withoutAuditing(function () use ($agent) {
                $agent->update(['pin_hash' => null]);
            });

            Audit::create([
                'user_type' => $actor->getMorphClass(),
                'user_id' => $actor->getAuthIdentifier(),
                'event' => 'agent_pin_reset',
                'auditable_type' => Agent::class,
                'auditable_id' => $agent->getKey(),
                'old_values' => ['pin_configured' => $hadPin],
                'new_values' => ['pin_configured' => false],
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
                    ? mb_substr($request->userAgent(), 0, 1023)
                    : null,
                'tags' => 'agent,pin_reset',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Le code PIN de l\'agent a été réinitialisé.',
        ]);
    }

    public function toggleSync($id)
    {
        $agent           = Agent::findOrFail($id);
        $agent->can_sync = ! $agent->can_sync;
        $agent->save();

        return response()->json([
            'success'    => true,
            'can_sync'   => $agent->can_sync,
            'agent_name' => $agent->nom,
        ]);
    }
}
