@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">

        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800"></i>Stock de carnets</h1>
                <p class="text-muted small mb-0">Suivi rigoureux des arrivages, des ventes des carnets </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalEntree">
                    <i class="bi bi-plus-circle me-1"></i> Nouvel Arrivage (Entrée)
                </button>
                {{-- <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalSortie">
                    <i class="bi bi-dash-circle me-1"></i> Enregistrer une Vente (Sortie)
                </button> --}}
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION DES KPI PROFESSIONNELS (CARDS)     -->
        <!-- ========================================== -->
        <div class="row g-3 mb-4">
            <!-- 1. Stock Total Disponible -->
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Stock total en réserve</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">{{ number_format($stockTotal, 0, ',', ' ') }}
                                    carnets</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-boxes fs-2 text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Stock de Tontines -->
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Stock carnets tontines</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">{{ number_format($stockTontines, 0, ',', ' ') }}
                                    carnets</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-piggy-bank fs-2 text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Stock de Comptes d'Épargne -->
            <div class="col-xl-4 col-md-12">
                <div class="card border-0 shadow-sm border-start border-info border-4 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Stock comptes d'épargne</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">{{ number_format($stockComptes, 0, ',', ' ') }}
                                    carnets</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-wallet2 fs-2 text-info opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages de succès ou d'erreur -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Veuillez corriger les erreurs dans le formulaire.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- BARRE DE FILTRE GLOBALE (optionnelle ou pour la courbe) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold text-secondary small"><i class="bi bi-filter me-1"></i> Filtrer les données :</span>
                <div class="d-flex align-items-center gap-2">
                    <select id="filtrePeriode" class="form-select form-select-sm">
                        <option value="7_jours">7 derniers jours</option>
                        <option value="ce_mois">Ce mois-ci</option>
                        <option value="12_mois">12 derniers mois</option>
                        <option value="personnalisee">Période personnalisée...</option>
                    </select>

                    <!-- Inputs de dates (masqués par défaut) -->
                    <div id="blocDatesPersonnalisees" class="d-none align-items-center gap-1">
                        <input type="date" id="dateDebut" class="form-control form-control-sm">
                        <span class="small text-muted">à</span>
                        <input type="date" id="dateFin" class="form-control form-control-sm">
                        <button id="btnValiderPersonnalise" class="btn btn-primary btn-sm">Ok</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIGNE DES GRAPHIQUES (Ligne & Donut) -->
        <div class="row mb-4">
            <!-- Courbe d'évolution des ventes -->
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800 fs-6"><i class="bi bi-graph-up me-2 text-primary"></i>Évolution des
                            ventes</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="evolutionCarnetsChart" style="max-height: 400px;min-height:400px"></canvas>
                    </div>
                </div>
            </div>

            <!-- Graphique Donut par Catégories de Tontine -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-gray-800 fs-6"><i
                                class="bi bi-pie-chart-fill me-2 text-success"></i>Répartition par catégories de tontine
                        </h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="tontineDonutChart" style="max-height: 400px;min-height:400px"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- NAVIGATION DES ONGLETS -->
        <ul class="nav nav-tabs mb-4" id="stockTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-success" id="entrees-tab" data-bs-toggle="tab"
                    data-bs-target="#entrees-tab-pane" type="button" role="tab" aria-controls="entrees-tab-pane"
                    aria-selected="true">
                    <i class="bi bi-arrow-down-left-circle me-1"></i> Entrées (Arrivages / Achats)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-primary" id="sorties-tab" data-bs-toggle="tab"
                    data-bs-target="#sorties-tab-pane" type="button" role="tab" aria-controls="sorties-tab-pane"
                    aria-selected="false">
                    <i class="bi bi-arrow-up-right-circle me-1"></i> Sorties (Ventes / Attributions)
                </button>
            </li>
        </ul>

        <!-- CONTENU DES ONGLETS -->
        <div class="tab-content" id="stockTabContent">
            <!-- 1. ONGLET DES ENTRÉES -->
            <div class="tab-pane fade show active" id="entrees-tab-pane" role="tabpanel" aria-labelledby="entrees-tab"
                tabindex="0">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type de Carnet</th>
                                        <th>Quantité initiale en stock</th>
                                        <th>Quantité restante en stock</th>
                                        <th>Prix U. Achat</th>
                                        <th>Motif / Remarque</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($entrees as $entree)
                                        <tr>
                                            <td>{{ $entree->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                @if ($entree->categoryTontine)
                                                    Tontine :
                                                    {{ $entree->categoryTontine->libelle }}
                                                @else
                                                    Carnet d'Épargne
                                                @endif
                                            </td>
                                            <td class="fw-bold text-success">{{ $entree->quantite }}</td>
                                            <td class="fw-bold text-primary">{{ $entree->quantite_restante }}</td>
                                            <td>{{ number_format($entree->prix_unitaire_achat, 0, ',', ' ') }} F</td>
                                            <td class="text-muted small">{{ $entree->motif ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">Aucune entrée
                                                enregistrée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $entrees->links() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. ONGLET DES SORTIES -->
            <div class="tab-pane fade" id="sorties-tab-pane" role="tabpanel" aria-labelledby="sorties-tab"
                tabindex="0">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type de Carnet</th>
                                        <th>Quantité</th>
                                        <th>Prix U. Vente</th>
                                        <th>Motif / Client</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sorties as $sortie)
                                        <tr>
                                            <td>{{ $sortie->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if ($sortie->categoryTontine)
                                                    <span class="badge bg-info text-dark">Tontine :
                                                        {{ $sortie->categoryTontine->libelle }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Carnet d'Épargne</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-danger">-{{ $sortie->quantite }}</td>
                                            <td>{{ $sortie->prix_unitaire_vente ? number_format($sortie->prix_unitaire_vente, 0, ',', ' ') . ' F' : '-' }}
                                            </td>
                                            <td class="text-muted small">{{ $sortie->motif ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">Aucune sortie
                                                enregistrée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $sorties->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL : Enregistrer une Entrée -->
    <div class="modal fade" id="modalEntree" tabindex="-1" aria-labelledby="modalEntreeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('admin.stocks.entree') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalEntreeLabel"><i class="bi bi-plus-circle me-2"></i>Enregistrer un
                        arrivage (Entrée)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type_carnet_select" class="form-label">Type de carnet <span
                                class="text-danger">*</span></label>
                        <select name="type_carnet" id="type_carnet_select" class="form-select" required
                            onchange="toggleCategorieSelect()">
                            <option value="compte">Carnet d'Épargne</option>
                            <option value="tontine">Tontine</option>
                        </select>
                    </div>

                    <div class="mb-3" id="wrapper_categorie" style="display: none;">
                        <label for="categories_tontine_id" class="form-label">Catégorie de tontine <span
                                class="text-danger">*</span></label>
                        <select name="categories_tontine_id" id="categories_tontine_id" class="form-select">
                            <option value="">-- Sélectionner une catégorie --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantite_entree" class="form-label">Quantité reçue <span
                                class="text-danger">*</span></label>
                        <input type="number" name="quantite" id="quantite_entree" class="form-control" min="1"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="prix_unitaire_achat" class="form-label">Prix d'achat unitaire (F CFA) <span
                                class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="prix_unitaire_achat" id="prix_unitaire_achat"
                            class="form-control" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="motif_entree" class="form-label">Motif / Référence facture</label>
                        <input type="text" name="motif" id="motif_entree" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success btn-sm">Enregistrer l'entrée</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inclusion de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Scripts JavaScript -->
    <script>
        function toggleCategorieSelect() {
            const typeSelect = document.getElementById('type_carnet_select');
            const wrapperCategorie = document.getElementById('wrapper_categorie');
            const categorieSelect = document.getElementById('categories_tontine_id');

            if (typeSelect.value === 'tontine') {
                wrapperCategorie.style.display = 'block';
                categorieSelect.setAttribute('required', 'required');
            } else {
                wrapperCategorie.style.display = 'none';
                categorieSelect.removeAttribute('required');
                categorieSelect.value = '';
            }
        }

        let lineChart = null;
        let donutChart = null;

        // Charger la courbe d'évolution
        function chargerLigneGraphique(periode = '7_jours', dateDebut = '', dateFin = '') {
            let url = "{{ route('admin.evolution-ventes') }}?periode=" + periode;
            if (periode === 'personnalisee') {
                url += `&date_debut=${dateDebut}&date_fin=${dateFin}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('evolutionCarnetsChart').getContext('2d');
                    if (lineChart) lineChart.destroy();

                    lineChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                    label: 'Ventes carnet de tontine',
                                    data: data.tontine,
                                    borderColor: 'rgb(40, 167, 69)',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Ventes carnet d\'épargne',
                                    data: data.compte,
                                    borderColor: 'rgb(0, 123, 255)',
                                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                });
        }

        // Charger le graphique Donut des catégories de tontine
        function chargerDonutGraphique(periode = '7_jours', dateDebut = '', dateFin = '') {
            let url = "{{ route('admin.repartition-tontines') }}?periode=" + periode;
            if (periode === 'personnalisee') {
                url += `&date_debut=${dateDebut}&date_fin=${dateFin}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('tontineDonutChart').getContext('2d');
                    if (donutChart) donutChart.destroy();

                    donutChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels, // Noms des catégories de tontine
                            datasets: [{
                                data: data.values, // Quantités vendues par catégorie
                                backgroundColor: [
                                    '#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12
                                    }
                                }
                            }
                        }
                    });
                });
        }

        // Gestion de la période globale
        document.getElementById('filtrePeriode').addEventListener('change', function() {
            const bloc = document.getElementById('blocDatesPersonnalisees');
            if (this.value === 'personnalisee') {
                bloc.classList.remove('d-none');
                bloc.classList.add('d-flex');
            } else {
                bloc.classList.remove('d-flex');
                bloc.classList.add('d-none');
                chargerLigneGraphique(this.value);
                chargerDonutGraphique(this.value);
            }
        });

        document.getElementById('btnValiderPersonnalise').addEventListener('click', function() {
            const debut = document.getElementById('dateDebut').value;
            const fin = document.getElementById('dateFin').value;

            if (!debut || !fin) {
                alert('Veuillez sélectionner une date de début et de fin.');
                return;
            }
            chargerLigneGraphique('personnalisee', debut, fin);
            chargerDonutGraphique('personnalisee', debut, fin);
        });

        // Chargement initial des deux graphiques
        document.addEventListener('DOMContentLoaded', () => {
            chargerLigneGraphique('7_jours');
            chargerDonutGraphique('7_jours');
        });
    </script>
@endsection
