<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriesCharge;
use App\Models\Depense;
use App\Models\MouvementCaisse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DepenseController extends Controller
{
    /**
     * Affiche la liste des dépenses.
     */
    public function index(Request $request)
    {
        $query = Depense::with(['categorie.typeCharge', 'user']);

        // Filtre par catégorie de charge en utilisant l'ULID
        if ($request->filled('categorie_id')) {
            $query->whereHas('categorie', function ($q) use ($request) {
                $q->where('ulid', $request->categorie_id);
            });
        }

        // Filtre par plage de dates (provenant de Flatpickr avec le séparateur " au ")
        if ($request->filled('periode')) {
            $dates = explode(' au ', $request->periode);

            if (count($dates) == 2) {

                $query->whereBetween('date_depense', [trim($dates[0]), trim($dates[1])]);
            } elseif (count($dates) == 1) {

                $query->whereDate('date_depense', trim($dates[0]));
            }
        }

        $depenses = $query->latest('date_depense')->paginate(15)->appends(request()->query());

        // Récupération des catégories actives
        $categories = CategoriesCharge::where('actif', true)->with('typeCharge')->get();

        return view('admin.depenses.index', compact('depenses', 'categories'));
    }

    /**
     * Enregistre une nouvelle dépense.
     */
    public function store(Request $request)
    {
        $request->merge([
            'montant' => str_replace(' ', '', $request->montant),
        ]);
        $request->validate([
            'categories_charge_id' => 'required|exists:categories_charges,id',
            'montant'              => 'required|numeric|min:0.01',
            'date_depense'         => 'required|date',
            'beneficiaire'         => 'nullable|string|max:255',
            'mode_paiement'        => 'required|string|max:50',
            'reference_piece'      => 'nullable|string|max:100',
            'motif'                => 'required|string',
        ]);

        $depense = Depense::create([
            'ulid'                 => strtolower((string) Str::ulid()),
            'categories_charge_id' => $request->categories_charge_id,
            'montant'              => $request->montant,
            'date_depense'         => $request->date_depense,
            'beneficiaire'         => $request->beneficiaire,
            'mode_paiement'        => $request->mode_paiement,
            'reference_piece'      => $request->reference_piece,
            'motif'                => $request->motif,
            'user_id'              => Auth::id(),
        ]);

        MouvementCaisse::create([
            'ulid'           => strtolower((string) Str::ulid()),
            'type_operation' => 'depense operationnelle',
            'sens'           => 'sortie',
            'montant'        => $request->montant,
            'date_mouvement' => $request->date_depense . ' ' . now()->format('H:i:s'),
            'mode_paiement'  => $request->mode_paiement,
            'reference'      => $request->reference_piece ?? 'DEP-' . $depense->id,
            'libelle'        => 'Dépense : ' . $request->motif . ($request->beneficiaire ? ' ( Bénéficiaire: ' . $request->beneficiaire . ')' : ''),
            'source_type'    => Depense::class,
            'source_id'      => $depense->id,
            'user_id'        => Auth::id(),
        ]);

        return redirect()->route('admin.depenses.index')
            ->with('success', 'Dépense enregistrée avec succès.');
    }

    /**
     * Affiche le formulaire de modification (si tu souhaites une page dédiée ou un modal d'édition).
     */
    public function edit($ulid)
    {
        $depense    = Depense::where('ulid', $ulid)->firstOrFail();
        $categories = CategoriesCharge::where('actif', true)->with('typeCharge')->get();

        return view('admin.depenses.edit', compact('depense', 'categories'));
    }

    /**
     * Met à jour une dépense existante.
     */
    public function update(Request $request, $ulid)
    {
        $request->merge([
            'montant' => str_replace(' ', '', $request->montant),
        ]);

        $depense = Depense::where('ulid', $ulid)->firstOrFail();

        $request->validate([
            'categories_charge_id' => 'required|exists:categories_charges,id',
            'montant'              => 'required|numeric|min:0.01',
            'date_depense'         => 'required|date',
            'beneficiaire'         => 'nullable|string|max:255',
            'mode_paiement'        => 'required|string|max:50',
            'reference_piece'      => 'nullable|string|max:100',
            'motif'                => 'required|string',
        ]);

        // 1. Mise à jour de la dépense
        $depense->update([
            'categories_charge_id' => $request->categories_charge_id,
            'montant'              => $request->montant,
            'date_depense'         => $request->date_depense,
            'beneficiaire'         => $request->beneficiaire,
            'mode_paiement'        => $request->mode_paiement,
            'reference_piece'      => $request->reference_piece,
            'motif'                => $request->motif,
        ]);

        // 2. Mise à jour du mouvement de caisse correspondant
        MouvementCaisse::where('source_type', Depense::class)
            ->where('source_id', $depense->id)
            ->update([
                'montant'        => $request->montant,
                'date_mouvement' => $request->date_depense . ' ' . now()->format('H:i:s'),
                'mode_paiement'  => $request->mode_paiement,
                'reference'      => $request->reference_piece ?? 'DEP-' . $depense->id,
                'libelle'        => 'Dépense : ' . $request->motif . ($request->beneficiaire ? ' ( Bénéficiaire: ' . $request->beneficiaire . ')' : ''),
            ]);

        return redirect()->route('admin.depenses.index')
            ->with('success', 'Dépense et mouvement de caisse mis à jour avec succès.');
    }

    /**
     * Supprime une dépense.
     */
    public function destroy($ulid)
    {
        $depense = Depense::where('ulid', $ulid)->firstOrFail();
        $depense->delete();

        return back()->with('success', 'Dépense supprimée avec succès.');
    }
}
