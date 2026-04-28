<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Agent;
use App\Models\ClientAgentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $agentId = $request->query('agent_id');

        $clients = Client::with(['agent', 'carnets'])
            ->withCount('carnets')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nom', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%")
                        ->orWhere('adresse', 'like', "%{$search}%")
                        ->orWhereHas('agent', function ($agentQuery) use ($search) {
                            $agentQuery->where('nom', 'like', "%{$search}%");
                        });
                });
            })
            ->when($agentId, function ($query) use ($agentId) {
                $query->where('agent_id', $agentId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $agents = Agent::orderBy('nom')->get(['id', 'nom']);

        return view('admin.clients.index', compact('clients', 'agents', 'search', 'agentId'));
    }

    public function create()
    {
        $agents = Agent::all();
        return view('admin.clients.form', compact('agents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string',
            'agent_id' => 'required|exists:agents,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'date_naissance' => 'nullable|date',
        ]);

        $data = $request->all();

        // Gestion de l'image
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('clients', 'public');
        }

        $client = Client::create($data);

        // Historique d'attribution
        ClientAgentHistory::create([
            'client_id' => $client->id,
            'agent_id' => $request->agent_id,
            'assigned_at' => now()
        ]);

        return redirect()->route('admin.clients.index')->with('success', 'Client ajouté avec succès');
    }

    public function show($id)
    {
        $client = Client::with([
            'agent',
            'agentHistory.agent',
            'carnets.cycles',
        ])->findOrFail($id);
        return view('admin.clients.show', compact('client'));
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        $agents = Agent::all();
        // dd($client->photo);
        return view('admin.clients.form', compact('client', 'agents'));
    }

    public function update(Request $request, $id)
    {
    $request->validate([
        // Champs obligatoires
        'nom' => 'required|string|max:255',
        'telephone' => 'required|string|max:20',
        'agent_id' => 'required|exists:agents,id',
        // Informations personnelles (optionnelles)
        'date_naissance' => 'nullable|date',
        'lieu_naissance' => 'nullable|string|max:255',
        'genre' => 'nullable|in:masculin,féminin,autre',
        'statut_matrimonial' => 'nullable|string|max:100',
        'nationalite' => 'nullable|string|max:100',
        'profession' => 'nullable|string|max:255',
        'adresse' => 'nullable|string|max:255',

        // Photo
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

        // Personne de référence
        'reference_nom' => 'nullable|string|max:255',
        'reference_telephone' => 'nullable|string|max:20',
    ]);

        $client = Client::findOrFail($id);
        $data = $request->all();

        // 1. Gestion du changement d'agent (Historique)
        if ($client->agent_id != $request->agent_id) {
            ClientAgentHistory::where('client_id', $id)
                ->whereNull('unassigned_at')
                ->update(['unassigned_at' => now()]);

            ClientAgentHistory::create([
                'client_id' => $id,
                'agent_id' => $request->agent_id,
                'assigned_at' => now()
            ]);
        }

        // 2. Gestion de la suppression d'image (via la croix)
        if ($request->input('remove_photo') == '1') {
            if ($client->photo) {
                Storage::disk('public')->delete($client->photo);
                $data['photo'] = null;
            }
        }

        // 3. Gestion de l'upload d'une nouvelle image
        if ($request->hasFile('photo')) {
            // On supprime l'ancienne s'il y en a une
            if ($client->photo) {
                Storage::disk('public')->delete($client->photo);
            }
            $data['photo'] = $request->file('photo')->store('clients', 'public');
        }

        $client->update($data);

        return redirect()->route('admin.clients.index')->with('success', 'Fiche client mise à jour');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        
        // Supprimer la photo physiquement si elle existe
        if ($client->photo) {
            Storage::disk('public')->delete($client->photo);
        }

        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Client supprimé');
    }

    public function export($format)
    {
        $clients = Client::with('agent')->get();
        if ($format == 'csv') {
            $filename = 'clients.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',

            ];

            $callback = function () use ($clients) {

                $handle = fopen('php://output', 'w');

                fputcsv($handle, ['Nom', 'Téléphone', 'Adresse', 'Agent']);

                foreach ($clients as $client) {

                    fputcsv($handle, [

                        $client->nom,

                        $client->telephone,

                        $client->adresse,

                        $client->agent->nom ?? 'N/A',

                    ]);

                }

                fclose($handle);

            };



            return response()->stream($callback, 200, $headers);

        } elseif ($format == 'excel') {

            return Excel::download(new class($clients) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {

                private $clients;



                public function __construct($clients)

                {

                    $this->clients = $clients;

                }



                public function collection()

                {

                    return collect($this->clients)->map(function ($client) {

                        return [

                            'Nom' => $client->nom,

                            'Téléphone' => $client->telephone,

                            'Adresse' => $client->adresse,

                            'Agent' => $client->agent->nom ?? 'N/A',

                        ];

                    });

                }



                public function headings(): array

                {

                    return ['Nom', 'Téléphone', 'Adresse', 'Agent'];

                }

            }, 'clients.xlsx');

        }



        return redirect()->back();

    }
}