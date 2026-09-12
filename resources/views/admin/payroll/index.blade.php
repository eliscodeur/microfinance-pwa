@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1">Génération et Validation des Salaires & Paie</h2>
                <p class="text-muted mb-0">Visualisation, calcul automatique et validation des éléments de paie mensuelle.
                </p>
            </div>
        </div>
        {{-- @dump($payrolls) --}}
        <!-- Formulaire de filtre par mois/année et bouton Valider tout dynamique -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <!-- 1. FORMULAIRE DE FILTRE (GET) -->
                    <form action="{{ route('admin.payrolls.index') }}" method="GET" id="filterForm"
                        class="col-md-8 row g-3 align-items-end m-0 p-0">
                        <div class="col-md-5">
                            <label for="mois" class="form-label small fw-bold">Mois de paie</label>
                            <select name="mois" id="mois" class="form-select">
                                @for ($m = 1; $m <= 12; $m++)
                                    @php $nomMois = ucfirst(\Carbon\Carbon::create(null, $m, 1)->locale('fr')->monthName); @endphp
                                    <option value="{{ $m }}" {{ isset($mois) && $mois == $m ? 'selected' : '' }}>
                                        {{ $nomMois }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label for="annee" class="form-label small fw-bold">Année</label>
                            <select name="annee" id="annee" class="form-select">
                                @php $anneeCourante = now()->year; @endphp
                                @for ($y = $anneeCourante; $y >= $anneeCourante - 5; $y--)
                                    <option value="{{ $y }}"
                                        {{ isset($annee) && $annee == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-dark w-100">
                                <i class="bi bi-funnel me-1"></i> Filtrer
                            </button>
                        </div>
                    </form>

                    <!-- 2. FORMULAIRE DE VALIDATION GLOBALE (POST) -->
                    <div class="col-md-4 d-flex align-items-end">
                        @php
                            $contientDesAttentes = collect($payrolls ?? [])->contains(function ($item) {
                                return $item->statut === 'En attente';
                            });
                        @endphp

                        @php
                            $estMoisActuelOuFutur =
                                $annee > now()->year || ($annee == now()->year && $mois >= now()->month);
                        @endphp

                        @can('Modifier données')
                            @if ($estMoisActuelOuFutur)
                                <div class="w-100 alert alert-warning py-2 mb-0 small text-center">
                                    <i class="bi bi-exclamation-triangle me-1"></i> La validation de la paie n'est possible qu'à
                                    la fin du mois.
                                </div>
                            @elseif ($contientDesAttentes)
                                <!-- Votre formulaire de validation existant -->
                                <form action="{{ route('admin.payrolls.store') }}" method="POST" class="w-100">
                                    @csrf
                                    <input type="hidden" name="mois" value="{{ $mois }}">
                                    <input type="hidden" name="annee" value="{{ $annee }}">
                                    <button type="submit" class="btn btn-success w-100" id="btnValiderTout">
                                        <i class="bi bi-check2-all me-1"></i> Valider tout
                                    </button>
                                </form>
                            @else
                                <div
                                    class="w-100 d-flex align-items-center justify-content-center bg-success-subtle text-success border border-success rounded px-2 py-2 small fw-bold text-center">
                                    ✓ Paie validée pour cette période
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des résultats -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table id="payrollsTable" class="table table-striped table-bordered dt-responsive nowrap"
                    style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Agent</th>
                            <th>Salaire de base</th>
                            <th>Commissions sur colletes</th>
                            <th>Commissions sur carnet</th>
                            <th>Commissions travail</th>
                            <th>Bonus</th>
                            <th>Salaire Net</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($payrolls ?? [] as $payroll)
                            {{-- @php dd($payroll); @endphp --}}
                            <tr>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <div class="fw-bold">
                                            {{ $payroll->agent->nom ?? '---' }}
                                        </div>
                                        <!-- Affichage du numéro de l'agent dans un badge en bas -->
                                        <span class="badge bg-secondary font-monospace" style="font-size: 0.75rem;">
                                            N° {{ $payroll->agent->code_agent }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($payroll->salaire_base ?? 0, 0, ',', ' ') }} </td>
                                <td class="text-end">{{ number_format($payroll->commission_cycle ?? 0, 0, ',', ' ') }}
                                </td>
                                <td class="text-end">{{ number_format($payroll->commission_carnet ?? 0, 0, ',', ' ') }}
                                </td>
                                <td class="text-end">{{ number_format($payroll->commission_travail ?? 0, 0, ',', ' ') }}
                                </td>
                                <td class="text-end">{{ number_format($payroll->bonus ?? 0, 0, ',', ' ') }} </td>
                                <td class="fw-bold text-success text-end">
                                    {{ number_format($payroll->salaire_net ?? 0, 0, ',', ' ') }} </td>
                                <td>
                                    <span class="badge bg-{{ $payroll->statut == 'Validé' ? 'success' : 'warning' }}">
                                        {{ $payroll->statut ?? 'En attente' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        {{-- Le bouton de visualisation est toujours accessible via l'ulid ou les paramètres de l'agent --}}
                                        <a href="{{ route('admin.payrolls.details', ['agent' => $payroll->agent->ulid, 'mois' => $mois, 'annee' => $annee]) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-text me-1"></i> Voir les détails
                                        </a>

                                        @if ($payroll->statut == 'En attente')
                                            @if (!$estMoisActuelOuFutur)
                                                <button type="button" class="btn btn-sm btn-outline-success">
                                                    Valider
                                                </button>
                                            @else
                                                <span class="badge bg-secondary d-inline-flex align-items-center"
                                                    title="La validation n'est possible qu'après la clôture du mois">
                                                    <i class="bi bi-lock me-1"></i> En cours / Verrouillé
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- DataTables gère l'affichage vide --}}
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Scripts DataTables & Dépendances --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#payrollsTable').DataTable({
                language: {
                    emptyTable: "Aucun salaire à afficher pour ce mois.",
                    info: "Affichage de _START_ à _END_ sur _TOTAL_ agents",
                    infoEmpty: "Affichage de 0 à 0 sur 0 agent",
                    infoFiltered: "(filtré à partir de _MAX_ éléments au total)",
                    lengthMenu: "Afficher _MENU_ éléments",
                    loadingRecords: "Chargement...",
                    processing: "Traitement...",
                    search: "Rechercher :",
                    zeroRecords: "Aucun résultat trouvé",
                    paginate: {
                        first: "Premier",
                        last: "Dernier",
                        next: "Suivant",
                        previous: "Précédent"
                    }
                },
                pagingType: 'simple_numbers',
                dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                buttons: [{
                    text: '<i class="fas fa-file-excel me-1"></i> Exporter (Excel)',
                    className: 'btn btn-success btn-sm',
                    action: function(e, dt) {
                        var searchParam = encodeURIComponent(dt.search());
                        var moisParam = $('#mois').val() || '';
                        var anneeParam = $('#annee').val() || '';

                        window.location.href = "{{ url('admin/payrolls/export/excel') }}" +
                            "?search=" + searchParam +
                            "&mois=" + moisParam +
                            "&annee=" + anneeParam;
                    }
                }],
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tous"]
                ],
                order: [
                    [0, 'asc']
                ],
                columnDefs: [{
                    orderable: false,
                    searchable: false,
                    targets: 7
                }]
            });
        });
    </script>
@endsection
