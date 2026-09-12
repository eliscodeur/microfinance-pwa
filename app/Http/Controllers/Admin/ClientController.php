<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use App\Models\ClientAgentHistory;
use App\Models\ClientCarnetNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::with(['agent', 'carnets'])
            ->withCount('carnets')
            ->latest()
            ->get();

        $agents = Agent::orderBy('nom')->get(['id', 'nom']);

        return view('admin.clients.index', compact('clients', 'agents'));
    }

    public function create()
    {
        $agents = Agent::all();
        return view('admin.clients.form', compact('agents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'prenom'    => 'required|string|max:255',
            'telephone' => 'required|string',
            'agent_id'  => 'nullable|exists:agents,id',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $data = $validated;
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('clients', 'public');
            }

            $client = Client::create($data);

            if ($request->filled('agent_id')) {
                ClientAgentHistory::create([
                    'client_id'   => $client->id,
                    'agent_id'    => $request->agent_id,
                    'assigned_at' => now(),
                ]);
            }

            return redirect()->route('admin.clients.index')->with('success', 'Client ajouté avec succès.');
        });
    }

    public function show(string $ulid)
    {
        $client = Client::with([

        ])->where('ulid', $ulid)->firstOrFail();

        return view('admin.clients.show', compact('client'));
    }

    public function edit(string $ulid)
    {
        $client = Client::findOrFail($ulid);
        $agents = Agent::all();

        return view('admin.clients.form', compact('client', 'agents'));
    }

    public function update(Request $request, string $ulid)
    {
        $client = Client::findOrFail($ulid);

        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'prenom'    => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'agent_id'  => 'nullable|exists:agents,id',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        return DB::transaction(function () use ($request, $client, $validated) {
            if ($client->agent_id != $request->agent_id) {
                ClientAgentHistory::where('client_id', $client->id)
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => now()]);

                if ($request->filled('agent_id')) {
                    ClientAgentHistory::create([
                        'client_id'   => $client->id,
                        'agent_id'    => $request->agent_id,
                        'assigned_at' => now(),
                    ]);
                }
            }

            $data = $validated;
            if ($request->hasFile('photo') || $request->input('remove_photo') == '1') {
                if ($client->photo) {
                    Storage::disk('public')->delete($client->photo);
                }

                $data['photo'] = $request->hasFile('photo') ? $request->file('photo')->store('clients', 'public') : null;
            }

            $client->update($data);
            return redirect()->route('admin.clients.index')->with('success', 'Fiche client mise à jour.');
        });
    }

    public function destroy(int $id)
    {
        $client = Client::findOrFail($id);

        if ($client->photo) {
            Storage::disk('public')->delete($client->photo);
        }

        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Client supprimé');
    }

    // public function export(Request $request, string $format)
    // {
    //     $exportAll = $request->boolean('all'); // Option pour tout exporter
    //     $search    = trim((string) $request->query('search', ''));
    //     $agentId   = $request->query('agent_id');

    //     $query = Client::with('agent');

    //     // Applique les filtres uniquement si "all" n'est pas demandé
    //     if (! $exportAll) {
    //         $query->when($search !== '', function ($q) use ($search) {
    //             $q->where(function ($subQuery) use ($search) {
    //                 $subQuery->where('nom', 'like', "%{$search}%")
    //                     ->orWhere('prenom', 'like', "%{$search}%")
    //                     ->orWhere('telephone', 'like', "%{$search}%")
    //                     ->orWhere('adresse', 'like', "%{$search}%")
    //                     ->orWhere(DB::raw("CONCAT(nom, ' ', prenom)"), 'like', "%{$search}%");
    //             });
    //         })
    //             ->when($agentId, fn($q) => $q->where('agent_id', $agentId));
    //     }

    //     $clients = $query->get();

    //     if ($clients->isEmpty()) {
    //         return redirect()->back()->with('error', 'Aucun client trouvé pour l\'exportation.');
    //     }

    //     $extension = ($format === 'excel') ? 'xlsx' : 'csv';
    //     $filename  = 'clients_' . ($exportAll ? 'complet_' : 'filtre_') . date('Y-m-d_His') . '.' . $extension;

    //     // --- ENREGISTREMENT DE LA TRACE EN BDD ---
    //     ExportHistory::create([
    //         'user_id'      => auth()->id(),
    //         'module'       => 'clients',
    //         'type_export'  => $format,
    //         'filename'     => $filename,
    //         'filters_used' => $exportAll ? ['mode' => 'export_integral'] : [
    //             'search'   => $search ?: null,
    //             'agent_id' => $agentId ?: null,
    //         ],
    //     ]);

    //     // --- GÉNÉRATION DU FICHIER ---
    //     if ($format == 'csv') {
    //         $headers = [
    //             'Content-Type'        => 'text/csv',
    //             'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    //         ];

    //         $callback = function () use ($clients) {
    //             $handle = fopen('php://output', 'w');
    //             fputcsv($handle, ['Nom', 'Prénom', 'Téléphone', 'Adresse', 'Agent Inscripteur']);

    //             foreach ($clients as $client) {
    //                 fputcsv($handle, [
    //                     $client->nom,
    //                     $client->prenom,
    //                     $client->telephone,
    //                     $client->adresse,
    //                     $client->agent->nom ?? 'Aucun',
    //                 ]);
    //             }
    //             fclose($handle);
    //         };

    //         return response()->stream($callback, 200, $headers);

    //     } elseif ($format == 'excel') {
    //         return Excel::download(new class($clients) implements FromCollection
    //         {
    //             private $clients;

    //             public function __construct($clients)
    //             {
    //                 $this->clients = $clients;
    //             }

    //             public function collection()
    //             {
    //                 $rows = collect($this->clients)->map(function ($client) {
    //                     return [
    //                         $client->nom,
    //                         $client->prenom,
    //                         $client->telephone,
    //                         $client->adresse,
    //                         $client->agent->nom ?? 'Aucun',
    //                     ];
    //                 });

    //                 return $rows->prepend([
    //                     'Nom',
    //                     'Prénom',
    //                     'Téléphone',
    //                     'Adresse',
    //                     'Agent Inscripteur',
    //                 ]);
    //             }
    //         }, $filename); // Utilisation du nouveau nom de fichier dynamique
    //     }

    //     return redirect()->back();
    // }

    public function storeNumCarnet(Request $request)
    {
        $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'type_carnet' => 'required|in:compte,tontine',
            'quantite'    => 'required|integer|min:1|max:10',
        ]);

        $client = Client::findOrFail($request->client_id);

        ClientCarnetNumber::generateForClient(
            $client,
            $request->type_carnet,
            $request->input('quantite', 1)
        );

        return back()->with('success', 'Numéro(s) de carnet généré(s) avec succès.');
    }
}
