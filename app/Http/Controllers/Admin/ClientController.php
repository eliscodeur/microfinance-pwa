<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Agent;
use App\Models\ClientAgentHistory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
                        })
                        ->orWhereHas('carnets', function ($carnetQuery) use ($search) {
                            $carnetQuery->where('numero', 'like', "%{$search}%")
                                ->orWhere('reference_physique', 'like', "%{$search}%");
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $agents = Agent::all();
        return view('admin.clients.form', compact('agents'));
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
            'nom' => 'required',
            'telephone' => 'required',
            'agent_id' => 'required'
        ]);

        $client = Client::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'agent_id' => $request->agent_id
        ]);

        // Enregistrer dans l'historique
        ClientAgentHistory::create([
            'client_id' => $client->id,
            'agent_id' => $request->agent_id,
            'assigned_at' => now()
        ]);

        return redirect()->route('admin.clients.index')->with('success', 'Client ajouté');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $client = Client::with([
            'agent',
            'agentHistory.agent',
            'carnets.cycles',
        ])->findOrFail($id);
        return view('admin.clients.show', compact('client'));
    }

    public function exportHistory($id)
    {
        $client = Client::with('agentHistory.agent')->findOrFail($id);
        $filename = 'client-' . $client->id . '-agent-history.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($client) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Client', 'Agent', 'Assigned At', 'Unassigned At']);

            foreach ($client->agentHistory as $h) {
                fputcsv($handle, [
                    $client->nom,
                    $h->agent->nom ?? 'Agent supprimé',
                    optional($h->assigned_at)->format('Y-m-d H:i:s'),
                    optional($h->unassigned_at)->format('Y-m-d H:i:s') ?: 'Actuel',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $client = Client::findOrFail($id);
        $agents = Agent::all();
        return view('admin.clients.form', compact('client', 'agents'));
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
        $request->validate([
            'nom' => 'required',
            'telephone' => 'required',
            'agent_id' => 'required'
        ]);

        $client = Client::findOrFail($id);

        // Si l'agent change, mettre à jour l'historique
        if ($client->agent_id != $request->agent_id) {
            // Marquer l'ancienne attribution comme terminée
            $lastHistory = ClientAgentHistory::where('client_id', $id)
                ->whereNull('unassigned_at')
                ->first();
            if ($lastHistory) {
                $lastHistory->update(['unassigned_at' => now()]);
            }
            // Créer une nouvelle attribution
            ClientAgentHistory::create([
                'client_id' => $id,
                'agent_id' => $request->agent_id,
                'assigned_at' => now()
            ]);
        }

        $client->update([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'agent_id' => $request->agent_id
        ]);

        return redirect()->route('admin.clients.index')->with('success', 'Client mis à jour');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
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
