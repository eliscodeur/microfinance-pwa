<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB; 
use App\Models\ClientAgentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $agents = Agent::paginate(10);
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
        // dd($request->all());
        // return;
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('agents', 'public');
        }
        // 2. Génération du code NEC automatique
        $count = Agent::count() + 1;
        $code = 'NEC-' . str_pad($count, 5, '0', STR_PAD_LEFT);


       // 3. Création de l'Utilisateur (Authentification)
        DB::transaction(function () use ($request, $code, $imagePath) {
            
            // Création du compte utilisateur (Accès PWA)
            $user = User::create([
                'name'     => $request->nom,
                'email'    => $request->email,
                'username' => $code, 
                'password' => Hash::make($request->password),
                'type'     => 'agent',
                'can_sync' => true, 
                'is_active'=> 1,
            ]);

            // Création du profil agent
            Agent::create([
                'user_id'    => $user->id, // Le lien magique entre les deux tables
                'code_agent' => $code,
                'nom'        => $request->nom,
                'telephone'  => $request->telephone,
                'actif'      => 1,
                'image' => $imagePath // si tu gères l'upload ici
            ]);
        });

        return redirect()->route('admin.agents.index')->with('success', "Agent créé ! Identifiant : $code");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $agent = Agent::findOrFail($id);
        $clientsCount = Client::where('agent_id', $agent->id)->count();
        $history = ClientAgentHistory::with('client')->where('agent_id', $agent->id)->orderBy('assigned_at', 'desc')->get();
        return view('admin.agents.show', compact('agent', 'clientsCount', 'history'));
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
        $user = $agent->user;

        $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'telephone' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
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
                'name' => $request->nom,
                'email' => $request->email,
            ]);

            $agent->update([
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'image' => $imagePath
            ]);
        });
        return redirect()->route('admin.agents.index')->with('success', 'Agent modifié');
    }

    public function toggleStatus($id)
    {
        $agent = Agent::findOrFail($id);
        
        // Bascule de l'état
        $agent->actif = !$agent->actif;
        
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
                'actif' => (bool)$agent->actif,
                'nom' => $agent->nom,
                'message' => "Agent $status avec succès."
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
        $hasHistory = \DB::table('collectes')->where('agent_id', $id)->exists();

        if ($hasCurrentClients || $hasHistory) {
            return response()->json([
                'success' => false,
                'message' => "Interdit : Cet agent a un historique d'activité (collectes ou clients). Vous pouvez seulement le désactiver."
            ], 422);
        }

        // Si 0 client et 0 historique -> Suppression autorisée
        if ($agent->user) {
            $agent->user->delete();
        }
        $agent->delete();

        return response()->json([
            'success' => true,
            'message' => "L'agent a été supprimé définitivement."
        ]);
    }

    public function export($format)
    {
        $agents = Agent::all();

        if ($format == 'csv') {
            $filename = 'agents.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($agents) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Nom', 'Téléphone', 'Email', 'Actif']);
                foreach ($agents as $agent) {
                    fputcsv($handle, [
                        $agent->nom,
                        $agent->telephone,
                        $agent->email,
                        $agent->actif ? 'Oui' : 'Non',
                    ]);
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } elseif ($format == 'excel') {
            return Excel::download(new class($agents) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $agents;

                public function __construct($agents)
                {
                    $this->agents = $agents;
                }

                public function collection()
                {
                    return collect($this->agents)->map(function ($agent) {
                        return [
                            'Nom' => $agent->nom,
                            'Téléphone' => $agent->telephone,
                            'Email' => $agent->email,
                            'Actif' => $agent->actif ? 'Oui' : 'Non',
                        ];
                    });
                }

                public function headings(): array
                {
                    return ['Nom', 'Téléphone', 'Email', 'Actif'];
                }
            }, 'agents.xlsx');
        }

        return redirect()->back();
    }

    public function toggleSync($id)
    {
        $agent = Agent::findOrFail($id);
        $agent->can_sync = !$agent->can_sync;
        $agent->save();

        return response()->json([
            'success' => true,
            'can_sync' => $agent->can_sync,
            'agent_name' => $agent->nom
        ]);
    }
}
