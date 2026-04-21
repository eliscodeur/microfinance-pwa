<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Role;

class UserController extends Controller
{
    public function profile()
    {
        $user = auth()->user()->load('role');

        return view('admin.users.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.profile')->with('success', 'Profil mis a jour avec succes.');
    }

    /**
     * Affiche la liste des administrateurs uniquement.
     */
    public function index()
    {
        // On récupère uniquement les admins pour cette vue
        $users = User::where('type', 'admin')
                     ->orderBy('name', 'asc')
                     ->get();

        // 1. Récupérer tous les utilisateurs de type 'admin' avec leur rôle associé
        $users = User::where('type', 'admin')->with('role')->get();

    // 2. On récupère tous les rôles pour le formulaire
        $roles = Role::all(); 

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Enregistre un nouvel administrateur.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        // 1. Validation stricte
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id'  => 'required|exists:roles,id',
        ]);

        // 2. Création de l'utilisateur
        User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']), // Hachage du mot de passe
            'type'      => 'admin', // 'admin' envoyé par le champ hidden
            'is_active' => true,
            'role_id'   => $validated['role_id'],
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', 'Administrateur créé avec succès.');
    }

    public function update(Request $request, User $user)
    {
        
        $user = User::findOrFail($user->id);
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
        ]);

        // SÉCURITÉ : On ne récupère QUE les champs autorisés.
        // Même si un utilisateur "force" l'envoi d'un password en JS, 
        // Laravel l'ignorera car il n'est pas dans cette liste.
        $user->update($request->only(['name', 'email', 'role_id']));

        return redirect()->route('admin.users.index')->with('success', 'Administrateur mis à jour.');
    }
    public function edit($id)
    {
        // On ne fait rien ici car l'édition se passe en JS sur la page index
        return redirect()->route('admin.users.index');
    }

    /**
     * Supprime un administrateur (sauf soi-même).
     */
    public function destroy(User $user)
    {
        // Sécurité : Un admin ne peut pas se supprimer lui-même
       // Sécurité : Empêcher de se supprimer soi-même
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
