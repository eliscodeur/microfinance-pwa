<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CategoryTontine;
use App\Models\Carnet;
use App\Models\Depot;
use App\Models\Cycle;
use App\Models\Retrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarnetController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Carnet::with([
                'client',
                'categoryTontine',
                'parent',
                'cycles.collectes',
                'credits' => function($q) {
                    $q->where('statut', 'active');
                }
            ])->withCount('cycles');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero', 'LIKE', "%$search%")
                      ->orWhereHas('client', function($sq) use ($search) {
                          $sq->where('nom', 'LIKE', "%$search%")
                             ->orWhere('prenom', 'LIKE', "%$search%")
                             ->orWhere('telephone', 'LIKE', "%$search%")
                             ->orWhere(\DB::raw("CONCAT(nom, ' ', prenom)"), 'LIKE', "%$search%");
                      });
                });
            }

            if ($request->filled('filter')) {
                if ($request->filter == 'vierge') {
                    $query->has('cycles', '=', 0);
                } elseif ($request->filter == 'actif') {
                    $query->has('cycles', '>', 0);
                }
            }

            $currentType   = $request->input('type', 'tontine');
            $totalTontines = (clone $query)->where('type', 'tontine')->count();
            $totalComptes  = (clone $query)->where('type', 'compte')->count();
            $totalGeneral  = $totalTontines + $totalComptes;

            $query->where('type', $currentType);
            $carnets    = $query->latest()->paginate(20)->withQueryString();
            $categories = CategoryTontine::all();
            $clients    = Client::select('id', 'nom', 'prenom')->orderBy('nom')->get();

            $tontinesActives = Carnet::where('type', 'tontine')
                ->where('statut', 'actif')
                ->with('client')
                ->get();

            return view('admin.carnets.index', compact(
                'carnets', 'clients', 'categories', 'tontinesActives',
                'currentType', 'totalTontines', 'totalComptes', 'totalGeneral'
            ));

        } catch (\Exception $e) {
            \Log::error("Erreur Carnets index : " . $e->getMessage());
            return redirect()->back()->with('error', "Une erreur est survenue.");
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'type'                => 'required|in:tontine,compte',
            'date_debut'          => 'required|date',
            'category_tontine_id' => 'required_if:type,tontine',
            'parent_id'           => 'nullable|exists:carnets,id',
        ], [
            'client_id.required'               => 'Le client est obligatoire.',
            'category_tontine_id.required_if'  => 'Veuillez choisir une catégorie pour la tontine.',
        ]);

        DB::beginTransaction();
        try {
            $validated['statut'] = 'actif';
            $carnet = Carnet::create($validated);
            DB::commit();
            return redirect()->route('admin.carnets.index')
                ->with('success', "Carnet " . $carnet->numero . " créé avec succès.");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $carnet = Carnet::findOrFail($id);

        $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'type'                => 'required|in:tontine,compte',
            'date_debut'          => 'required|date',
            'category_tontine_id' => 'required_if:type,tontine',
            'parent_id'           => 'nullable|exists:carnets,id',
        ], [
            'client_id.required'              => 'Le client est obligatoire.',
            'category_tontine_id.required_if' => 'Veuillez choisir une catégorie pour la tontine.',
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

    public function show($id)
    {
        $carnet = Carnet::with([
            'client',
            'cycles.collectes',
            'cycles.agent',
            'cycles.retraits.admin',
            'depots.user',
            'retraits.admin',
            'credits'
        ])->findOrFail($id);

        return view('admin.carnets.show', compact('carnet'));
    }

    public function getTontinesByClient($clientId)
    {
        $tontines = Carnet::where('client_id', (int) $clientId)
            ->where('type', 'tontine')
            ->where('statut', 'actif')
            ->get(['id', 'numero']);

        return response()->json($tontines);
    }

    public function getCarnetsByClient($clientId)
    {
        try {
            $carnets = Carnet::with([
                'categoryTontine', 'parent',
                'cycles.collectes', 'depots', 'retraits'
            ])
                ->where('client_id', (int) $clientId)
                ->where('statut', 'actif')
                ->whereDoesntHave('credits', fn($q) => $q->where('statut', 'active'))
                ->get()
                ->map(function (Carnet $carnet) {
                    $category        = $carnet->categoryTontine;
                    $requiredPointages = $category ? $category->minimumPointagesRequired() : null;
                    return [
                        'id'                => $carnet->id,
                        'numero'            => $carnet->numero,
                        'type'              => $carnet->type,
                        'category'          => $category ? $category->libelle : null,
                        'nombre_cycles'     => $category ? $category->nombre_cycles : null,
                        'required_pointages'=> $requiredPointages,
                        'total_pointages'   => $carnet->totalPointages(),
                        'available_savings' => $carnet->availableSavings(),
                        'guarantee_base'    => $carnet->guaranteeBase(),
                        'linked_tontine'    => $carnet->parent ? [
                            'id'     => $carnet->parent->id,
                            'numero' => $carnet->parent->numero,
                        ] : null,
                    ];
                });

            return response()->json($carnets);
        } catch (\Exception $e) {
            \Log::error('getCarnetsByClient: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
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
            \Log::error("Erreur Dépôt: " . $e->getMessage());

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

                $carnet          = Carnet::findOrFail($request->carnet_id);
                $montantNetSaisi = (float) $request->montant_total;
                $commissionAppliquee = 0;
                $soldeDisponible     = 0;
                $cycle               = null;

                if ($carnet->type === 'tontine') {
                    if (!$request->cycle_id) {
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
                    $commissionFixe  = (float) ($cycle->montant_journalier ?? 0);
                    $totalCollecte   = (float) $cycle->collectes->sum('montant');
                    $totalDejaRetire = (float) $cycle->retraits->sum('montant_net');
                    $netTotalAttendu = $totalCollecte - $commissionFixe;
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
            \Log::error("Erreur Retrait: " . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', "Échec : " . $e->getMessage())->withInput();
        }
    }
}