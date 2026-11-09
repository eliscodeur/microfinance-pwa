<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Carnet;
use App\Models\CarnetAgentHistory;
use App\Models\CategoryTontine;
use App\Models\Client;
use App\Models\ClientCarnetNumber;
use App\Models\CashSession;
use App\Models\Cycle;
use App\Models\Depot;
use App\Models\MouvementCaisse;
use App\Models\Recette;
use App\Models\Retrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                'cycles.retraits',
                'cycles.creditSettlementSources',
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
            // 1. Récupérer et verrouiller le numéro physique en stock
            $carnetNumber = ClientCarnetNumber::where('id', $request->client_carnet_number_id)
                ->where('statut', 'disponible')
                ->lockForUpdate()
                ->first();

            if (! $carnetNumber) {
                return back()->withInput()->with('error', 'Ce numéro de carnet n\'est plus disponible en stock.');
            }

            // 2. Déterminer le prix unitaire de vente selon le type (tontine ou compte/épargne)
            $prixVente         = 0;
            $categoryTontineId = null;

            if ($validated['type'] === 'tontine') {
                $categoryTontineId = $request->category_tontine_id;
                $categorie         = DB::table('categories_tontine')->where('id', $categoryTontineId)->first();
                $prixVente         = $categorie ? $categorie->prix : 0;
            } else {
                $tarif = DB::table('parametre_tarifs')->where('code', 'carnet_epargne_prix')->first();
                if (! $tarif) {
                    return back()->withErrors(['error' => 'Le prix de vente des carnets n\'est pas configuré pour ce stock de carnets. Veuillez renseigner le tarif dans les paramètres.'])->withInput();
                }
                $prixVente = $tarif->prix;
            }

            // 3. LOGIQUE FIFO AVEC DISTINCTION TONTINE / COMPTE
            $query = DB::table('mouvement_stock_carnets')
                ->where('type', 'entree')
                ->where('quantite_restante', '>', 0);

            // Si c'est une tontine, on cherche dans le stock spécifique à cette catégorie
            if ($validated['type'] === 'tontine') {
                $query->where('categories_tontine_id', $categoryTontineId);
            } else {
                // Si c'est un compte épargne, on cherche dans le stock sans catégorie
                $query->whereNull('categories_tontine_id');
            }

            $entreeStock = $query->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();

            if (! $entreeStock) {
                $libelleStock = $validated['type'] === 'tontine' ? 'de cette catégorie de tontine' : 'de compte épargne';
                return back()->withInput()->with('error', "Stock épuisé : aucun carnet disponible en entrée pour ce type ($libelleStock).");
            }

            $prixAchat = $entreeStock->prix_unitaire_achat;

            DB::table('mouvement_stock_carnets')
                ->where('id', $entreeStock->id)
                ->decrement('quantite_restante', 1);
            // 4. Préparation des données du carnet
            $validated['numero']     = $carnetNumber->numero;
            $validated['ulid']       = strtolower((string) \Illuminate\Support\Str::ulid());
            $validated['statut']     = 'actif';
            $validated['date_debut'] = now()->toDateString();
            $validated['created_by'] = auth()->id();

            if ($validated['type'] === 'compte') {$validated['category_tontine_id'] = null;}

            //  Créer le carnet attribué
            $carnet = Carnet::create($validated);

            //  Enregistrer l'historique de l'agent
            CarnetAgentHistory::create([
                'ulid'        => strtolower((string) \Illuminate\Support\Str::ulid()),
                'carnet_id'   => $carnet->id,
                'agent_id'    => $validated['agent_id'],
                'assigned_at' => now(),
            ]);

            // Enregistrement de la recette de vente
            $recette = Recette::create([
                'ulid'          => strtolower((string) \Illuminate\Support\Str::ulid()),
                'reference'     => 'REC-CAR-' . date('Ymd') . '-' . uniqid(),
                'type_recette'  => 'vente_carnet',
                'montant'       => $prixVente,
                'client_id'     => $request->client_id,
                'credit_id'     => null,
                'user_id'       => Auth::id(),
                'mode_paiement' => $request->mode_paiement ?? 'especes',
                'date_recette'  => $request->date_vente ?? now()->toDateString(),
                'commentaire'   => $request->motif ?? 'Vente de carnets',
            ]);

            // Enregistrement du mouvement de caisse (Entrée d'argent)
            MouvementCaisse::create([
                'ulid'           => strtolower((string) Str::ulid()),
                'type_operation' => 'vente_carnet',
                'sens'           => 'entree',
                'montant'        => $prixVente,
                'date_mouvement' => $request->date_vente ?? now()->toDateString() . ' ' . now()->format('H:i:s'),
                'mode_paiement'  => $request->mode_paiement ?? 'especes',
                'reference'      => $recette->reference,
                'libelle'        => 'Encaissement : ' . ($request->motif ?? 'Vente de carnets'),
                'source_type'    => Recette::class,
                'source_id'      => $recette->id,
                'user_id'        => Auth::id(),
            ]);

            //  Mettre à jour le statut du numéro physique
            $carnetNumber->update([
                'statut'  => 'utilise',
                'used_at' => now(),
            ]);

            //  Enregistrer le mouvement de stock (Sortie)
            DB::table('mouvement_stock_carnets')->insert([
                'ulid'                  => strtolower((string) \Illuminate\Support\Str::ulid()),
                'categories_tontine_id' => $categoryTontineId,
                'type'                  => 'sortie',
                'quantite'              => 1,
                'quantite_restante'     => 0,
                'prix_unitaire_achat'   => $prixAchat,
                'prix_unitaire_vente'   => $prixVente,
                'motif'                 => 'Attribution carnet n° ' . $carnet->numero,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            DB::commit();
            return redirect()->route('admin.carnets.index')
                ->with('success', "Carnet n° " . $carnet->numero . " attribué avec succès.");

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
            'cycles.creditSettlementSources',
            'creditSettlementSources',
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
            'carnet_id'          => 'required|integer|exists:carnets,id',
            'montant'            => 'required|integer|min:1|max:9999999999999',
            'date_depot'         => 'required|date|before_or_equal:now',
            'mode_paiement'      => 'required|in:especes,mobile_money,virement',
            'reference_paiement' => 'nullable|required_if:mode_paiement,mobile_money,virement|string|max:100',
            'payeur_type'        => 'required|in:client,tiers',
            'payeur_nom'         => 'nullable|required_if:payeur_type,tiers|string|max:150',
            'payeur_telephone'   => 'nullable|string|max:30',
            'payeur_lien'        => 'nullable|required_if:payeur_type,tiers|string|max:80',
            'idempotency_key'    => 'required|uuid',
            'commentaire'        => 'nullable|string|max:1000',
        ], [
            'montant.integer' => 'Le montant doit être un nombre entier en FCFA.',
            'date_depot.before_or_equal' => 'La date du dépôt ne peut pas être dans le futur.',
            'reference_paiement.required_if' => 'La référence du paiement est obligatoire pour ce mode de règlement.',
            'payeur_nom.required_if' => 'Le nom du déposant est obligatoire si le payeur est un tiers.',
            'payeur_lien.required_if' => 'Le lien du déposant avec le client est obligatoire.',
        ]);

        $existingDepot = Depot::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingDepot) {
            if ((int) $existingDepot->user_id !== (int) auth()->id()) {
                $response = response()->json([
                    'success' => false,
                    'message' => 'Cette demande a déjà été utilisée. Actualisez la page avant de réessayer.',
                ], 409);
                return $request->expectsJson() || $request->ajax()
                    ? $response
                    : redirect()->back()->with('error', 'Cette demande a déjà été utilisée. Actualisez la page avant de réessayer.');
            }

            $response = response()->json([
                'success' => true,
                'message' => 'Ce dépôt a déjà été enregistré.',
                'reference' => $existingDepot->reference_operation,
                'receipt_url' => route('admin.depots.receipt', $existingDepot),
            ]);
            return $request->expectsJson() || $request->ajax()
                ? $response
                : redirect()->route('admin.depots.receipt', $existingDepot);
        }

        try {
            $depot = DB::transaction(function () use ($validated) {
                $carnet = Carnet::whereKey($validated['carnet_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $client = Client::whereKey($carnet->client_id)->firstOrFail();
                $session = CashSession::where('user_id', auth()->id())
                    ->where('statut', 'ouverte')
                    ->latest('date_ouverture')
                    ->lockForUpdate()
                    ->first();

                if (! $session) {
                    throw new \DomainException('Votre session de caisse est fermée. Ouvrez une caisse avant d’effectuer un dépôt.');
                }
                if ($carnet->type !== 'compte') {
                    throw new \DomainException('Un dépôt d’épargne ne peut être enregistré que sur un carnet de compte. Utilisez le formulaire de collecte pour une tontine.');
                }
                if ($carnet->statut !== 'actif' || ! $client->is_active) {
                    throw new \DomainException('Le dépôt est impossible : le carnet ou le compte client est inactif.');
                }

                $montant = (int) $validated['montant'];
                $soldeAvant = round(
                    (float) $carnet->depots()->sum('montant')
                    - (float) $carnet->retraits()->sum('montant_net')
                    - (float) $carnet->creditSettlementSources()->sum('montant_affecte'),
                    2
                );
                $payeurEstClient = $validated['payeur_type'] === 'client';
                $nomPayeur = $payeurEstClient
                    ? trim($client->nom . ' ' . $client->prenom)
                    : trim($validated['payeur_nom']);

                $depot = Depot::create([
                    'client_id'           => $client->id,
                    'carnet_id'           => $carnet->id,
                    'user_id'             => auth()->id(),
                    'cash_session_id'     => $session->id,
                    'montant'             => $montant,
                    'date_depot'          => $validated['date_depot'],
                    'commentaire'         => $validated['commentaire'] ?? null,
                    'mode_paiement'       => $validated['mode_paiement'],
                    'reference_paiement'  => $validated['reference_paiement'] ?? null,
                    'payeur_type'         => $validated['payeur_type'],
                    'payeur_nom'          => $nomPayeur,
                    'payeur_telephone'    => $payeurEstClient ? $client->telephone : ($validated['payeur_telephone'] ?? null),
                    'payeur_lien'         => $payeurEstClient ? 'Titulaire du carnet' : $validated['payeur_lien'],
                    'idempotency_key'     => $validated['idempotency_key'],
                    'solde_carnet_avant'  => $soldeAvant,
                    'solde_carnet_apres'  => $soldeAvant + $montant,
                ]);

                $referenceDepot = 'DEP-' . now()->format('Ymd') . '-' . str_pad((string) $depot->id, 8, '0', STR_PAD_LEFT);
                $depot->update(['reference_operation' => $referenceDepot]);

                MouvementCaisse::create([
                    'ulid'           => strtolower((string) Str::ulid()),
                    'type_operation' => 'depot compte',
                    'sens'           => 'entree',
                    'montant'        => $montant,
                    'date_mouvement' => $validated['date_depot'],
                    'mode_paiement'  => $validated['mode_paiement'],
                    'reference'      => $referenceDepot,
                    'libelle'        => 'Dépôt épargne carnet #' . $carnet->numero . ' - Client ' . $client->nom . ' ' . $client->prenom,
                    'source_type'    => Depot::class,
                    'source_id'      => $depot->id,
                    'user_id'        => auth()->id(),
                    'cash_session_id' => $session->id,
                ]);

                return $depot;
            });

            $message = 'Dépôt de ' . number_format((float) $depot->montant, 0, ',', ' ') . ' FCFA enregistré avec succès.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'reference' => $depot->reference_operation,
                    'receipt_url' => route('admin.depots.receipt', $depot),
                ]);
            }

            return redirect()->route('admin.depots.receipt', $depot);
        } catch (\DomainException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (QueryException $e) {
            $existingDepot = Depot::where('idempotency_key', $validated['idempotency_key'])->first();
            if ($existingDepot && (int) $existingDepot->user_id === (int) auth()->id()) {
                return ($request->expectsJson() || $request->ajax())
                    ? response()->json([
                    'success' => true,
                    'message' => 'Ce dépôt a déjà été enregistré.',
                    'reference' => $existingDepot->reference_operation,
                    'receipt_url' => route('admin.depots.receipt', $existingDepot),
                    ])
                    : redirect()->route('admin.depots.receipt', $existingDepot);
            }

            Log::error('Échec de l’enregistrement d’un dépôt d’épargne.', [
                'user_id' => auth()->id(),
                'carnet_id' => $validated['carnet_id'],
                'exception' => $e->getMessage(),
            ]);

            $response = response()->json([
                'success' => false,
                'message' => 'Le dépôt n’a pas pu être enregistré. Vérifiez la connexion et réessayez.',
            ], 500);
            return $request->expectsJson() || $request->ajax()
                ? $response
                : redirect()->back()->withInput()->with('error', 'Le dépôt n’a pas pu être enregistré. Vérifiez la connexion et réessayez.');
        } catch (\Throwable $e) {
            Log::error('Échec de l’enregistrement d’un dépôt d’épargne.', [
                'user_id' => auth()->id(),
                'carnet_id' => $validated['carnet_id'],
                'exception' => $e->getMessage(),
            ]);

            $response = response()->json([
                'success' => false,
                'message' => 'Une erreur technique a empêché l’enregistrement du dépôt.',
            ], 500);
            return $request->expectsJson() || $request->ajax()
                ? $response
                : redirect()->back()->withInput()->with('error', 'Une erreur technique a empêché l’enregistrement du dépôt.');
        }
    }

    public function depotReceipt(Depot $depot)
    {
        $depot->load(['client', 'carnet', 'user']);

        return view('admin.carnets.depot-recu', compact('depot'));
    }

    /**
     * Enregistre un retrait (tontine ou épargne).
     * Retourne JSON si requête AJAX, redirect sinon.
     */
    public function storeRetrait(Request $request)
    {
        $validated = $request->validate([
            'carnet_id'        => 'required|exists:carnets,id',
            'client_id'        => 'required|exists:clients,id',
            'cycle_id'         => 'nullable|exists:cycles,id',
            'montant_total'    => 'required|integer|min:1',
            'date_retrait'     => 'required|date',
            'type_retrait'     => 'required|in:partiel,total',
            'mode_paiement'    => 'required|in:especes,mobile_money,virement',
            'reference_paiement' => 'nullable|required_if:mode_paiement,mobile_money,virement|string|max:100',
            'motif'            => 'required|in:retrait_normal,retrait_exceptionnel,cloture_cycle,autre',
            'idempotency_key'  => 'required|uuid',
            'note'             => 'nullable|string|max:1000',
        ]);

        $existingRetrait = Retrait::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingRetrait) {
            if ((int) $existingRetrait->admin_id !== (int) auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande a déjà été utilisée. Actualisez la page avant de réessayer.',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cette opération avait déjà été enregistrée.',
                'reference' => $existingRetrait->reference_operation,
                'receipt_url' => route('admin.retraits.receipt', $existingRetrait),
            ]);
        }

        try {
            $retrait = DB::transaction(function () use ($validated) {
                $carnet = Carnet::whereKey($validated['carnet_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $client = Client::whereKey($carnet->client_id)->firstOrFail();
                $session = CashSession::where('user_id', auth()->id())
                    ->where('statut', 'ouverte')
                    ->lockForUpdate()
                    ->first();

                if (! $session) {
                    throw new \DomainException('Votre session de caisse est fermée. Ouvrez une caisse avant d’effectuer un retrait.');
                }
                if ((int) $validated['client_id'] !== (int) $carnet->client_id) {
                    throw new \DomainException('Le client sélectionné ne correspond pas au carnet.');
                }
                if (! $client->is_active) {
                    throw new \DomainException('Le retrait est impossible : le compte du client est inactif.');
                }
                if ($carnet->statut !== 'actif') {
                    throw new \DomainException('Le retrait est impossible : ce carnet n’est pas actif.');
                }
                if ($carnet->type !== 'tontine' && $validated['type_retrait'] === 'total') {
                    throw new \DomainException('Le retrait total avec clôture de cycle ne concerne que les carnets de tontine.');
                }

                $montantNetSaisi = (int) $validated['montant_total'];
                $commissionAppliquee = 0.0;
                $soldeDisponible = 0.0;
                $cycle = null;
                if ($carnet->type === 'tontine') {
                    if (empty($validated['cycle_id'])) {
                        throw new \DomainException('Veuillez sélectionner un cycle pour un carnet de tontine.');
                    }

                    $cycle = Cycle::whereKey($validated['cycle_id'])
                        ->where('carnet_id', $carnet->id)
                        ->where('statut', 'termine')
                        ->whereNull('retire_at')
                        ->lockForUpdate()
                        ->first();
                    if (! $cycle) {
                        throw new \DomainException('Le cycle sélectionné n’appartient pas à ce carnet ou n’est plus retirable.');
                    }

                    $totalCollecte = (float) $cycle->collectes()->sum('montant');
                    $totalDejaRetire = (float) $cycle->retraits()->sum('montant_net');
                    $commissionsDejaPrelevees = (float) $cycle->retraits()->sum('commission');
                    $totalAffecteCredit = (float) $cycle->creditSettlementSources()->sum('montant_affecte');
                    $commissionsCredit = (float) $cycle->creditSettlementSources()->sum('commission');
                    $cycleAlreadyUsed = $cycle->retraits()->exists()
                        || $cycle->creditSettlementSources()->exists();
                    $commissionFixe  = (float) ($cycle->montant_journalier ?? 0);

                    $commissionAppliquee = $cycleAlreadyUsed ? 0 : $commissionFixe;
                    $soldeDisponible = $totalCollecte
                        - $totalDejaRetire
                        - $commissionsDejaPrelevees
                        - $totalAffecteCredit
                        - $commissionsCredit
                        - $commissionAppliquee;
                } else {
                    $totalDepots = (float) $carnet->depots()->sum('montant');
                    $totalRetraits = (float) $carnet->retraits()->sum('montant_net');
                    $totalAffecteCredit = (float) $carnet->creditSettlementSources()->sum('montant_affecte');
                    $soldeDisponible = $totalDepots - $totalRetraits - $totalAffecteCredit;
                }

                $soldeDisponible = round($soldeDisponible, 2);
                if ($soldeDisponible <= 0 || $montantNetSaisi > $soldeDisponible) {
                    throw new \DomainException(
                        'Fonds insuffisants. Solde disponible : '
                        . number_format(max(0, $soldeDisponible), 0, ',', ' ')
                        . ' F.'
                    );
                }

                if ($carnet->type === 'tontine' && $validated['type_retrait'] === 'total'
                    && abs($montantNetSaisi - $soldeDisponible) > 0.01) {
                    throw new \DomainException('Pour une clôture totale, le montant doit être égal au net disponible du cycle.');
                }

                $encoursCredit = $carnet->credits()
                    ->whereIn('statut', ['active', 'in_arrears'])
                    ->get(['montant_accorde', 'interet_total', 'penalty_amount', 'montant_rembourse'])
                    ->sum(function ($credit) {
                        return (float) $credit->montant_accorde
                            + (float) $credit->interet_total
                            + (float) ($credit->penalty_amount ?? 0)
                            - (float) ($credit->montant_rembourse ?? 0);
                    });
                $soldePourCouvertureCredit = $carnet->type === 'tontine'
                    ? (float) $carnet->solde_tontine_non_retire
                    : $soldeDisponible;
                if ($encoursCredit > $soldePourCouvertureCredit) {
                    throw new \DomainException('Retrait impossible : le solde ne couvre pas l’encours de crédit du client.');
                }

                $retrait = Retrait::create([
                    'carnet_id'          => $carnet->id,
                    'client_id'          => $client->id,
                    'cycle_id'           => $cycle ? $cycle->id : null,
                    'admin_id'           => auth()->id(),
                    'cash_session_id'    => $session->id,
                    'montant_total'      => $montantNetSaisi + $commissionAppliquee,
                    'commission'         => $commissionAppliquee,
                    'montant_net'        => $montantNetSaisi,
                    'date_retrait'       => $validated['date_retrait'],
                    'note'               => $validated['note'] ?? null,
                    'idempotency_key'    => $validated['idempotency_key'],
                    'mode_paiement'      => $validated['mode_paiement'],
                    'reference_paiement' => $validated['reference_paiement'] ?? null,
                    'motif'              => $validated['motif'],
                    'solde_carnet_avant' => $soldeDisponible,
                    'solde_carnet_apres' => $soldeDisponible - $montantNetSaisi,
                ]);
                $retrait->reference_operation = 'RET-' . now()->format('Ymd') . '-' . str_pad((string) $retrait->id, 6, '0', STR_PAD_LEFT);
                $retrait->save();

                MouvementCaisse::create([
                    'ulid'           => strtolower((string) Str::ulid()),
                    'type_operation' => 'retrait ' . $carnet->type,
                    'sens'           => 'sortie',
                    'montant'        => $montantNetSaisi, // Le montant effectif décaissé
                    'date_mouvement' => $validated['date_retrait'],
                    'mode_paiement'  => $validated['mode_paiement'],
                    'reference'      => $retrait->reference_operation,
                    'libelle'        => 'Retrait (' . $carnet->type . ') - Client ' . $client->nom . ' ' . $client->prenom,
                    'source_type'    => Retrait::class,
                    'source_id'      => $retrait->id,
                    'user_id'        => auth()->id(),
                    'cash_session_id' => $session->id,
                ]);

                if ($carnet->type === 'tontine' && $cycle) {
                    if (abs($montantNetSaisi - $soldeDisponible) <= 0.01) {
                        $cycle->update(['retire_at' => $validated['date_retrait']]);
                    }
                }

                return $retrait;
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Retrait de ' . number_format((float) $retrait->montant_net, 0, ',', ' ') . ' F enregistré avec succès.',
                    'reference' => $retrait->reference_operation,
                    'receipt_url' => route('admin.retraits.receipt', $retrait),
                ]);
            }

            return redirect()->route('admin.retraits.receipt', $retrait);
        } catch (\DomainException $exception) {
            if (! $request->ajax()) {
                return redirect()->back()->with('error', $exception->getMessage())->withInput();
            }
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (QueryException $exception) {
            $existingRetrait = Retrait::where('idempotency_key', $validated['idempotency_key'])->first();
            if ($existingRetrait && (int) $existingRetrait->admin_id === (int) auth()->id()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cette opération avait déjà été enregistrée.',
                    'reference' => $existingRetrait->reference_operation,
                    'receipt_url' => route('admin.retraits.receipt', $existingRetrait),
                ]);
            }

            Log::error('Échec de l’enregistrement d’un retrait.', [
                'user_id' => auth()->id(),
                'carnet_id' => $validated['carnet_id'],
                'exception' => $exception,
            ]);
            if (! $request->ajax()) {
                return redirect()->back()->with('error', 'Le retrait n’a pas pu être enregistré. Vérifiez les données puis réessayez.')->withInput();
            }
            return response()->json(['success' => false, 'message' => 'Le retrait n’a pas pu être enregistré. Vérifiez les données puis réessayez.'], 500);
        } catch (\Throwable $exception) {
            Log::error('Erreur inattendue lors de l’enregistrement d’un retrait.', [
                'user_id' => auth()->id(),
                'carnet_id' => $validated['carnet_id'],
                'exception' => $exception,
            ]);
            if (! $request->ajax()) {
                return redirect()->back()->with('error', 'Une erreur est survenue. Le retrait n’a pas été confirmé.')->withInput();
            }
            return response()->json(['success' => false, 'message' => 'Une erreur est survenue. Le retrait n’a pas été confirmé.'], 500);
        }
    }

    public function receipt(int $retrait)
    {
        $retrait = Retrait::with(['client', 'carnet', 'cycle', 'admin', 'cashSession'])
            ->findOrFail($retrait);

        return view('admin.carnets.recu', compact('retrait'));
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

    public function evolutionVentes(Request $request)
    {
        $periode = $request->input('periode', '7_jours');

        // 1. Définir l'intervalle selon le filtre choisi
        if ($periode === '7_jours') {
            $startDate    = now()->subDays(6)->startOfDay();
            $endDate      = now()->endOfDay();
            $groupByMonth = false;
        } elseif ($periode === 'ce_mois') {
            $startDate    = now()->startOfMonth();
            $endDate      = now()->endOfMonth();
            $groupByMonth = false;
        } elseif ($periode === '12_mois') {
            $startDate    = now()->subMonths(11)->startOfMonth();
            $endDate      = now()->endOfMonth();
            $groupByMonth = true; // On groupe par mois pour 12 mois
        } elseif ($periode === 'personnalisee') {
            $startDate = Carbon::parse($request->input('date_debut'))->startOfDay();
            $endDate   = Carbon::parse($request->input('date_fin'))->endOfDay();
            // Optionnel : basculer en mois si l'écart est supérieur à 3 mois
            $groupByMonth = $startDate->diffInDays($endDate) > 90;
        } else {
            $startDate    = now()->subDays(6)->startOfDay();
            $endDate      = now()->endOfDay();
            $groupByMonth = false;
        }

        // 2. Récupérer les données groupées (par jour ou par mois)
        if ($groupByMonth) {
            $ventes = Carnet::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periode_cle"),
                'type',
                DB::raw('COUNT(*) as total')
            )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('periode_cle', 'type')
                ->orderBy('periode_cle', 'asc')
                ->get()
                ->keyBy(fn($item) => $item->periode_cle . '_' . $item->type);
        } else {
            $ventes = Carnet::select(
                DB::raw('DATE(created_at) as periode_cle'),
                'type',
                DB::raw('COUNT(*) as total')
            )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('periode_cle', 'type')
                ->orderBy('periode_cle', 'asc')
                ->get()
                ->keyBy(fn($item) => $item->periode_cle . '_' . $item->type);
        }

        // 3. Remplir les périodes manquantes pour une courbe continue
        $labels      = [];
        $tontineData = [];
        $compteData  = [];
        $current     = $startDate->copy();

        while ($current <= $endDate) {
            if ($groupByMonth) {
                $keyFormat   = $current->format('Y-m');
                $labelFormat = $current->locale('fr')->isoFormat('MMM YYYY'); // Ex: "Janv. 2026"
                $current->addMonth();
            } else {
                $keyFormat   = $current->format('Y-m-d');
                $labelFormat = $current->locale('fr')->isoFormat('D MMM YYYY'); // Ex: "23 Sept. 2026"
                $current->addDay();
            }

            // Éviter les doublons de labels si on avance par mois
            if (! in_array($labelFormat, $labels)) {
                $labels[] = $labelFormat;

                $tontineKey = $keyFormat . '_tontine';
                $compteKey  = $keyFormat . '_compte';

                $tontineData[] = isset($ventes[$tontineKey]) ? $ventes[$tontineKey]->total : 0;
                $compteData[]  = isset($ventes[$compteKey]) ? $ventes[$compteKey]->total : 0;
            }
        }

        return response()->json([
            'labels'  => $labels,
            'tontine' => $tontineData,
            'compte'  => $compteData,
        ]);
    }

    public function repartitionTontines(Request $request)
    {
        $periode = $request->input('periode', '7_jours');

        // 1. Définir l'intervalle selon le filtre choisi
        if ($periode === '7_jours') {
            $startDate = now()->subDays(6)->startOfDay();
            $endDate   = now()->endOfDay();
        } elseif ($periode === 'ce_mois') {
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        } elseif ($periode === '12_mois') {
            $startDate = now()->subMonths(11)->startOfMonth();
            $endDate   = now()->endOfMonth();
        } elseif ($periode === 'personnalisee') {
            $startDate = Carbon::parse($request->input('date_debut'))->startOfDay();
            $endDate   = Carbon::parse($request->input('date_fin'))->endOfDay();
        } else {
            $startDate = now()->subDays(6)->startOfDay();
            $endDate   = now()->endOfDay();
        }

        // 2. Récupérer les ventes groupées par catégorie de tontine
        $repartition = Carnet::select(
            'categories_tontine.libelle as nom_categorie',
            DB::raw('COUNT(carnets.id) as total')
        )
            ->join('categories_tontine', 'carnets.category_tontine_id', '=', 'categories_tontine.id')
            ->where('carnets.type', 'tontine')
            ->whereBetween('carnets.created_at', [$startDate, $endDate])
            ->groupBy('categories_tontine.id', 'categories_tontine.libelle')
            ->orderBy('total', 'desc')
            ->get();

        // 3. Formater les données pour Chart.js (Donut)
        $labels = $repartition->pluck('nom_categorie')->toArray();
        $values = $repartition->pluck('total')->toArray();

        return response()->json([
            'labels' => $labels,
            'values' => $values,
        ]);
    }

}
