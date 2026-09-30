@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1">Salaires & Paie - Employés Administratifs</h2>
                <p class="text-muted mb-0">Visualisation, calcul automatique et validation des bulletins de paie du personnel
                    administratif.</p>
            </div>
        </div>

        <!-- Formulaire de filtre par mois/année et bouton Valider tout -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <!-- 1. FORMULAIRE DE FILTRE (GET) -->
                    <form action="{{ route('admin.payrolls.employes.index') }}" method="GET" id="filterForm"
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
                                return strtolower($item->statut ?? 'en attente') === 'en attente' ||
                                    strtolower($item->statut ?? '') === 'brouillon';
                            });

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
                                <form action="{{ route('admin.payrolls.employes.store') }}" method="POST" class="w-100"
                                    id="formValiderTout">
                                    @csrf
                                    <input type="hidden" name="periode"
                                        value="{{ $annee . '-' . str_pad($mois, 2, '0', STR_PAD_LEFT) }}">

                                    <button type="button" class="btn btn-success w-100" id="btnValiderTout">
                                        <i class="bi bi-check2-all me-1"></i> Valider tout
                                    </button>
                                </form>

                                {{-- Script SweetAlert2 --}}
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.getElementById('btnValiderTout').addEventListener('click', function(e) {
                                        Swal.fire({
                                            title: 'Confirmer la validation globale ?',
                                            text: "Cette action va valider les salaires des employés administratifs et générer les dépenses associées pour cette période.",
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#28a745',
                                            cancelButtonColor: '#d33',
                                            confirmButtonText: 'Oui, tout valider',
                                            cancelButtonText: 'Annuler'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                document.getElementById('formValiderTout').submit();
                                            }
                                        });
                                    });
                                </script>
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
                <table id="payrollsEmployesTable"
                    class="table table-striped table-bordered dt-responsive nowrap align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Employé</th>
                            <th class="text-end">Salaire de base</th>
                            <th class="text-end">Primes / Indemnités</th>
                            <th class="text-end">Salaire Brut</th>
                            <th class="text-end text-danger">Avance déduite</th>
                            <th class="text-end fw-bold">Salaire Net</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($payrolls ?? [] as $payroll)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column align-items-start">
                                        <span class="fw-semibold text-dark">
                                            {{ $payroll->employe->nom ?? '' }}
                                            {{ $payroll->employe->prenoms ?? ($payroll->employe->name ?? '---') }}
                                        </span>
                                        <span class="text-muted font-monospace" style="font-size: 0.75rem;">
                                            {{ $payroll->employe->poste ?? 'Administratif' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($payroll->salaire_base ?? 0, 0, ',', ' ') }}</td>
                                <td class="text-end">
                                    {{ number_format(($payroll->primes_totales ?? 0) + ($payroll->indemnites_totales ?? 0), 0, ',', ' ') }}
                                </td>
                                <td class="text-end">{{ number_format($payroll->salaire_brut ?? 0, 0, ',', ' ') }}</td>

                                <td class="text-end text-danger">
                                    @if (($payroll->avance_deduite ?? 0) > 0)
                                        - {{ number_format($payroll->avance_deduite, 0, ',', ' ') }}
                                    @else
                                        0
                                    @endif
                                </td>

                                <td class="text-end fw-bold text-dark">
                                    {{ number_format($payroll->salaire_net ?? 0, 0, ',', ' ') }}
                                </td>
                                <td>
                                    @php
                                        $statutBrut = strtolower($payroll->statut ?? 'en attente');
                                        $statutClass = match ($statutBrut) {
                                            'validé', 'valide' => 'bg-light text-dark border border-secondary',
                                            'brouillon'
                                                => 'bg-warning-subtle text-warning border border-warning-subtle',
                                            default => 'bg-light text-muted border',
                                        };
                                    @endphp
                                    <span class="badge {{ $statutClass }} fw-normal">
                                        {{ ucfirst($payroll->statut ?? 'En attente') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        @php
                                            $statut = strtolower($payroll->statut ?? 'en attente');
                                            $employeUlid = $payroll->employe->ulid ?? '#';
                                        @endphp

                                        {{-- Si le salaire n'a pas d'ID (pas encore enregistré) ou s'il est en brouillon / en attente --}}
                                        @if (empty($payroll->id) || $statut == 'brouillon' || $statut == 'en attente')
                                            <a href="{{ route('admin.payrolls.employes.details', $employeUlid) }}?mois={{ $mois }}&annee={{ $annee }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i> Détails
                                            </a>
                                        @else
                                            {{-- Sinon, le salaire est validé, on affiche le bouton vers le bulletin officiel --}}
                                            <a href="{{ route('admin.payrolls.employe.validated', $payroll->ulid) }}"
                                                class="btn btn-sm btn-success">
                                                <i class="bi bi-file-earmark-text me-1"></i> Voir le bulletin
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- DataTables gère le contenu vide --}}
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
            $('#payrollsEmployesTable').DataTable({
                language: {
                    emptyTable: "Aucun salaire à afficher pour ce mois.",
                    info: "Affichage de _START_ à _END_ sur _TOTAL_ employés",
                    infoEmpty: "Affichage de 0 à 0 sur 0 employé",
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

                        window.location.href =
                            "{{ url('admin/payrolls/employes/export/excel') }}" +
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('btnValiderTout').addEventListener('click', function(e) {
            e.preventDefault(); // Empêche la soumission directe

            Swal.fire({
                title: 'Confirmation de validation',
                text: "Voulez-vous vraiment valider et comptabiliser les salaires de tous les employés actifs pour cette période ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754', // Vert (Bootstrap Success)
                cancelButtonColor: '#6c757d', // Gris (Bootstrap Secondary)
                confirmButtonText: 'Oui, tout valider',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Si l'utilisateur clique sur "Oui", on soumet le formulaire
                    document.getElementById('formValiderTout').submit();
                }
            });
        });
    </script>
@endsection
