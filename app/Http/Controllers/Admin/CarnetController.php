<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Carnet;
use App\Models\CarnetAgentHistory;
use App\Models\CategoryTontine;
use App\Models\Client;
use App\Models\ClientCarnetNumber;
use App\Models\Cycle;
use App\Models\Depot;
use App\Models\Retrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarnetController extends Controller
{
    public function index(Request $request, string $type = 'tontine')
    {
        try {
            $currentType = in_array($type, ['tontine', 'compte']) ? $type : 'tontine';

            $query = Carnet::with([
                'client',
                'categoryTontine',
                'parent',
                'cycles.collectes',
                'credits' => function ($q) {
                    $q->where('statut', 'active');
                },
            ])->withCount('cycles');

            if ($request->filled('filter')) {
                if ($request->filter == 'vierge') {
                    $query->has('cycles', '=', 0);
                } elseif ($request->filter == 'actif') {
                    $query->has('cycles', '>', 0);
                }
            }

            $totalTontines = (clone $query)->where('type', 'tontine')->count();
            $totalComptes  = (clone $query)->where('type', 'compte')->count();
            $totalGeneral  = $totalTontines + $totalComptes;

            // Récupération de la collection pour l'onglet actif
            $carnets = $query->where('type', $currentType)->latest()->get();

            $categories = CategoryTontine::all();
            $clients    = Client::select('id', 'nom', 'prenom')->orderBy('nom')->get();
            $agents     = Agent::select('ulid', 'id', 'nom', 'code_agent')->orderBy('nom')->get();

            $tontinesActives = Carnet::where('type', 'tontine')
                ->where('statut', 'actif')
                ->with('client')
                ->get();

            return view('admin.carnets.index', compact(
                'carnets', 'clients', 'categories', 'tontinesActives',
                'currentType', 'totalTontines', 'totalComptes', 'totalGeneral', 'agents'
            ));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Une erreur est survenue : " . $e->getMessage());
        }
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'               => 'required|exists:clients,id',
            'type'                    => 'required|in:tontine,compte',
            'category_tontine_id'     => 'required_if:type,tontine',
            'parent_id'               => 'nullable|exists:carnets,id',
            'client_carnet_number_id' => 'required|exists:client_carnet_numbers,id',
            'agent_id'                => 'required|exists:agents,id',
        ], [
            'client_id.required'               => 'Le client est obligatoire.',
            'category_tontine_id.required_if'  => 'Veuillez choisir une catégorie pour la tontine.',
            'client_carnet_number_id.required' => 'Veuillez sélectionner un numéro de carnet.',
            'agent_id.required'                => 'Veuillez sélectionner un agent.',
        ]);

        DB::beginTransaction();
        try {
            // 1. Récupérer le numéro physique source
            $carnetNumber = ClientCarnetNumber::findOrFail($request->client_carnet_number_id);

            $validated['numero']     = $carnetNumber->numero;
            $validated['ulid']       = strtolower((string) \Illuminate\Support\Str::ulid());
            $validated['statut']     = 'actif';
            $validated['date_debut'] = now()->toDateString();
            $validated['created_by'] = auth()->id();

            if ($validated['type'] === 'compte') {
                $validated['category_tontine_id'] = null;
            }
            // Créer le carnet
            $carnet = Carnet::create($validated);
            // Enregistrer l'historique de l'attribution de l'agent
            CarnetAgentHistory::create([
                'ulid'        => strtolower((string) \Illuminate\Support\Str::ulid()),
                'carnet_id'   => $carnet->id,
                'agent_id'    => $validated['agent_id'],
                'assigned_at' => now(),
            ]);

            DB::table('client_carnet_numbers')
                ->where('id', $carnetNumber->id)
                ->update([
                    'statut'     => 'utilise',
                    'used_at'    => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();
            return redirect()->route('admin.carnets.index')
                ->with('success', "Carnet n° " . $carnet->numero . " créé avec succès.");

        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $carnet = Carnet::findOrFail($id);

        $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'type'                => 'required|in:tontine,compte',
            'date_debut'          => 'required|date',
            'category_tontine_id' => 'required_if:type,tontine',
            'parent_id'           => 'nullable|exists:carnets,id',
            'agent_id'            => 'required|exists:agents,id',
        ], [
            'client_id.required'              => 'Le client est obligatoire.',
            'category_tontine_id.required_if' => 'Veuillez choisir une catégorie pour la tontine.',
            'agent_id.required'               => 'L\'agent est obligatoire.',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            if ($request->type === 'tontine') {
                $data['parent_id'] = null;
            } else {
                $data['category_tontine_id'] = null;
            }
            $carnet->update($data);
            DB::commit();
            return redirect()->route('admin.carnets.index')->with('success', 'Carnet mis à jour');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Carnet $carnet)
    {
        try {
            $carnet->delete();
            return redirect()->back()->with('success', 'Le carnet vierge a été supprimé.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(string $ulid)
    {
        $carnet = Carnet::with([
            'client',
            'cycles.collectes',
            'cycles.agent',
            'cycles.retraits.admin',
            'depots.user',
            'retraits.admin',
            'credits',
        ])->where('ulid', $ulid)->firstOrFail();

        return view('admin.carnets.show', compact('carnet'));
    }
    public function getTontinesByClient(int $clientId)
    {
        $tontines = Carnet::where('client_id', (int) $clientId)
            ->where('type', 'tontine')
            ->where('statut', 'actif')
            ->get(['id', 'numero']);

        return response()->json($tontines);
    }

    public function getAvailableCarnetNumbers(int $clientId)
    {
        $typeCarnet = request()->get('type', 'tontine');

        $carnetNumbers = ClientCarnetNumber::where('client_id', $clientId)
            ->where('type_carnet', $typeCarnet)
            ->where('statut', 'disponible')
            ->get(['id', 'numero', 'type_carnet']);

        return response()->json($carnetNumbers);
    }

    public function getCarnetsByClient(int $clientId)
    {
        try {
            // Eager loading : on charge uniquement le nécessaire
            $carnets = Carnet::with([
                'categoryTontine',
                'cycles'  => function ($q) {$q->whereNull('retire_at')->with('collectes');},
                'depots',
                'retraits',
                'credits' => function ($q) {$q->where('statut', 'active');},
            ])
                ->where('client_id', (int) $clientId)
                ->where('statut', 'actif')
                ->get();

            // 2. Transformation : on délègue les calculs au modèle
            $formattedCarnets = $carnets->map(function (Carnet $carnet) {

                // Logique conditionnelle basée sur le type
                $solde = ($carnet->type === 'compte')
                    ? $carnet->solde_disponible
                    : $carnet->activeCycleSavings();

                // On récupère le montant de la mise via le cycle en cours ou la catégorie
                $mise = $carnet->cycles->first()->montant_journalier ?? ($carnet->categoryTontine->montant_cotisation ?? 0);

                return [
                    'id'                 => $carnet->id,
                    'numero'             => $carnet->numero,
                    'type'               => $carnet->type,
                    'statut'             => $carnet->statut,
                    'date_creation'      => $carnet->created_at->format('Y-m-d'),
                    'solde'              => $solde,
                    'solde_bloque'       => $carnet->credits->sum('montant_demande'),
                    'mise'               => $mise,
                    'total_pointages'    => $carnet->totalPointages(),
                    'required_pointages' => $carnet->categoryTontine ? $carnet->categoryTontine->minimumPointagesRequired() : 0,
                    'date_fin_cycle'     => null,
                ];
            });

            return response()->json($formattedCarnets);

        } catch (\Exception $e) {

            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des carnets.'], 500);
        }
    }

    /**
     * Enregistre un dépôt d'épargne.
     * Retourne JSON si requête AJAX, redirect sinon.
     */
    public function storeDepot(Request $request)
    {
        $validated = $request->validate([
            'carnet_id'   => 'required|exists:carnets,id',
            'client_id'   => 'required|exists:clients,id',
            'montant'     => 'required|numeric|min:1',
            'date_depot'  => 'required|date',
            'commentaire' => 'nullable|string',
        ]);

        try {
            Depot::create([
                'client_id'   => $validated['client_id'],
                'carnet_id'   => $validated['carnet_id'],
                'user_id'     => auth()->id(),
                'montant'     => $validated['montant'],
                'date_depot'  => $validated['date_depot'],
                'commentaire' => $validated['commentaire'] ?? null,
                'cycle_id'    => null,
            ]);

            $message = 'Dépôt de ' . number_format($validated['montant'], 0, ',', ' ') . ' F enregistré avec succès.';

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Enregistre un retrait (tontine ou épargne).
     * Retourne JSON si requête AJAX, redirect sinon.
     */
    public function storeRetrait(Request $request)
    {
        $request->validate([
            'carnet_id'     => 'required|exists:carnets,id',
            'client_id'     => 'required|exists:clients,id',
            'cycle_id'      => 'nullable|exists:cycles,id',
            'montant_total' => 'required|numeric|min:0',
            'date_retrait'  => 'required|date',
            'type_retrait'  => 'required|in:partiel,total',
            'note'          => 'nullable|string',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {

                $carnet              = Carnet::findOrFail($request->carnet_id);
                $montantNetSaisi     = (float) $request->montant_total;
                $commissionAppliquee = 0;
                $soldeDisponible     = 0;
                $cycle               = null;

                if ($carnet->type === 'tontine') {
                    if (! $request->cycle_id) {
                        throw new \Exception("Veuillez sélectionner un cycle pour un carnet de tontine.");
                    }

                    $cycle           = Cycle::with(['collectes', 'retraits'])->findOrFail($request->cycle_id);
                    $totalCollecte   = (float) $cycle->collectes->sum('montant');
                    $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
                    $commissionFixe  = (float) ($cycle->montant_journalier ?? 0);

                    // Commission prélevée uniquement au premier retrait
                    $commissionAppliquee = ($cycle->retraits->count() == 0) ? $commissionFixe : 0;
                    $soldeDisponible     = $totalCollecte - $totalDejaRetire - $commissionAppliquee;

                } else {
                    $soldeDisponible     = (float) $carnet->solde_disponible;
                    $commissionAppliquee = 0;
                }

                // Vérification de sécurité (marge 1F pour arrondis)
                if ($montantNetSaisi > ($soldeDisponible + 1)) {
                    throw new \Exception(
                        "Fonds insuffisants. Solde disponible : " . number_format($soldeDisponible, 0, ',', ' ') . " F."
                    );
                }

                Retrait::create([
                    'carnet_id'     => $request->carnet_id,
                    'client_id'     => $request->client_id,
                    'cycle_id'      => ($carnet->type === 'tontine') ? $request->cycle_id : null,
                    'admin_id'      => auth()->id(),
                    'montant_total' => $montantNetSaisi + $commissionAppliquee,
                    'commission'    => $commissionAppliquee,
                    'montant_net'   => $montantNetSaisi,
                    'date_retrait'  => $request->date_retrait,
                    'note'          => $request->note,
                ]);

                // Clôture automatique du cycle (tontine uniquement)
                if ($carnet->type === 'tontine' && $cycle) {
                    $commissionFixe   = (float) ($cycle->montant_journalier ?? 0);
                    $totalCollecte    = (float) $cycle->collectes->sum('montant');
                    $totalDejaRetire  = (float) $cycle->retraits->sum('montant_net');
                    $netTotalAttendu  = $totalCollecte - $commissionFixe;
                    $cumulRetraitsNet = $totalDejaRetire + $montantNetSaisi;

                    if ($request->type_retrait === 'total' || $cumulRetraitsNet >= ($netTotalAttendu - 5)) {
                        $cycle->update(['retire_at' => $request->date_retrait]);
                    }
                }

                return number_format($montantNetSaisi, 0, ',', ' ');
            });

            $message = "Retrait de {$result} F enregistré avec succès.";

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', "Échec : " . $e->getMessage())->withInput();
        }
    }

    public function reassign(Request $request, string $ulid)
    {
        $request->validate([
            'new_agent_ulid' => 'required|exists:agents,ulid',
        ]);

        DB::transaction(function () use ($request, $ulid) {
            $currentHistory = CarnetAgentHistory::where('ulid', $ulid)->firstOrFail();
            $carnet         = Carnet::findOrFail($currentHistory->carnet_id);
            $newAgent       = Agent::where('ulid', $request->new_agent_ulid)->firstOrFail();

            // 1. Clôturer toutes les lignes ouvertes de ce carnet pour éviter les doublons actifs
            CarnetAgentHistory::where('carnet_id', $carnet->id)
                ->whereNull('unassigned_at')
                ->update(['unassigned_at' => now()]);

            // 2. Créer la nouvelle entrée d'historique
            CarnetAgentHistory::create([
                'ulid'          => (string) Str::ulid(),
                'agent_id'      => $newAgent->id,
                'carnet_id'     => $carnet->id,
                'assigned_at'   => now(),
                'unassigned_at' => null,
            ]);

            // 3. Mettre à jour l'agent principal sur le carnet (optimisation des requêtes)
            $carnet->update([
                'agent_id' => $newAgent->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Carnet réattribué avec succès.',
        ]);
    }

}
