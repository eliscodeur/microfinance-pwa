<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriesCharge;
use App\Models\CashSession;
use App\Models\CategoryTontine;
use App\Models\Depense;
use App\Models\MouvementCaisse;
use App\Models\MouvementStockCarnets;
use App\Services\CashExpenseGuard;
use App\Services\CashOperationAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class StockCarnetController extends Controller
{
    public function index()
    {
        $categories        = CategoryTontine::all();
        $categoriesCharges = CategoriesCharge::where('actif', true)->with('typeCharge')->get();
        // Récupérer uniquement les entrées
        $entrees = MouvementStockCarnets::with('categoryTontine')
            ->where('type', 'entree')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page_entrees');

        // Récupérer uniquement les sorties
        $sorties = MouvementStockCarnets::with('categoryTontine')
            ->where('type', 'sortie')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page_sorties');

        // --- CALCUL DES STOCKS DISPONIBLES (Basé sur quantite_restante) ---

        // 1. Stock des Tontines (là où categories_tontine_id n'est PAS NULL)
        $stockTontines = MouvementStockCarnets::whereNotNull('categories_tontine_id')
            ->sum('quantite_restante');

        // 2. Stock des Comptes (là où categories_tontine_id EST NULL)
        $stockComptes = MouvementStockCarnets::whereNull('categories_tontine_id')
            ->sum('quantite_restante');

        // 3. Total Général en réserve
        $stockTotal = $stockTontines + $stockComptes;

        return view('admin.stocks.index', compact(
            'categories',
            'entrees',
            'sorties',
            'stockTontines',
            'stockComptes',
            'categoriesCharges',
            'stockTotal'
        ));
    }

    public function storeEntree(Request $request)
    {
        $cashSessionId = null;

        try {
            $request->validate([
                'type_carnet'           => 'required|in:compte,tontine',
                'categories_tontine_id' => 'required_if:type_carnet,tontine|nullable|exists:categories_tontine,id',
                'quantite'              => 'required|integer|min:1',
                'prix_unitaire_achat'   => 'required|numeric|min:0',
                'motif'                 => 'nullable|string|max:255',
                'categories_charge_id'  => 'required|exists:categories_charges,id',
                'date_depense'          => 'required|date',
                'mode_paiement'         => 'required|string|max:50',
                'beneficiaire'          => 'nullable|string|max:255',
                'reference_piece'       => 'nullable|string|max:255',
            ]);

            return DB::transaction(function () use ($request, &$cashSessionId) {
                $categorieId  = $request->type_carnet === 'compte' ? null : $request->categories_tontine_id;
                $montantTotal = $request->quantite * $request->prix_unitaire_achat;
                $motifTexte   = $request->motif ?? 'Arrivage / Achat de carnets';

                $session = app(CashExpenseGuard::class)->assertForAuthenticatedUser(
                    $request->mode_paiement,
                    (float) $montantTotal
                );
                $cashSessionId = $session ? $session->id : null;

                MouvementStockCarnets::create([
                    'categories_tontine_id' => $categorieId,
                    'type'                  => 'entree',
                    'quantite'              => $request->quantite,
                    'quantite_restante'     => $request->quantite,
                    'prix_unitaire_achat'   => $request->prix_unitaire_achat,
                    'motif'                 => $motifTexte,
                ]);

                $depense = Depense::create([
                    'ulid'                 => strtolower((string) Str::ulid()),
                    'categories_charge_id' => $request->categories_charge_id,
                    'montant'              => $montantTotal,
                    'date_depense'         => $request->date_depense,
                    'beneficiaire'         => $request->beneficiaire,
                    'mode_paiement'        => $request->mode_paiement,
                    'reference_piece'      => $request->reference_piece,
                    'motif'                => $motifTexte,
                    'user_id'              => Auth::id(),
                ]);

                MouvementCaisse::create([
                    'ulid'           => strtolower((string) Str::ulid()),
                    'type_operation' => 'depense_operationnelle',
                    'sens'           => 'sortie',
                    'montant'        => $montantTotal,
                    'date_mouvement' => $request->date_depense . ' ' . now()->format('H:i:s'),
                    'mode_paiement'  => $request->mode_paiement,
                    'reference'      => $request->reference_piece ?? 'DEP-' . $depense->id,
                    'libelle'        => 'Dépense : ' . $motifTexte . ($request->beneficiaire ? ' ( Bénéficiaire: ' . $request->beneficiaire . ')' : ''),
                    'source_type'    => Depense::class,
                    'source_id'      => $depense->id,
                    'user_id'        => Auth::id(),
                ]);

                return redirect()->route('admin.stocks.index')->with('success', 'Entrée de stock et décaissement enregistrés avec succès.');
            });
        } catch (Throwable $exception) {
            $cashSessionId = $cashSessionId ?: CashSession::where('user_id', Auth::id())
                ->where('statut', 'ouverte')
                ->value('id');

            app(CashOperationAuditService::class)->recordRejectedExpense(
                (int) Auth::id(),
                $cashSessionId,
                $request->only([
                    'type_carnet',
                    'quantite',
                    'prix_unitaire_achat',
                    'prix_unitaire_achat',
                    'categories_charge_id',
                    'date_depense',
                    'mode_paiement',
                    'beneficiaire',
                    'reference_piece',
                    'motif',
                ]) + [
                    'operation' => 'carnet_purchase',
                    'montant' => is_numeric($request->quantite) && is_numeric($request->prix_unitaire_achat)
                        ? (float) $request->quantite * (float) $request->prix_unitaire_achat
                        : null,
                ],
                $exception,
                $request->fullUrl(),
                $request->ip(),
                $request->userAgent()
            );

            if ($exception instanceof \DomainException) {
                return redirect()->route('admin.stocks.index')
                    ->withErrors(['caisse' => $exception->getMessage()])
                    ->withInput();
            }

            throw $exception;
        }
    }
}
