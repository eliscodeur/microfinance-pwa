@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        @can('payroll.pay')
            @php $cashWarningMessage = 'Les salaires approuvés ne peuvent être payés qu’avec une session de caisse ouverte.'; @endphp
            @include('admin.partials.cash-warning-banner')
        @endcan
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1">Préparation et règlement des salaires</h2>
                <p class="text-muted mb-0">Les bulletins sont préparés, approuvés puis payés par des actions distinctes.
                </p>
            </div>
            <!-- @php
            $christiane = collect($payrolls)->first(fn($p) => str_contains(strtolower($p->agent->nom), 'christiane'));
        @endphp
        @dump($christiane) -->
        </div>
        {{-- @dump($payrolls) --}}
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <!-- Filtre de période et préparation des bulletins -->
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
                                $statut = strtolower(str_replace(' ', '_', $item->statut ?? ''));
                                return in_array($statut, ['en_attente', 'rejete', 'rejeté'], true);
                            });
                        @endphp

                        @php
                            $estMoisActuelOuFutur =
                                $annee > now()->year || ($annee == now()->year && $mois >= now()->month);
                        @endphp

                        @can('payroll.prepare')
                            @if ($estMoisActuelOuFutur)
                                <div class="w-100 alert alert-warning py-2 mb-0 small text-center">
                                    <i class="bi bi-exclamation-triangle me-1"></i> La préparation de la paie n'est possible qu'à
                                    la fin du mois.
                                </div>
                            @elseif ($contientDesAttentes)
                                <form action="{{ route('admin.payrolls.store') }}" method="POST" class="w-100"
                                    id="formValiderTout">
                                    @csrf
                                    {{-- On fusionne l'année et le mois au format YYYY-MM attendu par le contrôleur --}}
                                    <input type="hidden" name="periode"
                                        value="{{ $annee . '-' . str_pad($mois, 2, '0', STR_PAD_LEFT) }}">

                                    <button type="button" class="btn btn-success w-100" id="btnValiderTout">
                                        <i class="bi bi-calculator me-1"></i> Préparer les salaires
                                    </button>
                                </form>

                                {{-- Script SweetAlert2 --}}
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.getElementById('btnValiderTout').addEventListener('click', function(e) {
                                        Swal.fire({
                                            title: 'Préparer les bulletins ?',
                                            text: "Cette action calcule et enregistre les bulletins de la période. Elle ne déclenche aucun paiement.",
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#28a745', // Vert Bootstrap
                                            cancelButtonColor: '#d33', // Rouge
                                            confirmButtonText: 'Oui, préparer',
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
                                    ✓ Aucun bulletin à préparer
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
                <table id="payrollsTable" class="table table-striped table-bordered dt-responsive nowrap align-middle"
                    style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Agent</th>
                            <th class="text-end">Salaire de base</th>
                            <th class="text-end">Commissions (Cycles)</th>
                            <th class="text-end">Commissions (Carnet)</th>
                            <th class="text-end">Commissions (Travail)</th>
                            <th class="text-end">Bonus</th>
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
                                            {{ $payroll->agent->nom ?? '---' }}
                                        </span>
                                        <span class="text-muted font-monospace" style="font-size: 0.75rem;">
                                            N° {{ $payroll->agent->code_agent }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($payroll->salaire_base ?? 0, 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($payroll->commission_cycle ?? 0, 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($payroll->commission_carnet ?? 0, 0, ',', ' ') }}
                                </td>
                                <td class="text-end">{{ number_format($payroll->commission_travail ?? 0, 0, ',', ' ') }}
                                </td>
                                <td class="text-end">{{ number_format($payroll->bonus ?? 0, 0, ',', ' ') }}</td>

                                <!-- Colonne Avance sur salaire déduite -->
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
                                        $statutClass = match (strtolower($payroll->statut ?? '')) {
                                            'valide' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'paye' => 'bg-success-subtle text-success border border-success-subtle',
                                            'brouillon' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                            'rejete' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            default => 'bg-light text-muted border',
                                        };
                                        $statutLabel = match (strtolower($payroll->statut ?? '')) {
                                            'en_attente' => 'À préparer',
                                            'brouillon' => 'À approuver',
                                            'valide' => 'Approuvé - à payer',
                                            'paye' => 'Payé',
                                            'rejete' => 'Rejeté - à reprendre',
                                            default => ucfirst($payroll->statut ?? ''),
                                        };
                                    @endphp
                                    <span class="badge {{ $statutClass }} fw-normal">
                                        {{ $statutLabel }}
                                    </span>
                                    @if($payroll->created_by_name || $payroll->validated_by_name || $payroll->paid_by_name || $payroll->rejected_by_name)
                                        <div class="small text-muted mt-1">
                                            @if($payroll->created_by_name)<div>Préparé : {{ $payroll->created_by_name }}</div>@endif
                                            @if($payroll->validated_by_name)<div>Approuvé : {{ $payroll->validated_by_name }}</div>@endif
                                            @if($payroll->paid_by_name)<div>Payé : {{ $payroll->paid_by_name }}</div>@endif
                                            @if($payroll->rejected_by_name)<div>Rejeté : {{ $payroll->rejected_by_name }}</div>@endif
                                            @if($payroll->rejection_reason)<div>Motif : {{ $payroll->rejection_reason }}</div>@endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        @if (in_array($payroll->statut, ['en_attente', 'rejete'], true))
                                            {{-- Bouton de prévisualisation pour les salaires en attente --}}
                                            <a href="{{ route('admin.payrolls.details', ['agent' => $payroll->agent->ulid, 'mois' => $mois, 'annee' => $annee]) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Prévisualiser et ajuster">
                                                <i class="bi bi-file-earmark-text me-1"></i> Détails
                                            </a>

                                            @if ($estMoisActuelOuFutur)
                                                <span class="text-muted small d-inline-flex align-items-center"
                                                    title="La préparation n'est possible qu'après la clôture du mois">
                                                    <i class="bi bi-lock me-1"></i> Verrouillé
                                                </span>
                                            @endif
                                        @elseif ($payroll->statut === 'brouillon')
                                            @if ($payroll->salaire_id)
                                                @can('payroll.approve')
                                                    <form action="{{ route('admin.payrolls.approve', $payroll->salaire_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approuver ce bulletin ? Le paiement restera à effectuer séparément.')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-sm btn-outline-primary" type="submit">Approuver</button>
                                                    </form>
                                                @endcan
                                                @can('payroll.reject')
                                                    <form action="{{ route('admin.payrolls.reject', $payroll->salaire_id) }}" method="POST" class="d-inline" onsubmit="return askRejectionReason(this)">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="motif_rejet">
                                                        <button class="btn btn-sm btn-outline-danger" type="submit">Rejeter</button>
                                                    </form>
                                                @endcan
                                            @endif
                                        @elseif ($payroll->statut === 'valide')
                                            @if ($payroll->salaire_id)
                                                @can('payroll.pay')
                                                    <form action="{{ route('admin.payrolls.pay', $payroll->salaire_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmer le paiement en espèces ? Une dépense et un mouvement de caisse seront créés.')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-sm btn-success" type="submit">Payer</button>
                                                    </form>
                                                @endcan
                                            @endif
                                        @else
                                            {{-- Bouton de consultation du bulletin validé avec l'ULID --}}
                                            <a href="{{ route('admin.payrolls.validated', $payroll->salaire_id) }}"
                                                class="btn btn-sm btn-outline-success"
                                                title="Consulter le bulletin officiel validé">
                                                <i class="bi bi-file-earmark-check me-1"></i> Bulletin
                                            </a>
                                            {{--
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                Payé / Validé
                                            </span> --}}
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
                    targets: -1 // Target la dernière colonne (Actions) au lieu de Salaire Net (7)
                }]
            });
        });
    </script>
    <script>
        function askRejectionReason(form) {
            const reason = window.prompt('Motif du rejet du bulletin (obligatoire) :');
            if (!reason || reason.trim().length < 3) {
                return false;
            }
            form.querySelector('[name="motif_rejet"]').value = reason.trim();
            return confirm('Confirmer le rejet de ce bulletin ?');
        }
    </script>
@endsection
