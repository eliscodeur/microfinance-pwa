@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4"><i class="bi bi-bank me-2"></i>Administration des Carnets</h2>
    </div>

    <ul class="nav nav-tabs mb-4" id="carnetTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button" role="tab">
                <i class="bi bi-journal-text"></i> Liste des Carnets
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-primary" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-content" type="button" role="tab">
                <i class="bi bi-journal-plus"></i> Ajouter / Modifier carnet
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="carnetTabsContent">
        <div class="tab-pane fade {{ !$errors->any() ? 'show active' : '' }}" id="list-content" role="tabpanel">
            <div class="bg-white p-3 border rounded-3 mb-4 shadow-sm">
                <form action="{{ route('admin.carnets.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Rechercher</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                placeholder="N° de carnet, nom du client..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase">Type</label>
                        <select name="type" class="form-select border-0 bg-light">
                            <option value="">Tous les types</option>
                            <option value="tontine" {{ request('type') == 'tontine' ? 'selected' : '' }}>Tontine</option>
                            <option value="compte" {{ request('type') == 'compte' ? 'selected' : '' }}>Épargne (Compte)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">État des Carnets</label>
                        <select name="filter" class="form-select border-0 bg-light">
                            <option value="">Tous les carnets</option>
                            <option value="vierge" {{ request('filter') == 'vierge' ? 'selected' : '' }}>Vierges (Supprimables)</option>
                            <option value="actif" {{ request('filter') == 'actif' ? 'selected' : '' }}>Avec transactions (Verrouillés)</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-filter me-2"></i>Filtrer
                        </button>
                        <a href="{{ route('admin.carnets.index') }}" class="btn btn-light border w-100 shadow-sm" title="Réinitialiser">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>    
            <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Numéro</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Cycles</th> {{-- Nouvelle colonne --}}
                                    <th>Détails</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($carnets as $carnet)
                                <tr>
                                    <td><strong class="text-primary">{{ $carnet->numero }}</strong></td>
                                    <td>
                                        {{ $carnet->client->nom }} {{ $carnet->client->prenom }}<br>
                                        <small class="text-muted">{{ $carnet->client->telephone }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $carnet->type === 'tontine' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning' }} border">
                                            {{ ucfirst($carnet->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{-- On affiche le nombre de cycles --}}
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $carnet->cycles_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($carnet->type === 'tontine')
                                            <small>{{ $carnet->categoryTontine->libelle ?? '-' }}</small>
                                        @else
                                            <small class="text-muted">{{ $carnet->parent ? 'Lié au #'.$carnet->parent->numero : 'Indépendant' }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">

                                            @if($carnet->is_deletable)
                                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="{{ $carnet->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @else
                                                <span class="text-muted" title="Transactions existantes">
                                                    <i class="bi bi-lock-fill"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-folder-x display-4 d-block mb-3"></i>
                                        Aucun carnet trouvé.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <div class="tab-pane fade {{ $errors->any() ? 'show active' : '' }}" id="add-content" role="tabpanel">
            <div class="card shadow-sm border-0 p-4">
                <h5 class="mb-4" id="form-title">{{ isset($carnet) ? 'Modifier' : 'Ouverture d\'un nouveau' }} carnet</h5>
                
                {{-- Affichage de l'alerte si des erreurs existent --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                <form action="{{ route('admin.carnets.store') }}" method="POST" id="carnet-form">
                    @csrf
                    <div id="method-field"></div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Client</label>
                            <select name="client_id" id="select-client" class="form-select @error('client_id') is-invalid @enderror" required onchange="loadTontines(this.value)">
                                <option value="">-- Sélectionner le client --</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->nom }} {{ $client->prenom }} ({{ $client->telephone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type de Carnet</label>
                            <select name="type" id="typeSelect" class="form-select @error('type') is-invalid @enderror" required onchange="toggleFields()">
                                <option value="tontine" {{ old('type') == 'tontine' ? 'selected' : '' }}>Tontine</option>
                                <option value="compte" {{ old('type') == 'compte' ? 'selected' : '' }}>Compte Épargne</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de début</label>
                            <input type="date" name="date_debut" id="field-date" class="form-control @error('date_debut') is-invalid @enderror" value="{{ old('date_debut', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-12" id="tontineFields">
                            <label class="form-label fw-bold">Catégorie de Tontine</label>
                            <select name="category_tontine_id" id="field-category" class="form-select @error('category_tontine_id') is-invalid @enderror">
                                <option value="">Choisir la durée...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_tontine_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->libelle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 d-none" id="compteFields">
                            <label class="form-label fw-bold text-warning">Lier à une tontine active</label>
                            <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror" disabled>
                                <option value="">Sélectionnez d'abord un client</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" id="submit-btn" class="btn btn-primary px-4">Enregistrer</button>
                        <button type="button" id="cancel-edit" class="btn btn-light border d-none">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-1">Êtes-vous sûr de vouloir supprimer le carnet :</p>
                <h4 class="text-primary mb-3" id="delete-carnet-numero"></h4>
                <p class="text-muted small">Cette action est irréversible. Seuls les carnets sans aucun cycle peuvent être supprimés.</p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    /**
     * Retourne la date du jour au format YYYY-MM-DD
     */
    const getTodayDate = () => new Date().toISOString().split('T')[0];

    /**
     * Bascule l'affichage entre Tontine et Compte
     */
    function toggleFields() {
        const typeSelect = document.getElementById('typeSelect');
        const tontineFields = document.getElementById('tontineFields');
        const compteFields = document.getElementById('compteFields');

        if (!typeSelect) return;

        if (typeSelect.value === 'tontine') {
            tontineFields.classList.remove('d-none');
            compteFields.classList.add('d-none');
        } else {
            tontineFields.classList.add('d-none');
            compteFields.classList.remove('d-none');
        }
    }

    /**
     * Charge les tontines du client via AJAX
     */
    function loadTontines(clientId, selectedParentId = null) {
        const parentSelect = document.getElementById('parent_id');
        if (!parentSelect || !clientId) return;

        parentSelect.innerHTML = '<option value="">Chargement...</option>';
        
        // AJOUT DU TIMESTAMP (?t=...) POUR SURPASSER LE SERVICE WORKER
        const url = '/admin/carnets/get-tontines/' + clientId + '?t=' + new Date().getTime();

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            parentSelect.innerHTML = '<option value="">-- Choisir une tontine --</option>';
            
            if (data.length === 0) {
                parentSelect.innerHTML = '<option value="">Aucune tontine active trouvée</option>';
            } else {
                data.forEach(tontine => {
                    let option = document.createElement('option');
                    option.value = tontine.id;
                    option.text = "Tontine n° " + tontine.numero;
                    if (selectedParentId && tontine.id == selectedParentId) option.selected = true;
                    parentSelect.appendChild(option);
                });
                parentSelect.disabled = false;
            }
        });
    }

    /**
     * Initialisation au chargement de la page (Gestion des erreurs Laravel)
     */
    document.addEventListener('DOMContentLoaded', function() {
        const clientSelect = document.getElementById('select-client');
        const clientId = clientSelect ? clientSelect.value : null;
        
        // On récupère la valeur old() injectée par Blade
        const oldParentId = "{{ old('parent_id') }}";
        
        if (clientId) {
            // Recharge les tontines si la page revient avec une erreur (clientId déjà rempli)
            loadTontines(clientId, oldParentId);
            toggleFields(); 
        }
    });

    /**
     * Gestion du clic sur le bouton Modifier
     */
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-btn');
        if (btn) {
            const data = btn.dataset;
            const form = document.getElementById('carnet-form');

            // 1. Titre et Action du formulaire
            document.getElementById('form-title').innerText = "Modifier le carnet #" + data.numero;
            form.action = "/admin/carnets/" + data.id;
            document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('submit-btn').innerText = "Mettre à jour";
            document.getElementById('cancel-edit').classList.remove('d-none');

            // 2. Remplissage des champs
            document.getElementById('select-client').value = data.client;
            document.getElementById('typeSelect').value = data.type;
            document.getElementById('field-date').value = data.date;
            
            if(document.getElementById('field-category')) {
                document.getElementById('field-category').value = data.category || "";
            }

            // 3. Charger les tontines liées
            loadTontines(data.client, data.parent);

            // 4. Mettre à jour l'affichage
            toggleFields();
            
            // 5. Switch d'onglet
            const addTabTrigger = document.getElementById('add-tab');
            if (addTabTrigger) {
                new bootstrap.Tab(addTabTrigger).show();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                // Bouton qui a déclenché le modal
                const button = event.relatedTarget;
                
                // Extraction des infos depuis les attributs data-
                const carnetId = button.getAttribute('data-id');
                const carnetNumero = button.getAttribute('data-numero');
                
                // Mise à jour du contenu du modal
                const modalNumero = deleteModal.querySelector('#delete-carnet-numero');
                const modalForm = deleteModal.querySelector('#delete-form');
                
                modalNumero.textContent = carnetNumero;
                // On définit l'action du formulaire dynamiquement
                modalForm.action = '/admin/carnets/' + carnetId;
            });
        }
    });

    /**
     * Bouton Annuler
     */
    const cancelBtn = document.getElementById('cancel-edit');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            const form = document.getElementById('carnet-form');
            form.reset();
            
            document.getElementById('method-field').innerHTML = '';
            document.getElementById('form-title').innerText = "Ouverture d'un nouveau carnet";
            document.getElementById('submit-btn').innerText = "Enregistrer";
            document.getElementById('field-date').value = getTodayDate();
            
            this.classList.add('d-none');
            toggleFields();
            
            const listTabTrigger = document.getElementById('list-tab');
            if (listTabTrigger) {
                new bootstrap.Tab(listTabTrigger).show();
            }
        });
    }
</script>
@endsection