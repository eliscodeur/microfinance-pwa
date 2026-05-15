@extends('admin.layouts.app')

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
                                    
                                        <span>{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        <i class="bi bi-shield-lock text-primary me-1"></i>
                                        {{ $user->role->nom ?: "---" }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-primary edit-btn" 
                                        data-id="{{ $user->id }}" 
                                        data-name="{{ $user->name }}" 
                                        data-email="{{ $user->email }}" 
                                        data-role="{{ $user->role_id }}"
                                        data-url="{{ route('admin.users.update', $user->id) }}"> <!-- L'URL exacte ici -->
                                        <i class="bi bi-pencil"></i>
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
                    <input type="text" name="name" id="field-name" class="form-control">
                    <div class="invalid-feedback" id="error-name"></div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Adresse Email</label>
                    <input type="email" name="email" id="field-email" class="form-control">
                    <div class="invalid-feedback" id="error-email"></div>
                </div>
            </div>
            
            <div class="row" id="password-group">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="field-password" class="form-control">
                    <div class="invalid-feedback" id="error-password"></div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" id="field-password-conf" class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rôle</label>
                    <select name="role_id" id="field-role" class="form-select">
                        <option value="" disabled selected>Choisissez un rôle</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->nom }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="error-role_id"></div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" id="submit-btn" class="btn btn-primary px-4">
                    <i class="bi bi-save"></i> <span id="btn-text">Enregistrer l'administrateur</span>
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
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. ALERTES INITIALES (SESSION) ---
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Fait !', text: "{{ session('success') }}", timer: 3000 });
        @endif

        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Erreur', text: "{{ session('error') }}" });
        @endif

        const adminForm = document.getElementById('admin-form');
        const userIdInput = document.getElementById('user_id');
        const methodField = document.getElementById('method-field');
        const cancelBtn = document.getElementById('cancel-edit');
        const submitBtn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const formTitle = document.getElementById('form-title');
        const addTab = document.getElementById('add-tab');

        // --- 2. GESTION DE LA MODIFICATION ---
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                // 1. Récupération des données (Priorité au data-url pour éviter l'erreur de route)
                const id = this.dataset.id;
                const name = this.dataset.name;
                const email = this.dataset.email;
                const role = this.dataset.role;
                // Si data-url n'existe pas, on construit l'URL de secours
                const updateUrl = this.dataset.url || `/admin/users/${id}`; 

                // 2. Remplissage des champs du formulaire
                // Assurez-vous que userIdInput est bien défini dans votre DOM (ex: const userIdInput = document.getElementById('user_id');)
                document.getElementById('user_id').value = id; 
                document.getElementById('field-name').value = name;
                document.getElementById('field-email').value = email;
                document.getElementById('field-role').value = role;

                // 3. Configuration de la requête vers le UserController@update
                const form = document.getElementById('admin-form');
                form.action = updateUrl; 
                
                // IMPORTANT: Le champ _method est crucial pour que Route::resource reconnaisse le PUT
                document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                
                // 4. Ajustements de l'interface (UI)
                document.getElementById('form-title').innerText = "Modifier l'Administrateur";
                document.getElementById('btn-text').innerText = "Mettre à jour";
                
                const addTab = document.getElementById('add-tab');
                addTab.innerHTML = '<i class="bi bi-pencil-square"></i> Modifier';
                
                document.getElementById('cancel-edit').classList.remove('d-none');

                // 5. Gestion spécifique du mot de passe (On ne le modifie pas via ce formulaire)
                const passwordGroup = document.getElementById('password-group');
                if (passwordGroup) {
                    passwordGroup.classList.add('d-none');
                    document.getElementById('field-password').required = false;
                }

                // 6. Basculer visuellement vers l'onglet du formulaire
                const tabTrigger = new bootstrap.Tab(addTab);
                tabTrigger.show();
            });
        });

        // --- 3. GESTION DE L'ANNULATION ---
        cancelBtn.addEventListener('click', function() {
            resetAdminForm();
            // Retour à la liste
            new bootstrap.Tab(document.getElementById('list-tab')).show();
        });

        function resetAdminForm() {
            adminForm.reset();
            userIdInput.value = '';
            adminForm.action = "{{ route('admin.users.store') }}";
            methodField.innerHTML = '';
            
            formTitle.innerText = "Informations du nouvel administrateur";
            btnText.innerText = "Enregistrer l'administrateur";
            addTab.innerHTML = '<i class="bi bi-person-plus-fill"></i> Ajouter un Admin';
            
            cancelBtn.classList.add('d-none');
            document.getElementById('password-group').classList.remove('d-none');
            document.getElementById('field-password').required = true;

            // Nettoyer les erreurs de validation précédentes
            adminForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        }

        // --- 4. SUPPRESSION VIA SWEETALERT ---
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;

                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: `Vous allez supprimer l'administrateur ${name}. Cette action est irréversible !`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Oui, supprimer !',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Création d'un formulaire dynamique pour la suppression
                        const f = document.createElement('form');
                        f.action = `/admin/users/${id}`;
                        f.method = 'POST';
                        f.innerHTML = `
                            @csrf
                            @method('DELETE')
                        `;
                        document.body.appendChild(f);
                        f.submit();
                    }
                });
            });
        });

        // --- 5. SOUMISSION AJAX DU FORMULAIRE ---
        adminForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            submitBtn.disabled = true;
            const originalText = btnText.innerText;
            btnText.innerText = "Traitement...";
            // Reset erreurs
            adminForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            adminForm.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = '');

            fetch(this.action, {
                method: 'POST', // On utilise toujours POST car FormData + Method Spoofing (PUT) géré par Laravel
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(res => {
                if (res.status === 422) {
                    // Erreurs de validation
                    const errors = res.body.errors;
                    Object.keys(errors).forEach(key => {
                        const input = adminForm.querySelector(`[name="${key}"]`);
                        const errorDiv = document.getElementById(`error-${key}`);
                        if (input) input.classList.add('is-invalid');
                        if (errorDiv) errorDiv.innerText = errors[key][0];
                    });
                } else if (res.status === 200 || res.status === 201) {
                    // Succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Réussi !',
                        text: res.body.message || 'Opération effectuée avec succès.',
                        timer: 2000
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(res.body.message || 'Erreur serveur');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Une erreur est survenue : ' + error.message
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                btnText.innerText = originalText;
            });
        });
    });
</script>
@endsection