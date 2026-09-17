@extends('admin.layouts.app')

@section('content')
    <!-- Carte d'Informations de l'Agent -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Agent : {{ $agent->code_agent }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($agent->image)
                        <img src="{{ asset('storage/' . $agent->image) }}" alt="Photo de l'agent"
                            class="img-fluid rounded-circle border shadow"
                            style="width: 150px; height: 150px; object-fit: cover; cursor: pointer;" data-bs-toggle="modal"
                            data-bs-target="#imageModal">
                    @else
                        <div class="bg-light border rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 150px; height: 150px;">
                            <i class="bi bi-person-circle" style="font-size: 80px; color: #ccc;"></i>
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
                    <h4 class="text-primary">{{ \Illuminate\Support\Str::upper($agent->nom) }}</h4>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <p><strong>Email :</strong> {{ $agent->user->email ?? 'Pas d\'email' }}</p>
                            <p><strong>Téléphone :</strong> {{ $agent->telephone }}</p>
                            <p><strong>Actif :</strong>
                                <span class="badge {{ $agent->actif ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $agent->actif ? 'Oui' : 'Non' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-sm-6">
                            <p><strong>Carnets Gérés :</strong> {{ $carnetsCount }}</p>
                            <p><strong>Créé le :</strong> {{ $agent->created_at->format('d/m/Y') }}</p>
                            <p><strong>Mis à jour :</strong> {{ $agent->updated_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="resetPin({{ $agent->id }})">
                                <i class="bi bi-shield-lock"></i> Réinitialiser le code PIN
                            </button>
                            <span class="small text-muted ms-2">L'agent devra définir un nouveau code à sa prochaine
                                connexion.</span>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#avanceModal">
                                <i class="bi bi-wallet2 me-1"></i> Gérer les avances sur salaire
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte : Performance et Évolution (Graphique) -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 text-dark"><i class="bi bi-graph-up me-2"></i>Performance et Évolution</h5>

            <!-- Filtres et champs personnalisés -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="chartFilter" class="form-select form-select-sm" style="width: auto;"
                    onchange="handleFilterChange(this.value)">
                    <option value="7_days">7 derniers jours</option>
                    <option value="this_month">Ce mois-ci</option>
                    <option value="12_months">12 derniers mois</option>
                    <option value="custom">Période personnalisée...</option>
                </select>

                <!-- Inputs de dates (masqués par défaut) -->
                <div id="customDateContainer" class="d-none align-items-center gap-1">
                    <input type="date" id="startDate" class="form-control form-control-sm">
                    <input type="date" id="endDate" class="form-control form-control-sm">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="fetchChartData('custom')">Ok</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <canvas id="gainsChart" width="400" height="150"></canvas>
        </div>
    </div>

    <!-- Carte : Historique des Attributions de Carnets -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0 text-dark"><i class="bi bi-journal-text me-2"></i>Historique des Attributions de Carnets</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="agentsTable">
                    <thead>
                        <tr>
                            <th class="text-start">N° de Carnet</th>
                            <th>Client Propriétaire</th>
                            <th>Date d'Attribution</th>
                            <th>Date de Désattribution</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $entry)
                            <tr>
                                <td class="text-start">
                                    <strong>{{ $entry->carnet ? $entry->carnet->numero : 'Carnet supprimé' }}</strong>
                                </td>
                                <td>
                                    @if ($entry->carnet && $entry->carnet->client)
                                        {{ \Illuminate\Support\Str::upper($entry->carnet->client->nom) }}
                                        {{ \Illuminate\Support\Str::upper($entry->carnet->client->prenom) }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ optional($entry->assigned_at)->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                <td>{{ optional($entry->unassigned_at)->format('d/m/Y H:i') ?? 'En cours' }}</td>
                                <td>
                                    @if ($entry->unassigned_at)
                                        <span class="badge bg-secondary">Désassigné</span>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-reassign"
                                            data-ulid="{{ $entry->ulid }}" data-numero="{{ $entry->carnet->numero }}">
                                            <i class="bi bi-arrow-left-right"></i> Réattribuer
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Carte : Gestion et Historique des Avances sur Salaire -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark"><i class="bi bi-wallet2 me-2"></i>Avances sur Salaire</h5>
            <span class="badge bg-secondary">Total validé :
                {{ number_format($avancesList->where('statut', 'valide')->sum('montant'), 0, ',', ' ') }} FCFA
            </span>
        </div>
        <div class="card-body">



            <!-- Tableau de l'historique -->
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Motif</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($avancesList ?? [] as $avance)
                            <tr>
                                <td>{{ $avance->created_at->format('d/m/Y H:i') }}</td>
                                <td class="fw-bold">{{ number_format($avance->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ $avance->motif ?? 'N/A' }}</td>
                                <td>
                                    @if ($avance->statut == 'valide')
                                        <span class="badge bg-success">Validé</span>
                                    @elseif($avance->statut == 'rejete')
                                        <span class="badge bg-danger">Rejeté</span>
                                    @else
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.avances.destroy', $avance->id) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Voulez-vous supprimer cette avance ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Aucune avance enregistrée pour cet
                                    agent.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Actions globales de bas de page -->
    <div class="mb-3 mt-3">
        Statut actuel :
        @if ($agent->actif)
            <span class="badge bg-success">Actif</span>
        @else
            <span class="badge bg-danger">Inactif / Suspendu</span>
        @endif
    </div>

    <div class="d-flex gap-2 mt-3 mb-5">
        @can('Activer/Désactiver')
            <button type="button" class="btn {{ $agent->actif ? 'btn-outline-warning' : 'btn-outline-success' }}"
                onclick="confirmerToggleStatus({{ $agent->id }}, {{ $agent->actif ? 'true' : 'false' }})">
                {{ $agent->actif ? 'Désactiver' : 'Activer' }} Agent
            </button>

            <form id="form-toggle-status-{{ $agent->id }}" method="POST"
                action="{{ route('admin.agents.toggleStatus', $agent->id) }}" style="display: none;">
                @csrf
                @method('PATCH')
            </form>
        @endcan
        <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary">Retour à la liste</a>
        @can('Modifier données')
            <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-primary">Modifier</a>
        @endcan
    </div>
    <div class="modal fade" id="reassignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <form id="reassignForm">
                @csrf
                <input type="hidden" id="history_ulid" name="history_ulid">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Réattribuer le carnet: <span id="numCarnet"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-bold mb-2">Sélectionner le nouvel agent :</label>
                        <!-- Conteneur scrollable pour les radios -->
                        <div id="agentsRadioContainer" class="border rounded p-3 overflow-auto row"
                            style="max-height: 250px;">
                            <span class="text-muted">Chargement des agents...</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitReassign">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal d'octroi d'avance -->
    <div class="modal fade" id="avanceModal" tabindex="-1" aria-labelledby="avanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="avanceModalLabel">
                        <i class="bi bi-wallet2 me-2"></i> Octroyer une avance - {{ $agent->code_agent }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="formAvance">
                    @csrf
                    <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                    <div class="modal-body">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="montant_total" class="form-label small">Montant Total (FCFA) <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control form-control-sm"
                                    id="montant_total" name="montant_total" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_tranches" class="form-label small">Nombre de tranches <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="nombre_tranches"
                                    name="nombre_tranches" required value="1" min="1">
                            </div>
                            <div class="col-md-6">
                                <label for="montant_mensuel_apercu" class="form-label small">Montant mensuel
                                    estimé</label>
                                <input type="text" class="form-control form-control-sm bg-white"
                                    id="montant_mensuel_apercu" readonly placeholder="Auto">
                            </div>
                            <div class="col-md-6">
                                <label for="motif" class="form-label small">Motif</label>
                                <input type="text" class="form-control form-control-sm" id="motif"
                                    name="motif">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitAvance">
                            <i class="bi bi-check-lg"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Scripts pour Chart.js et actions diverses -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let gainsChart;

        document.addEventListener("DOMContentLoaded", function() {
            // Initialisation du graphique vide au chargement
            const ctx = document.getElementById('gainsChart').getContext('2d');
            gainsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                            label: 'Collectes (F)',
                            data: [],
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Gains / Commissions (F)',
                            data: [],
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Charger les données par défaut (7 derniers jours)
            fetchChartData('7_days');
        });

        function handleFilterChange(value) {
            const customContainer = document.getElementById('customDateContainer');
            if (value === 'custom') {
                customContainer.classList.remove('d-none');
                customContainer.classList.add('d-flex');
            } else {
                customContainer.classList.remove('d-flex');
                customContainer.classList.add('d-none');
                fetchChartData(value);
            }
        }

        async function fetchChartData(filter) {
            let url = `{{ route('admin.agents.chartData', $agent->id) }}?filter=${filter}`;

            if (filter === 'custom') {
                const start = document.getElementById('startDate').value;
                const end = document.getElementById('endDate').value;
                if (!start || !end) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Attention',
                        text: 'Veuillez sélectionner les deux dates'
                    });
                    return;
                }

                if (start > end) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur de période',
                        text: 'La date de début ne peut pas être postérieure à la date de fin.'
                    });
                    return;
                }
                url += `&start_date=${start}&end_date=${end}`;
            }

            try {
                const response = await fetch(url);
                const data = await response.json();

                // Mettre à jour les données du graphique sans recharger la page
                gainsChart.data.labels = data.dates;
                gainsChart.data.datasets[0].data = data.collecte;
                gainsChart.data.datasets[1].data = data.gains;
                gainsChart.update();
            } catch (error) {
                console.error('Erreur lors du chargement des données du graphique', error);
            }
        }
    </script>
    <script>
        // Fonction de réinitialisation du PIN
        async function resetPin(agentId) {
            const {
                isConfirmed
            } = await Swal.fire({
                title: 'Réinitialiser le PIN ?',
                text: "L'agent ne pourra plus utiliser son ancien code pour les collectes hors ligne.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, réinitialiser',
                cancelButtonText: 'Annuler'
            });

            if (isConfirmed) {
                try {
                    const response = await fetch(`/admin/agents/${agentId}/reset-pin`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (response.ok) {
                        Swal.fire('Réinitialisé !', 'Le code PIN a été réinitialisé.', 'success');
                    } else {
                        Swal.fire('Erreur', result.message || 'Une erreur est survenue', 'error');
                    }
                } catch (error) {
                    Swal.fire('Erreur', 'Impossible de contacter le serveur', 'error');
                }
            }
        }

        // Fonction de confirmation pour Activer/Désactiver
        function confirmerToggleStatus(agentId, isActif) {
            const actionText = isActif ? 'désactiver' : 'activer';
            const confirmButtonColor = isActif ? '#ffc107' : '#198754';
            Swal.fire({
                title: `Êtes-vous sûr ?`,
                text: `Vous allez ${actionText} cet agent.`,
                icon: isActif ? 'warning' : 'info',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Oui, ${actionText} !`,
                cancelButtonText: 'Annuler',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`form-toggle-status-${agentId}`).submit();
                }
            });
        }
        $(document).ready(function() {
            $('#agentsTable').DataTable({
                dom: "<'row mb-3'<'col-md-6'B><'col-md-6 d-flex justify-content-end'f>>" +
                    "<'row'<'col-md-12'tr>>" +
                    "<'row mt-3'<'col-md-5'i><'col-md-7 d-flex justify-content-end'p>>",
                buttons: [
                    'copy', 'excel', 'csv', 'pdf', 'print'
                ],
                pagingType: "simple_numbers", // <-- C'est ici que tu actives l'affichage des numéros de page
                language: {
                    processing: "Traitement en cours...",
                    search: "Rechercher&nbsp;:",
                    lengthMenu: "Afficher _MENU_ éléments",
                    info: "Affichage de l'historique _START_ à _END_ sur _TOTAL_",
                    infoEmpty: "Affichage de l'historique 0 à 0 sur 0 élément",
                    infoFiltered: "(filtré de _MAX_ éléments au total)",
                    loadingRecords: "Chargement en cours...",
                    zeroRecords: "Aucun élément trouvé",
                    emptyTable: "Aucune donnée disponible dans le tableau",
                    paginate: {
                        first: "Premier",
                        previous: "Précédent",
                        next: "Suivant",
                        last: "Dernier"
                    }
                },
                responsive: true,
                pageLength: 25
            });
        });
        $(document).ready(function() {
            // 1. Clic sur le bouton de réattribution : charge les agents sous forme de radios
            $(document).on('click', '.btn-reassign', function() {
                let historyUlid = $(this).data('ulid');
                $('#history_ulid').val(historyUlid);
                $("#numCarnet").text($(this).data('numero'));
                $.ajax({
                    url: `/admin/agents-list/${historyUlid}`,
                    type: 'GET',
                    success: function(response) {
                        let html = '';
                        if (response.length === 0) {
                            html =
                                '<p class="text-muted mb-0">Aucun autre agent disponible.</p>';
                        } else {
                            // Ouverture de la ligne Bootstrap
                            html += '<div class="row">';
                            response.forEach((agent, index) => {
                                html += `
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check border p-2 rounded h-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-2" type="radio" name="new_agent_ulid" id="agent_${index}" value="${agent.ulid}" required>
                                            <label class="form-check-label w-100" for="agent_${index}" style="cursor: pointer;">
                                                <strong>${agent.nom}</strong>
                                                <span class="badge bg-light text-dark border">${agent.code_agent}</span>
                                            </label>
                                        </div>
                                    </div>
                                `;
                            });

                            html += '</div>';
                        }
                        $('#agentsRadioContainer').html(html);
                        $('#reassignModal').modal('show');
                    },
                    error: function() {
                        alert("Impossible de charger la liste des agents.");
                    }
                });
            });

            // 2. Soumission du formulaire en AJAX
            $('#reassignForm').on('submit', function(e) {
                e.preventDefault();
                let historyUlid = $('#history_ulid').val();
                // Récupérer la valeur du radio coché
                let newAgentUlid = $('input[name="new_agent_ulid"]:checked').val();
                let $submitBtn = $('#btnSubmitReassign');

                if (!newAgentUlid) {
                    alert("Veuillez sélectionner un agent.");
                    return;
                }

                $submitBtn.prop('disabled', true).text('En cours...');

                $.ajax({
                    url: `/admin/carnet-historique/${historyUlid}/reassign`,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        new_agent_ulid: newAgentUlid
                    },
                    success: function(response) {
                        $('#reassignModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Succès !',
                            text: response.message ||
                                'Le carnet a été réattribué avec succès.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        alert("Erreur : " + ("Une erreur est survenue"));
                        $submitBtn.prop('disabled', false).text('Enregistrer');
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const montantInput = document.getElementById('montant_total');
            const tranchesInput = document.getElementById('nombre_tranches');
            const apercuInput = document.getElementById('montant_mensuel_apercu');

            // Calcul automatique du montant mensuel
            function calculerMensuel() {
                let total = parseFloat(montantInput.value) || 0;
                let tranches = parseInt(tranchesInput.value) || 1;
                if (tranches > 0) {
                    let mensuel = total / tranches;
                    apercuInput.value = mensuel.toLocaleString('fr-FR') + ' FCFA';
                } else {
                    apercuInput.value = '0 FCFA';
                }
            }

            if (montantInput && tranchesInput) {
                montantInput.addEventListener('input', calculerMensuel);
                tranchesInput.addEventListener('input', calculerMensuel);
            }

            // Soumission du formulaire en AJAX
            const formAvance = document.getElementById('formAvance');
            if (formAvance) {
                formAvance.addEventListener('submit', function(e) {
                    e.preventDefault();
                    let formData = new FormData(formAvance);
                    let btn = document.getElementById('btnSubmitAvance');
                    btn.disabled = true;

                    fetch("{{ route('admin.agents.avances.store', $agent->id) }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            btn.disabled = false;
                            if (data.success) {
                                // Fermer le modal Bootstrap proprement
                                let modalEl = document.getElementById('avanceModal');
                                let modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap
                                    .Modal(modalEl);
                                modalObj.hide();

                                // Réinitialiser le formulaire
                                formAvance.reset();
                                if (apercuInput) apercuInput.value = '';

                                // Recharge la page pour actualiser le tableau géré par la vue
                                location.reload();
                            } else {
                                alert('Erreur lors de l\'enregistrement.');
                            }
                        })
                        .catch(error => {
                            btn.disabled = false;
                            console.error('Erreur:', error);
                            alert('Une erreur est survenue.');
                        });
                });
            }

            // Suppression d'une avance en AJAX (si vous gérez aussi la suppression par AJAX)
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-delete-avance')) {
                    let btn = e.target.closest('.btn-delete-avance');
                    let id = btn.getAttribute('data-id');

                    if (confirm('Voulez-vous supprimer cette avance ?')) {
                        fetch(`/admin/avances/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Supprime la ligne de la vue ou recharge la page
                                    let row = document.getElementById(`row-avance-${id}`);
                                    if (row) row.remove();
                                    else location.reload();
                                }
                            })
                            .catch(error => console.error('Erreur:', error));
                    }
                }
            });
        });
    </script>
@endsection
