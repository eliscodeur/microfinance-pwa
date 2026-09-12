@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4"><i class="bi bi-bank me-2"></i>Administration des Carnets</h2>
            <span class="badge bg-dark p-2">Total Général : {{ $totalGeneral }}</span>
        </div>

        {{-- Onglets Principaux --}}
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="mainTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link {{ !$errors->any() ? 'active' : '' }} fw-bold" id="list-tab" data-bs-toggle="tab"
                    data-bs-target="#list-content" type="button">
                    <i class="bi bi-journal-text me-1"></i> Consultation
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $errors->any() ? 'active' : '' }} fw-bold" id="add-tab" data-bs-toggle="tab"
                    data-bs-target="#add-content" type="button">
                    <i class="bi bi-plus-circle me-1"></i> Nouveau Carnet
                </button>
            </li>
        </ul>

        <div class="tab-content" id="mainTabsContent">
            {{-- SECTION LISTE --}}
            <div class="tab-pane fade {{ !$errors->any() ? 'show active' : '' }}" id="list-content">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light p-0">
                        <ul class="nav nav-tabs border-0" id="categoryTabs">
                            <li class="nav-item">
                                <a class="nav-link {{ $currentType == 'tontine' ? 'active' : '' }} px-4 py-3 fw-bold"
                                    href="{{ route('admin.carnets.index', ['type' => 'tontine']) }}">
                                    <i class="bi bi-arrow-repeat me-2"></i>Tontines
                                    <span class="badge bg-primary ms-2">{{ $totalTontines }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $currentType == 'compte' ? 'active' : '' }} px-4 py-3 fw-bold text-warning"
                                    href="{{ route('admin.carnets.index', ['type' => 'compte']) }}">
                                    <i class="bi bi-piggy-bank me-2"></i>Comptes Épargne
                                    <span class="badge bg-warning text-dark ms-2">{{ $totalComptes }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content p-3">
                        <div class="tab-pane fade show active">
                            @include('admin.carnets.partials.table', [
                                'type' => $currentType,
                                'items' => $carnets,
                            ])
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION FORMULAIRE --}}
            <div class="tab-pane fade {{ $errors->any() ? 'show active' : '' }}" id="add-content">
                <div class="card shadow-sm border-0 p-4">
                    <h5 class="mb-4 fw-bold text-primary" id="form-title">Ouverture d'un carnet</h5>
                    <form action="{{ route('admin.carnets.store') }}" method="POST" id="carnet-form">
                        @csrf
                        <div id="method-field"></div>
                        <div class="row g-3">
                            {{-- CLIENT --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">CLIENT <span
                                        class="text-danger">*</span></label>
                                <select name="client_id" id="select-client" class="form-select select2" required>
                                    <option value="">Choisir un client...</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}"
                                            {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->nom }} {{ $client->prenom }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- TYPE DE CARNET --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">TYPE DE CARNET <span
                                        class="text-danger">*</span></label>
                                <select name="type" id="typeSelect" class="form-select" required>
                                    <option value="tontine" {{ old('type') == 'tontine' ? 'selected' : '' }}>Tontine
                                    </option>
                                    <option value="compte" {{ old('type') == 'compte' ? 'selected' : '' }}>Compte Épargne
                                    </option>
                                </select>
                                @error('type')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- NUMÉRO DE CARNET DISPONIBLE --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">NUMÉRO DE CARNET DISPONIBLE <span
                                        class="text-danger">*</span></label>
                                <select name="client_carnet_number_id" id="client_carnet_number_id" class="form-select"
                                    required>
                                    <option value="">Sélectionnez d'abord un client...</option>
                                </select>
                                @error('client_carnet_number_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6" id="tontineFields">
                                <label class="form-label fw-bold small text-muted">CATÉGORIE TONTINE</label>
                                <select name="category_tontine_id" class="form-select">
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_tontine_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->libelle }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 d-none" id="compteFields">
                                <label class="form-label fw-bold small text-warning">LIER À UNE TONTINE
                                    (FACULTATIF)</label>
                                <select name="parent_id" id="parent_id" class="form-select">
                                    <option value="">Sélectionnez d'abord un client...</option>
                                </select>
                            </div>

                            {{-- AGENT ASSIGNÉ --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">AGENT ASSIGNÉ <span
                                        class="text-danger">*</span></label>
                                <select name="agent_id" id="select-agent" class="form-select" required>
                                    <option value="">Sélectionner un agent...</option>
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}"
                                            {{ old('agent_id') == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->nom }} {{ $agent->prenom }} ({{ $agent->code_agent }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('agent_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
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

    {{-- Chargement sécurisé de jQuery (si non présent dans le layout global) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // 1. Fonction pour basculer l'affichage des champs selon le type (tontine ou compte)
        function toggleFields() {
            const typeSelect = document.getElementById('typeSelect');
            const tontineFields = document.getElementById('tontineFields');
            const compteFields = document.getElementById('compteFields');

            if (!typeSelect) return;

            if (typeSelect.value === 'tontine') {
                if (tontineFields) tontineFields.classList.remove('d-none');
                if (compteFields) compteFields.classList.add('d-none');
            } else {
                if (tontineFields) tontineFields.classList.add('d-none');
                if (compteFields) compteFields.classList.remove('d-none');
            }
        }

        // 2. Fonction principale de rechargement des données (numéros et tontines parentes)
        function triggerCarnetReload(selectedCarnetId = null, selectedParentId = null) {
            const clientId = $('#select-client').val();
            const type = $('#typeSelect').val() || 'tontine';

            const $carnetSelect = $('#client_carnet_number_id');
            const $parentSelect = $('#parent_id');
            const $parentContainer = $('#compteFields'); // Utilisation directe de votre conteneur ID

            if (!clientId) {
                $carnetSelect.html('<option value="">Sélectionnez d\'abord un client...</option>');
                $parentSelect.html('<option value="">Sélectionnez d\'abord un client...</option>');
                return;
            }

            const urlCarnets = "{{ route('admin.carnets.get-available-numbers', ':clientId') }}".replace(':clientId',
                clientId);
            const urlTontines = "{{ route('admin.carnets.get-tontines', ':clientId') }}".replace(':clientId', clientId);

            // A. Charger les numéros de carnets disponibles selon le type choisi
            $.ajax({
                url: urlCarnets,
                type: 'GET',
                data: {
                    type: type
                },
                success: function(data) {
                    $carnetSelect.empty();
                    if (!data || data.length === 0) {
                        $carnetSelect.append('<option value="">Aucun numéro disponible pour ce type</option>');
                    } else {
                        $carnetSelect.append('<option value="">-- Choisir un numéro de carnet --</option>');
                        data.forEach(item => {
                            let isSelected = (selectedCarnetId && item.id == selectedCarnetId) ?
                                'selected' : '';
                            $carnetSelect.append(
                                `<option value="${item.id}" ${isSelected}>${item.numero} (${item.type_carnet})</option>`
                            );
                        });
                    }
                },
                error: function() {
                    $carnetSelect.html('<option value="">Erreur de chargement</option>');
                }
            });

            // B. Gérer le champ "Lier à une tontine" uniquement si le type est "compte"
            if (type === 'compte') {
                $parentContainer.removeClass('d-none'); // Affiche le bloc

                $.ajax({
                    url: urlTontines,
                    type: 'GET',
                    success: function(data) {
                        $parentSelect.empty();
                        if (!data || data.length === 0) {
                            $parentSelect.append(
                                '<option value="">-- Aucune tontine active (optionnel) --</option>');
                        } else {
                            $parentSelect.append('<option value="">-- Aucune liaison (facultatif) --</option>');
                            data.forEach(tontine => {
                                let isSelected = (selectedParentId && tontine.id == selectedParentId) ?
                                    'selected' : '';
                                $parentSelect.append(
                                    `<option value="${tontine.id}" ${isSelected}>Tontine n° ${tontine.numero}</option>`
                                );
                            });
                        }
                    },
                    error: function() {
                        $parentSelect.html('<option value="">Erreur de chargement</option>');
                    }
                });
            } else {
                // Si c'est une tontine, on vide et on cache le bloc parent
                $parentSelect.val('');
                $parentContainer.addClass('d-none');
            }
        }

        // 3. Initialisation des écouteurs d'événements au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const clientSelect = document.querySelector('#select-client');
            const typeSelect = document.querySelector('#typeSelect');

            // Déclencher les actions lorsque le client ou le type change
            if (clientSelect) {
                clientSelect.addEventListener('change', function() {
                    triggerCarnetReload();
                });
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    toggleFields();
                    triggerCarnetReload();
                });
            }

            // Exécuter une fois au chargement au cas où des valeurs sont déjà sélectionnées (ex: old() ou mode édition)
            toggleFields();
            if (clientSelect && clientSelect.value) {
                triggerCarnetReload();
            }
        });

        // Écouteur pour le bouton Modifier
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.edit-btn');
            if (btn) {
                const data = btn.dataset;
                const form = document.getElementById('carnet-form');

                document.getElementById('form-title').innerText = "Modifier le carnet #" + data.numero;
                form.action = "/admin/carnets/" + data.id;
                document.getElementById('method-field').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';
                document.getElementById('submit-btn').innerText = "Mettre à jour";
                document.getElementById('cancel-edit').classList.remove('d-none');

                $('#select-client').val(data.client).trigger('change');
                document.getElementById('typeSelect').value = data.type;

                toggleFields();
                triggerCarnetReload(data.carnetNumberId, data.parent);

                const addTabTrigger = document.getElementById('add-tab');
                if (addTabTrigger) {
                    new bootstrap.Tab(addTabTrigger).show();
                }
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });

        // Bouton Annuler
        const cancelBtn = document.getElementById('cancel-edit');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                const form = document.getElementById('carnet-form');
                form.reset();
                document.getElementById('method-field').innerHTML = '';
                document.getElementById('form-title').innerText = "Ouverture d'un nouveau carnet";
                document.getElementById('submit-btn').innerText = "Enregistrer";
                this.classList.add('d-none');

                $('#select-client').val('').trigger('change');
                toggleFields();

                const listTabTrigger = document.getElementById('list-tab');
                if (listTabTrigger) {
                    new bootstrap.Tab(listTabTrigger).show();
                }
            });
        }
        $(document).ready(function() {
            // Écouteurs de changements pour actualiser dynamiquement
            $('#select-client, #typeSelect').on('change', function() {
                triggerCarnetReload();
            });

            // Gestion de l'affichage initial des champs selon le type
            toggleFields();
            $('#typeSelect').on('change', toggleFields);

            // Restauration des anciennes valeurs en cas d'erreur de validation (old)
            const initialClientId = $('#select-client').val();
            if (initialClientId) {
                triggerCarnetReload("{{ old('client_carnet_number_id') }}", "{{ old('parent_id') }}");
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            var table = $('#carnetsTable').DataTable({
                autoWidth: false, // Empêche DataTables de calculer de mauvaises largeurs fixes
                dom: "<'row mb-3'<'col-md-6'B><'col-md-6 d-flex justify-content-end'f>>" +
                    "<'row'<'col-md-12'tr>>" +
                    "<'row mt-3'<'col-md-5'i><'col-md-7 d-flex justify-content-end'p>>",
                buttons: ['copy', 'excel', 'csv', 'pdf', 'print'],
                pagingType: "simple_numbers",
                language: {
                    processing: "Traitement en cours...",
                    search: "Rechercher&nbsp;:",
                    lengthMenu: "Afficher _MENU_ éléments",
                    info: "Affichage des carnets _START_ à _END_ sur _TOTAL_ éléments",
                    infoEmpty: "Affichage 0 sur 0 carnet",
                    infoFiltered: "(filtré de _MAX_ éléments au total)",
                    loadingRecords: "Chargement en cours...",
                    zeroRecords: "Aucun carnet trouvé",
                    emptyTable: "Aucune carnet trouvé",
                    paginate: {
                        first: "Premier",
                        previous: "Précédent",
                        next: "Suivant",
                        last: "Dernier"
                    }
                },
                responsive: true,
                pageLength: 10
            });

            // Redimensionne les colonnes proprement lors du changement d'onglets Bootstrap
            $('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                table.columns.adjust().responsive.recalc();
            });
        });
    </script>
@endsection
