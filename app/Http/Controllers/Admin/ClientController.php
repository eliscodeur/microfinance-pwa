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
use Illuminate\Support\Str;

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
        $agents = Agent::where('actif', true)->orderBy("nom", 'ASC')->get();
        return view('admin.clients.form', compact('agents'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'telephone' => str_replace([' ', '+228'], '', $request->telephone),
        ]);

        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'prenom'    => 'required|string|max:255',
            'telephone' => 'required|digits:8',
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
                    'ulid'        => strtolower((string) Str::ulid()),
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
        $client = Client::where('ulid', $ulid)->firstOrFail();

        $activeHistory = ClientAgentHistory::where('client_id', $client->id)
            ->whereNull('unassigned_at')
            ->first();

        $currentAgentId = $activeHistory ? $activeHistory->agent_id : null;

        $agents = Agent::where('actif', true)->get();

        return view('admin.clients.form', compact('client', 'agents', 'currentAgentId'));
    }

    public function update(Request $request, string $ulid)
    {
        $client = Client::where('ulid', $ulid)->firstOrFail();
        $request->merge([
            'telephone' => str_replace([' ', '+228'], '', $request->telephone),
        ]);

        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'prenom'    => 'required|string|max:255',
            'telephone' => 'required|digits:8',
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
