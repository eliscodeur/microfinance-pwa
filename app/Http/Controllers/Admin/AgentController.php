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
        // 1. Récupération du terme de recherche
        $search = $request->query('search');

        // 2. Requête filtrée
        $agents = Agent::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('code_agent', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->latest() // Trie par défaut
            ->paginate(10)
            ->withQueryString();

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
        $request->validate([
            'nom'       => 'required|string|max:255',
            'telephone' => 'required',
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

        // Variable déjà en place pour alimenter le tableau de la vue
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
        $agent = Agent::findOrFail($id);
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

        // SI C'EST UN FORMULAIRE CLASSIQUE
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

    public function resetPin(Agent $agent)
    {
        try {
            // On met le pin_hash à null
            $agent->update(['pin_hash' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Le code PIN de l\'agent a été réinitialisé.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation.',
            ], 500);
        }
    }
    // public function export(Request $request, $format)
    // {
    //     // 1. Récupérer le terme de recherche envoyé par l'URL
    //     $search = $request->query('search');

    //     // 2. Appliquer le même filtre que dans la méthode index()
    //     $agents = Agent::query()
    //         ->when($search, function ($query, $search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('nom', 'like', "%{$search}%")
    //                     ->orWhere('code_agent', 'like', "%{$search}%")
    //                     ->orWhere('telephone', 'like', "%{$search}%");
    //             });
    //         })
    //         ->get(); // On utilise get() ici au lieu de all()

    //     // Si aucun agent trouvé après filtrage
    //     if ($agents->isEmpty()) {
    //         return redirect()->back()->with('error', 'Aucun agent trouvé avec ces critères de recherche.');
    //     }

    //     // 3. Export CSV
    //     if ($format == 'csv') {
    //         $filename = 'agents_export_' . date('Y-m-d_His') . '.csv';
    //         $headers  = [
    //             'Content-Type'        => 'text/csv',
    //             'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    //         ];

    //         $callback = function () use ($agents) {
    //             $handle = fopen('php://output', 'w');
    //             fputcsv($handle, ['Nom', 'Code', 'Téléphone', 'Email', 'Actif']);
    //             foreach ($agents as $agent) {
    //                 fputcsv($handle, [
    //                     $agent->nom,
    //                     $agent->code_agent,
    //                     $agent->telephone,
    //                     $agent->email,
    //                     $agent->actif ? 'Oui' : 'Non',
    //                 ]);
    //             }
    //             fclose($handle);
    //         };

    //         return response()->stream($callback, 200, $headers);
    //     }

    //     // 4. Export Excel
    //     elseif ($format == 'excel') {
    //         return Excel::download(new class($agents) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
    //         {
    //             private $agents;
    //             public function __construct($agents)
    //             {$this->agents = $agents;}

    //             public function collection()
    //             {
    //                 return $this->agents->map(function ($agent) {
    //                     return [
    //                         'Nom'       => $agent->nom,
    //                         'Code'      => $agent->code_agent,
    //                         'Téléphone' => $agent->telephone,
    //                         'Email'     => $agent->email,
    //                         'Actif'     => $agent->actif ? 'Oui' : 'Non',
    //                     ];
    //                 });
    //             }

    //             public function headings(): array
    //             {
    //                 return ['Nom', 'Code', 'Téléphone', 'Email', 'Actif'];
    //             }
    //         }, 'agents_export_' . date('Y-m-d') . '.xlsx');
    //     }

    //     return redirect()->back();
    // }

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
