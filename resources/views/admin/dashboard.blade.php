@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- ENTÊTE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">NANA ECO CONSULTING</h2>
            <p class="text-muted">Tableau de bord de gestion</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary-subtle text-primary p-2 px-3 rounded-pill">
                <i class="bi bi-calendar3 me-2"></i> {{ now()->format('d F Y') }}
            </span>
        </div>
    </div>

    {{-- CARTES DE STATISTIQUES GÉNÉRALES --}}
    <div class="row g-4 mb-4">
        @can('agents.view')
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary text-white rounded-3 p-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Agents terrain</h6>
                        <h3 class="mb-0 fw-bold">{{ $totalAgents }}</h3>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('clients.view')
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-secondary text-white rounded-3 p-3">
                        <i class="bi bi-person-check-fill fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Clients actifs</h6>
                        <h3 class="mb-0 fw-bold">{{ $totalClients }}</h3>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('sync.view')
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-3 {{ $pendingSyncBatches > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Sync. en attente</h6>
                        <h3 class="mb-0 fw-bold {{ $pendingSyncBatches > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $pendingSyncBatches }}
                        </h3>
                    </div>
                    @endcan
                    <a href="{{ route('admin.sync-batches.index') }}" class="btn btn-sm btn-light border-0 rounded-circle p-2">
                        <i class="bi bi-arrow-right-short fs-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @can('treasury.view')
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h6 text-uppercase text-muted fw-bold mb-0">Situation de trésorerie — {{ now()->format('d/m/Y') }}</h4>
            <a href="{{ route('admin.caisse.journal') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-journal-text me-1"></i> Journal de caisse
            </a>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-success border-4">
                    <span class="text-muted text-uppercase small fw-bold">Entrées du jour</span>
                    <strong class="fs-4 text-success">{{ number_format($tresorerieJour['entrees'], 0, ',', ' ') }} F</strong>
                    <small class="text-muted">Tous modes de paiement, selon le journal</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-danger border-4">
                    <span class="text-muted text-uppercase small fw-bold">Sorties du jour</span>
                    <strong class="fs-4 text-danger">{{ number_format($tresorerieJour['sorties'], 0, ',', ' ') }} F</strong>
                    <small class="text-muted">Tous modes de paiement, selon le journal</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-primary border-4">
                    <span class="text-muted text-uppercase small fw-bold">Flux net du jour</span>
                    <strong class="fs-4 {{ $tresorerieJour['flux_net'] < 0 ? 'text-danger' : 'text-primary' }}">
                        {{ number_format($tresorerieJour['flux_net'], 0, ',', ' ') }} F
                    </strong>
                    <small class="text-muted">Entrées moins sorties ; hors solde reporté</small>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-warning border-4">
                    <span class="text-muted text-uppercase small fw-bold">Espèces théoriques en caisses ouvertes</span>
                    <strong class="fs-4">{{ number_format($soldeEspecesCaissesOuvertes, 0, ',', ' ') }} F</strong>
                    <small class="text-muted">Somme des fonds théoriques des sessions ouvertes</small>
                </div>
            </div>
        </div>
    @endcan

    @can('credits.view')
    {{-- CARTES DE SANTÉ FINANCIÈRE (CRÉDITS) --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary-subtle text-primary rounded-3 p-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Encours Total Prêts</h6>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalEncours, 0, ',', ' ') }} <small class="fs-6 text-muted">F</small></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning-subtle text-warning rounded-3 p-3">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Dossiers en Retard</h6>
                        <h3 class="mb-0 fw-bold text-warning">{{ $countClientsEnRetard }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-3 border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger-subtle text-danger rounded-3 p-3">
                        <i class="bi bi-exclamation-octagon fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Pénalités de retard</h6>
                        <h3 class="mb-0 fw-bold text-danger">{{ number_format($totalPenalites, 0, ',', ' ') }} <small class="fs-6">F</small></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- SECTION GRAPHIQUE ET ALERTES --}}
    <div class="row g-4">
        {{-- GRAPHIQUE DYNAMIQUE --}}
        @can('treasury.view')
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                        <h5 class="fw-bold mb-0">Mouvements de trésorerie</h5>

                        <div class="d-flex align-items-center gap-2">
                            <select id="chartFilter" class="form-select form-select-sm w-auto border-0 bg-light">
                                <option value="days">7 derniers jours</option>
                                <option value="this_month">Ce mois-ci</option>
                                <option value="months">12 derniers mois</option>
                                <option value="custom">Période personnalisée...</option>
                            </select>

                            <div id="customDateRange" class="d-none animate__animated animate__fadeIn d-flex gap-2">
                                <input type="date" id="startDate" class="form-control form-control-sm">
                                <input type="date" id="endDate" class="form-control form-control-sm">
                                <button id="applyCustomDate" class="btn btn-sm btn-primary">Ok</button>
                            </div>
                        </div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="tresorerieChart"></canvas>
                    </div>
                    <div class="table-responsive mt-4">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Entrées</th>
                                    <th class="text-end">Sorties</th>
                                    <th class="text-end">Flux net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tresorerieSeptJours as $jour)
                                    <tr>
                                        <td>{{ $jour['date'] }}</td>
                                        <td class="text-end text-success">{{ number_format($jour['entrees'], 0, ',', ' ') }} F</td>
                                        <td class="text-end text-danger">{{ number_format($jour['sorties'], 0, ',', ' ') }} F</td>
                                        <td class="text-end fw-semibold {{ $jour['flux_net'] < 0 ? 'text-danger' : '' }}">
                                            {{ number_format($jour['flux_net'], 0, ',', ' ') }} F
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted mt-2">Les flux reflètent les écritures du journal ; ils ne constituent pas un solde bancaire consolidé.</small>
                </div>
            </div>
        @endcan

        {{-- ALERTES ET ACTIONS RAPIDES --}}
        <!-- <div class="{{ auth()->user()->can('treasury.view') ? 'col-lg-4' : 'col-12' }}">
            <div class="card border-0 shadow-sm p-4 h-100">
                @can('credits.view')
                <h5 class="fw-bold mb-4 text-danger"><i class="bi bi-shield-exclamation me-2"></i>Alertes Pénalités</h5>
                <div class="table-responsive mb-4" style="max-height: 300px;">
                    <table class="table table-sm table-hover align-middle">
                        <tbody>
                            @forelse($topPenalites as $credit)
                            <tr>
                                <td>
                                    <div class="fw-bold small">{{ $credit->client->nom }}</div>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">{{ $credit->carnet->numero }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="text-danger fw-bold small">+ {{ number_format($credit->penalty_amount, 0, ',', ' ') }} F</div>
                                    <small class="text-muted" style="font-size: 0.7rem;">Agent: {{ $credit->agent->name ?? 'N/A' }}</small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.carnets.show', $credit->carnet_id) }}" class="btn btn-sm btn-light rounded-circle">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">Aucune pénalité en cours.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endcan

                @can('agents.view')
                {{-- ALERTES AGENTS PLAFOND --}}
                <div class="mt-4">
                    <h5 class="fw-bold mb-3 text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Alertes Agents</h5>
                    <div class="table-responsive mb-3" style="max-height: 200px;">
                        <table class="table table-sm table-hover align-middle">
                            <tbody>
                                @php
                                    $agentsPlafond = \App\Models\Agent::where('portefeuille_virtuel', '>', 1000000)->get();
                                @endphp
                                @forelse($agentsPlafond as $agent)
                                <tr>
                                    <td>
                                        <div class="fw-bold small">{{ $agent->nom }}</div>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">{{ $agent->code_agent }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="text-warning fw-bold small">{{ number_format($agent->portefeuille_virtuel, 0, ',', ' ') }} F</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Plafond dépassé</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.agents.show', $agent->id) }}" class="btn btn-sm btn-warning rounded-circle">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-2 text-muted small">Aucun agent en alerte plafond.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @endcan

                <div class="mt-auto">
                    <h5 class="fw-bold mb-3">Actions Rapides</h5>
                    <div class="d-grid gap-2">
                        @can('clients.create')
                            <a href="{{ route('admin.clients.create') }}" class="btn btn-outline-primary text-start p-2">
                                <i class="bi bi-person-plus me-2"></i> Nouveau Client
                            </a>
                        @endcan
                        @can('agents.view')
                            <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-success text-start p-2">
                                <i class="bi bi-person-gear me-2"></i> Gérer les Agents
                            </a>
                        @endcan
                        @can('commissions.view')
                            <a href="{{ route('admin.bonuses.index') }}" class="btn btn-outline-warning text-start p-2">
                                <i class="bi bi-cash-stack me-2"></i> Bonus & Commissions
                            </a>
                        @endcan
                        @can('carnets.view')
                            <a href="{{ route('admin.carnets.index') }}" class="btn btn-outline-dark text-start p-2">
                                <i class="bi bi-journal-bookmark-fill me-2"></i> Gérer les Carnets
                            </a>
                        @endcan
                        <a href="#" class="btn btn-outline-secondary text-start p-2">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Rapports du jour
                        </a>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</div>

<style>
    .card { transition: transform 0.2s, box-shadow 0.2s; }
    .card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1)!important; }
    .bg-primary-subtle { background-color: #cfe2ff; }
    .bg-warning-subtle { background-color: #fff3cd; }
    .bg-danger-subtle { background-color: #f8d7da; }
</style>

@can('treasury.view')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('tresorerieChart').getContext('2d');

    const dataSets = {
        days: {
            labels: {!! json_encode($dates) !!},
            entrees: {!! json_encode($entreesData) !!},
            sorties: {!! json_encode($sortiesData) !!}
        },
        this_month: {
            labels: {!! json_encode($thisMonthLabels) !!},
            entrees: {!! json_encode($thisMonthEntrees) !!},
            sorties: {!! json_encode($thisMonthSorties) !!}
        },
        months: {
            labels: {!! json_encode($monthsLabels) !!},
            entrees: {!! json_encode($monthsEntrees) !!},
            sorties: {!! json_encode($monthsSorties) !!}
        }
    };

    let currentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dataSets.days.labels,
            datasets: [
                {
                    label: 'Entrées',
                    data: dataSets.days.entrees,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.05)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Sorties',
                    data: dataSets.days.sorties,
                    borderColor: '#0d6efd',
                    borderDash: [5, 5],
                    borderWidth: 3,
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => v.toLocaleString() + ' F' }
                }
            }
        }
    });

    // Gestion des filtres
    const filterSelect = document.getElementById('chartFilter');
    const customRange = document.getElementById('customDateRange');

    filterSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customRange.classList.remove('d-none');
        } else {
            customRange.classList.add('d-none');
            const data = dataSets[this.value];
            if(data) {
                currentChart.data.labels = data.labels;
                currentChart.data.datasets[0].data = data.entrees;
                currentChart.data.datasets[1].data = data.sorties;
                currentChart.update();
            }
        }
    });

    // Filtre personnalisé via AJAX
    document.getElementById('applyCustomDate').addEventListener('click', function() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;

        if (!start || !end) {
            Swal.fire('Période incomplète', 'Veuillez sélectionner les deux dates.', 'warning');
            return;
        }

        fetch(`{{ route('admin.dashboard.chart-data') }}?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`, {
            headers: { Accept: 'application/json' }
        })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Impossible de charger les mouvements pour cette période.');
                }
                return data;
            })
            .then(data => {
                currentChart.data.labels = data.labels;
                currentChart.data.datasets[0].data = data.entrees;
                currentChart.data.datasets[1].data = data.sorties;
                currentChart.update();
            })
            .catch(error => {
                Swal.fire('Erreur', error.message, 'error');
            });
    });
</script>
@endcan
@endsection
