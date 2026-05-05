@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4"><i class="bi bi-bank me-2"></i>Administration des Carnets</h2>
    </div>

    {{-- Onglets Principaux --}}
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="mainTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button">
                <i class="bi bi-journal-text me-1"></i> Consultation
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-content" type="button">
                <i class="bi bi-plus-circle me-1"></i> Nouveau Carnet
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabsContent">
        {{-- SECTION LISTE --}}
        <div class="tab-pane fade {{ !$errors->any() ? 'show active' : '' }}" id="list-content">
            
            {{-- Barre de recherche globale --}}
            <div class="bg-white p-3 border rounded-3 mb-4 shadow-sm">
                <form action="{{ route('admin.carnets.index') }}" method="GET" class="row g-2">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                placeholder="Rechercher un numéro, un nom ou un téléphone..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark w-100 fw-bold">Rechercher</button>
                    </div>
                </form>
            </div>

            {{-- SOUS-ONGLETS PAR CATÉGORIE --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light p-0">
                    <ul class="nav nav-tabs border-0" id="categoryTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active px-4 py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-tontine">
                                <i class="bi bi-arrow-repeat me-2"></i>Tontines
                                <span class="badge bg-primary ms-2">{{ $carnets->where('type', 'tontine')->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4 py-3 fw-bold text-warning" data-bs-toggle="tab" data-bs-target="#tab-epargne">
                                <i class="bi bi-piggy-bank me-2"></i>Comptes Épargne
                                <span class="badge bg-warning text-dark ms-2">{{ $carnets->where('type', 'compte')->count() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content p-0">
                    {{-- Onglet Tontine --}}
                    <div class="tab-pane fade show active" id="tab-tontine">
                        @include('admin.carnets.partials.table', ['type' => 'tontine', 'items' => $carnets->where('type', 'tontine')])
                    </div>

                    {{-- Onglet Épargne --}}
                    <div class="tab-pane fade" id="tab-epargne">
                        @include('admin.carnets.partials.table', ['type' => 'compte', 'items' => $carnets->where('type', 'compte')])
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION FORMULAIRE (AJOUT/MODIF) --}}
        <div class="tab-pane fade {{ $errors->any() ? 'show active' : '' }}" id="add-content">
            {{-- Le formulaire reste identique à ta version précédente pour la gestion Laravel/Dexie --}}
            <div class="card shadow-sm border-0 p-4">
                <h5 class="mb-4 fw-bold text-primary" id="form-title">Ouverture d'un carnet</h5>
                <form action="{{ route('admin.carnets.store') }}" method="POST" id="carnet-form">
                    @csrf
                    <div id="method-field"></div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted">CLIENT</label>
                            <select name="client_id" id="select-client" class="form-select select2" required onchange="loadTontines(this.value)">
                                <option value="">Choisir...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->nom }} {{ $client->prenom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">TYPE</label>
                            <select name="type" id="typeSelect" class="form-select" onchange="toggleFields()">
                                <option value="tontine">Tontine</option>
                                <option value="compte">Compte Épargne</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">DATE DÉBUT</label>
                            <input type="date" name="date_debut" id="field-date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-12" id="tontineFields">
                            <label class="form-label fw-bold small text-muted">CATÉGORIE TONTINE</label>
                            <select name="category_tontine_id" class="form-select">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="compteFields">
                            <label class="form-label fw-bold small text-warning">LIER À UNE TONTINE</label>
                            <select name="parent_id" id="parent_id" class="form-select" disabled>
                                <option value="">Sélectionnez d'abord un client</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" id="submit-btn" class="btn btn-primary px-4">Enregistrer</button>
                        <button type="button" id="cancel-edit" class="btn btn-light border d-none">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>
    const getTodayDate = () => new Date().toISOString().split('T')[0];

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

    function loadTontines(clientId, selectedParentId = null) {
        const parentSelect = document.getElementById('parent_id');
        if (!parentSelect || !clientId) return;

        parentSelect.innerHTML = '<option value="">Chargement...</option>';
        const url = '/admin/carnets/get-tontines/' + clientId + '?t=' + new Date().getTime();

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }})
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

    document.addEventListener('DOMContentLoaded', function() {
        const clientSelect = document.getElementById('select-client');
        const oldParentId = "{{ old('parent_id') }}";
        if (clientSelect && clientSelect.value) {
            loadTontines(clientSelect.value, oldParentId);
            toggleFields(); 
        }

        // Modal suppression dynamique
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const carnetId = button.getAttribute('data-id');
                const carnetNumero = button.getAttribute('data-numero');
                
                deleteModal.querySelector('#delete-carnet-numero').textContent = carnetNumero;
                deleteModal.querySelector('#delete-form').action = '/admin/carnets/' + carnetId;
            });
        }
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-btn');
        if (btn) {
            const data = btn.dataset;
            const form = document.getElementById('carnet-form');

            document.getElementById('form-title').innerText = "Modifier le carnet #" + data.numero;
            form.action = "/admin/carnets/" + data.id;
            document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('submit-btn').innerText = "Mettre à jour";
            document.getElementById('cancel-edit').classList.remove('d-none');

            document.getElementById('select-client').value = data.client;
            document.getElementById('typeSelect').value = data.type;
            document.getElementById('field-date').value = data.date;
            
            if(document.getElementById('field-category')) {
                document.getElementById('field-category').value = data.category || "";
            }

            loadTontines(data.client, data.parent);
            toggleFields();
            
            const addTabTrigger = document.getElementById('add-tab');
            if (addTabTrigger) { new bootstrap.Tab(addTabTrigger).show(); }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

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
            if (listTabTrigger) { new bootstrap.Tab(listTabTrigger).show(); }
        });
    }
</script>
@endsection