@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Gestion des Administrateurs</h2>
    </div>

    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button" role="tab">
                <i class="bi bi-person-lines-fill"></i> Liste des Admins
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-primary" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-content" type="button" role="tab">
                <i class="bi bi-person-plus-fill"></i> Ajouter un Admin
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="adminTabsContent">
        <div class="tab-pane fade show active" id="list-content" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom de l'administrateur</th>
                                <th>Email / Login</th>
                                <th>Rôle</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <!-- <div class="rounded-circle bg-danger text-white d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; font-weight: bold;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div> -->
                                        <span>{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        <i class="bi bi-shield-lock text-primary me-1"></i>
                                        {{ $user->role->nom }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary shadow-sm edit-btn"
                                                data-id="{{ $user->id }}"
                                                data-name="{{ $user->name }}"
                                                data-email="{{ $user->email }}"
                                                data-role="{{ $user->role_id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/>
                                            </svg>
                                        </button>

                                        
                                        <button data-id="{{ $user->id }}" data-name="{{ $user->name }}" class="btn btn-sm btn-outline-danger shadow-sm delete-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                            </svg>
                                        </button>
                                       
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">Aucun administrateur enregistré.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <div class="tab-pane fade" id="add-content" role="tabpanel">
        <div class="card shadow-sm border-0 p-4">
            
            <h5 class="mb-4" id="form-title">Informations du nouvel administrateur</h5>
            
            <form action="{{ route('admin.users.store') }}" method="POST" id="admin-form">
                @csrf
                <div id="method-field"></div> <input type="hidden" name="user_id" id="user_id">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom Complet</label>
                        <input type="text" name="name" id="field-name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Adresse Email</label>
                        <input type="email" name="email" id="field-email" class="form-control" required>
                    </div>
                </div>
                
                <div id="password-group">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" id="field-password" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" id="field-password-conf" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role_id" id="field-role" class="form-select" required>
                            <option value="" disabled selected>Choisissez un rôle</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" id="submit-btn" class="btn btn-primary px-4">
                        <i class="bi bi-save"></i> Enregistrer l'administrateur
                    </button>
                    <button type="button" id="cancel-edit" class="btn btn-light border d-none">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // ... (tes autres remplissages de champs) ...

            // 1. CACHER le mot de passe
            document.getElementById('password-group').classList.add('d-none');
            document.getElementById('field-password').required = false;

            // 2. Changer le titre et l'action
            document.getElementById('form-title').innerText = "Modifier l'Administrateur";
            document.getElementById('submit-btn').innerText = "Mettre à jour";
            document.getElementById('cancel-edit').classList.remove('d-none');
            
            // Basculer l'onglet
            new bootstrap.Tab(document.getElementById('add-tab')).show();
        });
    });

// Bouton Annuler : On réaffiche tout
    document.getElementById('cancel-edit').addEventListener('click', function() {
        document.getElementById('password-group').classList.remove('d-none');
        document.getElementById('field-password').required = true;
        // ... (reset du reste du formulaire) ...
    });
    // Gérer le clic sur le bouton Annuler
    document.getElementById('cancel-edit').addEventListener('click', function() {
        // 1. Réinitialiser le formulaire
        document.getElementById('admin-form').reset();
        document.getElementById('user_id').value = '';
        
        // 2. Remettre les textes originaux
        document.getElementById('form-title').innerText = "Informations du nouvel administrateur";
        document.getElementById('submit-btn').innerHTML = '<i class="bi bi-save"></i> Enregistrer l\'administrateur';
        document.getElementById('add-tab').innerHTML = '<i class="bi bi-person-plus-fill"></i> Ajouter un Admin';
        
        // 3. Remettre l'URL initiale (pour le store)
        document.getElementById('admin-form').action = "{{ route('admin.users.store') }}";
        document.getElementById('method-field').innerHTML = ''; // Enlever le @method('PUT')
        
        // 4. Masquer le bouton Annuler et remettre le mot de passe en "required"
        this.classList.add('d-none');
        document.getElementById('field-password').required = true;
        document.getElementById('password-help').classList.add('d-none');
        
        // 5. Revenir à l'onglet liste (optionnel, selon ton choix)
        var listTab = new bootstrap.Tab(document.getElementById('list-tab'));
        listTab.show();
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionner tous les boutons modifier
        const editButtons = document.querySelectorAll('.edit-btn');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                // 1. Récupérer les données depuis les attributs 'data-' du bouton
                const id = this.dataset.id;
                document.getElementById('user_id').value = id; // Stocker l'ID dans un champ caché
                const name = this.dataset.name;
                const email = this.dataset.email;
                const role = this.dataset.role;

                // 2. Modifier les textes de l'interface
                document.getElementById('form-title').innerText = "Modifier l'Administrateur";
                document.getElementById('submit-btn').innerText = "Mettre à jour";
                // On change aussi le texte de l'onglet lui-même
                document.getElementById('add-tab').innerHTML = '<i class="bi bi-pencil-square"></i> Modifier';

                // 3. Remplir les champs du formulaire
                document.getElementById('field-name').value = name;
                document.getElementById('field-email').value = email;
                document.getElementById('field-role').value = role;

                // 4. Cacher le groupe mot de passe
                document.getElementById('password-group').classList.add('d-none');
                document.getElementById('field-password').required = false;

                // 5. Modifier dynamiquement l'URL du formulaire pour pointer vers 'update'
                const form = document.getElementById('admin-form');
                console.log("ID de l'utilisateur à modifier :", user_id.value); // Debug
                form.action = `/admin/users/${user_id.value}`; // URL pour la mise à jour
                
                // Ajouter le champ caché _method="PUT" pour Laravel
                document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                console.log("Méthode de mise à jour :", document.getElementById('method-field').innerHTML); // Debug

                // 6. Afficher le bouton Annuler
                document.getElementById('cancel-edit').classList.remove('d-none');

                // 7. Basculer automatiquement sur l'onglet du formulaire
                const tabTrigger = new bootstrap.Tab(document.getElementById('add-tab'));
                tabTrigger.show();
                const successAlert = document.getElementById('success-alert');
        
            });
        });

        // Gérer le bouton Annuler pour remettre le formulaire à zéro
        document.getElementById('cancel-edit').addEventListener('click', function() {
            document.getElementById('admin-form').reset();
            document.getElementById('form-title').innerText = "Ajouter un Admin";
            document.getElementById('submit-btn').innerText = "Enregistrer";
            document.getElementById('add-tab').innerHTML = '<i class="bi bi-person-plus"></i> Ajouter';
            document.getElementById('password-group').classList.remove('d-none');
            document.getElementById('field-password').required = true;
            document.getElementById('method-field').innerHTML = ''; // On enlève le PUT
            document.getElementById('admin-form').action = "{{ route('admin.users.store') }}";
            this.classList.add('d-none');
            
            // Revenir à l'onglet liste
            new bootstrap.Tab(document.getElementById('list-tab')).show();
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        
        // 1. Mettre à jour l'URL du formulaire de suppression
        const deleteForm = document.getElementById('delete-form');
        deleteForm.action = `/admin/users/${id}`; 

        // 2. Optionnel : Personnaliser le texte de la modale avec le nom
        const modalBody = document.querySelector('#deleteModal .modal-body');
        modalBody.innerHTML = `Êtes-vous sûr de vouloir supprimer <strong>${name}</strong> ? Cette action est irréversible.`;

        // 3. Afficher la modale
        const myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        myModal.show();
    });
});

</script>
@endsection