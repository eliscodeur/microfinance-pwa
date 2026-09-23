<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriesCharge;
use App\Models\TypesCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChargeController extends Controller
{
    public function index()
    {
        $typesCharges = TypesCharge::with('categories')->get();
        return view('admin.charges.index', compact('typesCharges'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'types_charge_id' => 'required|exists:types_charges,id',
            'libelle'         => 'required|string|max:255',
            'code_analytique' => 'nullable|string|max:50',
            'description'     => 'nullable|string',
        ]);

        CategoriesCharge::create([
            'ulid'            => (string) \Illuminate\Support\Str::ulid(),
            'types_charge_id' => $request->types_charge_id,
            'libelle'         => $request->libelle,
            'code_analytique' => $request->code_analytique,
            'description'     => $request->description,
            'actif'           => true,
        ]);

        return redirect()->route('admin.charges.index')
            ->with('success', 'Catégorie de charge créée avec succès.');
    }

    public function toggleCategoryStatus($ulid)
    {
        $categorie = CategoriesCharge::where('ulid', $ulid)->firstOrFail();

        // Alterne entre 0 et 1 (ou true/false)
        $categorie->actif = $categorie->actif == 1 ? 0 : 1;
        $categorie->save();

        return back()->with('success', 'Le statut de la catégorie a été mis à jour.');
    }
    public function storeType(Request $request)
    {
        $request->validate([
            'libelle'     => 'required|string|max:255|unique:types_charges,libelle',
            'code'        => 'required|string|max:50|unique:types_charges,code',
            'description' => 'nullable|string',
        ]);

        TypesCharge::create([
            'ulid'        => strtolower((string) Str::ulid()),
            'libelle'     => $request->libelle,
            'code'        => strtoupper($request->code),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.charges.index')
            ->with('success', 'Type de charge créé avec succès.');
    }
}
