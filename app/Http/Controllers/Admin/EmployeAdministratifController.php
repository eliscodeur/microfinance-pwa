<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeAdministratif;
use App\Models\Fonction;
use Illuminate\Http\Request;

class EmployeAdministratifController extends Controller
{
    public function index()
    {
        $employes  = EmployeAdministratif::with('fonction')->latest()->get();
        $fonctions = Fonction::all();

        return view('admin.employes.index', compact('employes', 'fonctions'));
    }

    public function create()
    {
        $fonctions = Fonction::all();
        return view('admin.employes.create', compact('fonctions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'            => 'required|string|max:255',
            'prenoms'        => 'required|string|max:255',
            'telephone'      => 'required|string|max:50',
            'fonction_id'    => 'required|exists:fonctions,id',
            'salaire_base'   => 'nullable|numeric|min:0', // Laisser vide pour prendre le salaire par défaut de la fonction
            'statut_contrat' => 'required|in:essai,confirme,cdd,cdi',
            'date_embauche'  => 'required|date',
            'email'          => 'nullable|email|max:255',
        ]);

        EmployeAdministratif::create($request->all());

        return redirect()->route('admin.employes.index')->with('success', 'Employé administratif enregistré avec succès.');
    }

    public function edit(EmployeAdministratif $employe)
    {
        $fonctions = Fonction::all();
        return view('admin.employes.edit', compact('employe', 'fonctions'));
    }

    public function update(Request $request, EmployeAdministratif $employe)
    {
        $request->validate([
            'nom'            => 'required|string|max:255',
            'prenoms'        => 'required|string|max:255',
            'telephone'      => 'required|string|max:50',
            'fonction_id'    => 'required|exists:fonctions,id',
            'salaire_base'   => 'nullable|numeric|min:0',
            'statut_contrat' => 'required|in:essai,confirme,cdd,cdi',
            'date_embauche'  => 'required|date',
            'email'          => 'nullable|email|max:255',
        ]);

        $employe->update($request->all());

        return redirect()->route('admin.employes.index')->with('success', 'Informations mises à jour avec succès.');
    }

    public function destroy(EmployeAdministratif $employe)
    {
        $employe->delete();
        return redirect()->route('admin.employes.index')->with('success', 'Employé supprimé avec succès.');
    }
}
