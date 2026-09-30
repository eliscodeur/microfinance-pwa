<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fonction;
use Illuminate\Http\Request;

class FonctionController extends Controller
{
    public function index()
    {
        $fonctions = Fonction::withCount('employes')->get();
        return view('admin.fonctions.index', compact('fonctions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'libelle'             => 'required|string|max:255|unique:fonctions,libelle',
            'salaire_base_defaut' => 'required|numeric|min:0',
            'description'         => 'nullable|string',
        ]);

        Fonction::create($request->all());

        return redirect()->back()->with('success', 'Fonction créée avec succès.');
    }

    public function update(Request $request, Fonction $fonction)
    {
        $request->validate([
            'libelle'             => 'required|string|max:255|unique:fonctions,libelle,' . $fonction->id,
            'salaire_base_defaut' => 'required|numeric|min:0',
            'description'         => 'nullable|string',
        ]);

        $fonction->update($request->all());

        return redirect()->back()->with('success', 'Fonction mise à jour avec succès.');
    }

    public function destroy(Fonction $fonction)
    {
        if ($fonction->employes()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer cette fonction car des employés y sont rattachés.');
        }

        $fonction->delete();
        return redirect()->back()->with('success', 'Fonction supprimée avec succès.');
    }
}
