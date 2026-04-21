<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Carnet;
use App\Models\Client;
use Illuminate\Http\Request;

class CarnetController extends Controller
{  
    public function index()
    {
        // On récupère les carnets avec leurs clients ET le nombre de cycles associés
        $carnets = Carnet::with('client')
            ->withCount('cycles') 
            ->orderBy('created_at', 'desc')
            ->get();

        $clients = \App\Models\Client::orderBy('nom')->paginate(25);

        return view('Admin.carnets.index', compact('carnets', 'clients'));
    }

    public function show($id)
    {
        $carnet = Carnet::with(['client', 'cycles.collectes', 'cycles.agent', 'cycles.retrait.admin'])->findOrFail($id);

        return view('admin.carnets.show', compact('carnet'));
    }
    // Affiche le formulaire (utile pour l'Admin ou l'Agent si en ligne)
    public function create($client_id)
    {
        $client = Client::findOrFail($client_id);
        return view('carnets.create', compact('client'));
    }

    // Enregistre le carnet
    public function store(Request $request)
    {
        // 1. Verrouiller la table pour éviter que deux personnes 
        // ne génèrent le même numéro en même temps (Race Condition)
        return DB::transaction(function () use ($request) {
            
            // 2. Trouver le dernier numéro existant
            $dernierCarnet = Carnet::orderBy('id', 'desc')->first();
            
            if (!$dernierCarnet) {
                $nouveauNumero = "NNC-001";
            } else {
                // On extrait le chiffre après "NNC-"
                // Exemple: "NNC-005" devient 5
                $dernierChiffre = (int) str_replace('NNC-', '', $dernierCarnet->numero);
                $suivant = $dernierChiffre + 1;
                
                // On reformate avec des zéros devant (ex: 006)
                $nouveauNumero = "NNC-" . str_pad($suivant, 3, '0', STR_PAD_LEFT);
            }

            // 3. Création du carnet
            $carnet = Carnet::create([
                'client_id' => $request->client_id,
                'numero' => $nouveauNumero, // Numéro auto-généré ici
                'reference_physique' => $request->reference_physique,
                'statut' => 'actif',
                'date_debut' => now(),
            ]);

            return redirect()->back()->with('success', "Carnet $nouveauNumero créé avec succès !");
        });
    }
    /**
 * Met à jour un carnet existant.
 */
    public function update(Request $request, $id)
    {
        // 1. Validation des données entrantes
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reference_physique' => 'nullable|string|max:100',
        ], [
            'client_id.required' => 'Veuillez sélectionner un client dans la liste.',
            'client_id.exists' => 'Le client sélectionné est invalide.'
        ]);

        // 2. Récupération du carnet (ou erreur 404 si l'ID n'existe pas)
        $carnet = Carnet::findOrFail($id);

        // 3. Mise à jour (on ne change généralement pas le numéro NNC)
        $carnet->update([
            'client_id' => $request->client_id,
            'reference_physique' => $request->reference_physique,
        ]);

        // 4. Redirection avec un message de succès
        return redirect()->route('admin.carnets.index')
                        ->with('success', "Le carnet {$carnet->numero} a été mis à jour.");
    }
    public function destroy($id)
    {
        // On récupère le carnet avec le compte de ses cycles
        $carnet = Carnet::withCount('cycles')->findOrFail($id);

        // RÈGLE MÉTIER : Vérification du nombre de cycles
        if ($carnet->cycles_count > 0) {
            return redirect()->back()->with('error', "Impossible de supprimer : ce carnet possède déjà {$carnet->cycles_count} cycle(s) enregistré(s).");
        }

        // Si 0 cycle, on supprime
        $carnet->delete();

        return redirect()->route('admin.carnets.index')->with('success', "Le carnet {$carnet->numero} a été supprimé avec succès.");
    }
}
