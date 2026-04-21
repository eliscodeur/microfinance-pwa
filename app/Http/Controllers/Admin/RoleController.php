<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()->orderBy('nom')->get();

        $roleToEdit = null;
        if ($request->filled('id')) {
            $roleToEdit = Role::find($request->id);
        }

        $permissionLabels = config('role_permissions.labels', []);

        return view('admin.roles.index', compact('roles', 'roleToEdit', 'permissionLabels'));
    }

    public function store(Request $request)
    {
        $allowed = config('role_permissions.labels', []);

        $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom',
            'permissions' => 'required|array|min:1',
            'permissions.*' => ['string', Rule::in($allowed)],
        ], [
            'nom.unique' => 'Ce rôle existe déjà dans le système.',
            'nom.required' => 'Le nom du rôle est obligatoire.',
            'permissions.required' => 'Veuillez sélectionner au moins une permission.',
            'permissions.min' => 'Veuillez sélectionner au moins une permission.',
        ]);

        Role::create([
            'nom' => $request->nom,
            'permissions' => $request->permissions,
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Rôle créé avec succès.');
    }

    public function update(Request $request, Role $role)
    {
        $allowed = config('role_permissions.labels', []);

        $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom,' . $role->id,
            'permissions' => 'required|array|min:1',
            'permissions.*' => ['string', Rule::in($allowed)],
        ], [
            'nom.unique' => 'Ce nom de rôle est déjà utilisé par un autre enregistrement.',
            'permissions.required' => 'Veuillez sélectionner au moins une permission.',
            'permissions.min' => 'Veuillez sélectionner au moins une permission.',
        ]);

        $role->update($request->only(['nom', 'permissions']));

        return redirect()->route('admin.roles.index')->with('success', 'Rôle mis à jour avec succès.');
    }
    public function edit(Role $role)
    {
        // Au lieu d'afficher une vue, on renvoie vers l'index avec l'ID en paramètre
        return redirect()->route('admin.roles.index', ['id' => $role->id]);
    }
    // Supprimer un rôle
   
    public function destroy(Role $role)
    {
        // Sécurité : vérifier si le rôle est utilisé par des membres de la tontine/agents
        if($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer ce rôle car il est attribué à des utilisateurs.');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Rôle supprimé avec succès.');
    }
}