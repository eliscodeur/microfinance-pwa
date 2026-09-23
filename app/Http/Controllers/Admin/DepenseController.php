<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriesCharge;
use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DepenseController extends Controller
{
    /**
     * Affiche la liste des dépenses.
     */
    public function index()
    {
        $depenses   = Depense::with(['categorie.typeCharge', 'user'])->latest('date_depense')->get();
        $categories = CategoriesCharge::where('actif', true)->with('typeCharge')->get();

        return view('admin.depenses.index', compact('depenses', 'categories'));
    }

    /**
     * Enregistre une nouvelle dépense.
     */
    public function store(Request $request)
    {
        $request->validate([
            'categories_charge_id' => 'required|exists:categories_charges,id',
            'montant'              => 'required|numeric|min:0.01',
            'date_depense'         => 'required|date',
            'beneficiaire'         => 'nullable|string|max:255',
            'mode_paiement'        => 'required|string|max:50',
            'reference_piece'      => 'nullable|string|max:100',
            'motif'                => 'required|string',
        ]);

        Depense::create([
            'ulid'                 => (string) Str::ulid(),
            'categories_charge_id' => $request->categories_charge_id,
            'montant'              => $request->montant,
            'date_depense'         => $request->date_depense,
            'beneficiaire'         => $request->beneficiaire,
            'mode_paiement'        => $request->mode_paiement,
            'reference_piece'      => $request->reference_piece,
            'motif'                => $request->motif,
            'user_id'              => Auth::id(),
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

        $depense->update([
            'categories_charge_id' => $request->categories_charge_id,
            'montant'              => $request->montant,
            'date_depense'         => $request->date_depense,
            'beneficiaire'         => $request->beneficiaire,
            'mode_paiement'        => $request->mode_paiement,
            'reference_piece'      => $request->reference_piece,
            'motif'                => $request->motif,
        ]);

        return redirect()->route('admin.depenses.index')
            ->with('success', 'Dépense mise à jour avec succès.');
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
