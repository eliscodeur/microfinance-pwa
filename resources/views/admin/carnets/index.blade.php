@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Gestion des Carnets</h2>
    </div>

    <ul class="nav nav-tabs mb-4" id="carnetTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button" role="tab">
                <i class="bi bi-journal-text"></i> Liste des Carnets
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-primary" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-content" type="button" role="tab">
                <i class="bi bi-journal-plus"></i> Ajouter un Carnet
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="carnetTabsContent">
        <div class="tab-pane fade show active" id="list-content" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Numéro Carnet</th>
                                <th>Client / Téléphone</th>
                                <th>Réf. Physique</th>
                                <th>Cycles</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($carnets as $carnet)
                            <tr>
                                <td><strong>{{ $carnet->numero }}</strong></td>
                                <td>
                                    {{ $carnet->client->nom }} <br>
                                    <small class="text-muted">{{ $carnet->client->telephone }}</small>
                                </td>
                                <td><span class="text-muted">{{ $carnet->reference_physique ?? '-' }}</span></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $carnet->cycles_count }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-{{ $carnet->statut == 'actif' ? 'success' : 'dark' }} border">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>
                                        {{ ucfirst($carnet->statut) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" 
                                            class="btn btn-sm btn-outline-primary shadow-sm edit-btn"
                                            data-id="{{ $carnet->id }}"
                                            data-numero="{{ $carnet->numero }}"
                                            data-client="{{ $carnet->client_id }}"
                                            data-ref="{{ $carnet->reference_physique }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/>
                                            </svg>
                                        </button>

                                        <button data-id="{{ $carnet->id }}" data-numero="{{ $carnet->numero }}" class="btn btn-sm btn-outline-danger shadow-sm delete-btn">
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
                                <td colspan="5" class="text-center py-5 text-muted">Aucun carnet enregistré.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="add-content" role="tabpanel">
            <div class="card shadow-sm border-0 p-4">
                <h5 class="mb-4" id="form-title">Informations du nouveau carnet</h5>
                
                <form action="{{ route('admin.carnets.store') }}" method="POST" id="carnet-form">
                    @csrf
                    <div id="method-field"></div> 
                    <input type="hidden" name="carnet_id" id="carnet_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client</label>
                            <input list="clientList" id="field-client-search" class="form-control" placeholder="Taper le nom du client..." autocomplete="off">
                            
                            <input type="hidden" name="client_id" id="field-client-id" required>

                            <datalist id="clientList">
                                @foreach($clients as $client)
                                    <option data-id="{{ $client->id }}" value="{{ $client->nom }} ({{ $client->telephone }})">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Référence Physique</label>
                            <input type="text" name="reference_physique" id="field-ref" class="form-control" placeholder="Ex: REF-ABC-123">
                        </div>
                    </div>

                    <div id="info-auto-gen" class="alert alert-info py-2 shadow-sm">
                        <i class="bi bi-info-circle me-2"></i> Le numéro <strong>NNC-XXX</strong> sera généré automatiquement.
                    </div>

                    <div class="mt-4">
                        <button type="submit" id="submit-btn" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Enregistrer le carnet
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
                Êtes-vous sûr de vouloir supprimer ce carnet ? Cette action est irréversible.
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
    const form = document.getElementById('carnet-form');
    const methodField = document.getElementById('method-field');
    const submitBtn = document.getElementById('submit-btn');
    const formTitle = document.getElementById('form-title');
    const addTab = document.getElementById('add-tab');
    const cancelBtn = document.getElementById('cancel-edit');
    
    // Champs spécifiques
    const clientIdInput = document.getElementById('field-client-id');
    const clientSearchInput = document.getElementById('field-client-search');
    const refInput = document.getElementById('field-ref');

    // 1. GESTION DU CLIC SUR EDITER
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Récupération des infos
            const id = this.dataset.id;
            const clientId = this.dataset.client;
            const ref = this.dataset.ref;
            const numero = this.dataset.numero;
            
            // Récupérer le nom du client (depuis la 2ème colonne du tableau)
            const row = this.closest('tr');
            const clientNameFull = row.querySelector('td:nth-child(2)').innerText.trim();
            // On nettoie un peu si besoin (on prend juste la première ligne avant le téléphone)
            const clientName = clientNameFull.split('\n')[0];

            // --- MISE À JOUR DE L'INTERFACE ---
            formTitle.innerText = "Modifier le Carnet : " + numero;
            addTab.innerHTML = '<i class="bi bi-pencil-square"></i> Modifier ' + numero;
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Mettre à jour';
            submitBtn.classList.replace('btn-primary', 'btn-warning');
            
            // --- CONFIGURATION DU FORMULAIRE ---
            form.action = `/admin/carnets/${id}`; 
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            cancelBtn.classList.remove('d-none');

            // --- REMPLISSAGE DES CHAMPS ---
            clientIdInput.value = clientId;
            clientSearchInput.value = clientName;
            refInput.value = ref;

            // --- BASCULER SUR L'ONGLET ---
            new bootstrap.Tab(addTab).show();
        });
    });

    // 2. GESTION DU BOUTON ANNULER
    cancelBtn.addEventListener('click', function() {
        // Remettre les textes originaux
        formTitle.innerText = "Informations du nouvel administrateur";
        addTab.innerHTML = '<i class="bi bi-journal-plus"></i> Ajouter un Carnet';
        submitBtn.innerHTML = '<i class="bi bi-save"></i> Enregistrer le carnet';
        submitBtn.classList.replace('btn-warning', 'btn-primary');

        // Remettre l'action vers le store et vider le PUT
        form.action = "{{ route('admin.carnets.store') }}";
        methodField.innerHTML = '';
        
        // Vider le formulaire
        form.reset();
        clientIdInput.value = '';
        this.classList.add('d-none');

        // Revenir à la liste
        new bootstrap.Tab(document.getElementById('list-tab')).show();
    });
    document.getElementById('carnet-form').addEventListener('submit', function(e) {
        const searchInput = document.getElementById('field-client-search');
        const hiddenInput = document.getElementById('field-client-id');
        const list = document.getElementById('clientList');
        const val = searchInput.value;

        // Si l'ID est vide au moment de cliquer sur "Enregistrer"
        if (!hiddenInput.value) {
            const options = list.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === val) {
                    hiddenInput.value = options[i].getAttribute('data-id');
                    break;
                }
            }
        }

        // Sécurité : si après vérification c'est toujours vide, on bloque l'envoi
        if (!hiddenInput.value) {
            e.preventDefault();
            alert("Veuillez sélectionner un client valide dans la liste suggérée.");
            searchInput.focus();
        }
    });
    document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function() {
        // 1. Récupérer l'ID et le numéro du carnet depuis les attributs data-
        const id = this.dataset.id;
        const numero = this.dataset.numero;
        
        // 2. Sélectionner le formulaire et le texte de la modal
        const form = document.getElementById('delete-form');
        const modalBody = document.querySelector('#deleteModal .modal-body');

        // 3. Mettre à jour l'URL d'action (doit correspondre à ta route admin.carnets.destroy)
        form.action = `/admin/carnets/${id}`;

        // 4. Personnaliser le message pour rassurer l'utilisateur
        modalBody.innerHTML = `Êtes-vous sûr de vouloir supprimer le carnet <strong>${numero}</strong> ? Cette action est irréversible.`;
        
        // 5. Ouvrir la modal (si le data-bs-toggle ne suffit pas)
        const myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        myModal.show();
    });
});
});
</script>
@endsection