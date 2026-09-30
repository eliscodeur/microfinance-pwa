@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h1 class="h3 text-dark fw-bold mb-0">Gestion des Avances sur Salaire</h1>
                <p class="text-muted small mb-0">Suivi, validation et traitement des demandes d'avances indépendantes du
                    mois.</p>
            </div>
        </div>

        <!-- Filtres rapides par statut -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('admin.payrolls.avance') }}" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="statut" class="col-form-label fw-semibold">Filtrer par statut :</label>
                    </div>
                    <div class="col-auto">
                        <select name="statut" id="statut" class="form-select form-select-sm"
                            onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente
                            </option>
                            <option value="en_cours" {{ request('statut') === 'en_cours' ? 'selected' : '' }}>En cours
                            </option>
                            <option value="soldee" {{ request('statut') === 'soldee' ? 'selected' : '' }}>Soldées</option>
                            <option value="rejetee" {{ request('statut') === 'rejetee' ? 'selected' : '' }}>Rejetées
                            </option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des avances -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date Demande</th>
                                <th>Bénéficiaire (Agent / Employé)</th>
                                <th>Montant Total</th>
                                <th>Mensualité</th>
                                <th>Tranches</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($avances as $avance)
                                @php
                                    // Détermination dynamique du nom et du code/identifiant selon la relation chargée
                                    $beneficiaireNom =
                                        $avance->agent->nom ??
                                        ($avance->employe->nom . ' ' . ($avance->employe->prenoms ?? '') ?? 'N/A');
                                    // $beneficiaireCode = $avance->agent->code_agent ?? ($avance->employe->ulid ?? '-');
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($avance->date_demande)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $beneficiaireNom }}</div>
                                        {{-- <small class="text-muted">{{ $beneficiaireCode }}</small> --}}
                                    </td>
                                    <td>
                                        <strong
                                            class="text-primary">{{ number_format($avance->montant_total, 0, ',', ' ') }}
                                        </strong>
                                    </td>
                                    <td>{{ number_format($avance->montant_mensuel, 0, ',', ' ') }}</td>
                                    <td>{{ $avance->tranches_payees }} / {{ $avance->nombre_tranches }}</td>
                                    <td>{{ $avance->motif ?? '-' }}</td>
                                    <td>
                                        @if ($avance->statut === 'en_attente')
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        @elseif($avance->statut === 'en_cours')
                                            <span class="badge bg-info text-dark">En cours</span>
                                        @elseif($avance->statut === 'soldee')
                                            <span class="badge bg-success">Soldée</span>
                                        @elseif($avance->statut === 'rejetee')
                                            <span class="badge bg-danger">Rejetée</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($avance->statut) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($avance->statut === 'en_attente')
                                            <button type="button" class="btn btn-success btn-sm btn-valider-avance me-1"
                                                data-id="{{ $avance->advance_uid }}" title="Valider l'avance">
                                                <i class="bi bi-check-lg"></i> Valider
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-rejeter-avance"
                                                data-id="{{ $avance->advance_uid }}" title="Rejeter l'avance">
                                                <i class="bi bi-x-lg"></i> Rejeter
                                            </button>
                                        @else
                                            <span class="text-muted small">Aucune action</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucune avance sur salaire trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $avances->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaires cachés pour soumettre les requêtes PATCH via SweetAlert -->
    @foreach ($avances as $avance)
        @if ($avance->statut === 'en_attente')
            <form id="form-valider-{{ $avance->advance_uid }}"
                action="{{ route('admin.payrolls.avance.valider', $avance->advance_uid) }}" method="POST" class="d-none">
                @csrf
                @method('PATCH')
            </form>
            <form id="form-rejeter-{{ $avance->advance_uid }}"
                action="{{ route('admin.payrolls.avance.rejeter', $avance->advance_uid) }}" method="POST" class="d-none">
                @csrf
                @method('PATCH')
            </form>
        @endif
    @endforeach
@endsection

@push('scripts')
    <!-- Script SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validation d'une avance
            document.querySelectorAll('.btn-valider-avance').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Confirmer la validation ?',
                        text: "Voulez-vous valider cette avance sur salaire ?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Oui, valider',
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(`form-valider-${id}`).submit();
                        }
                    });
                });
            });

            // Rejet d'une avance
            document.querySelectorAll('.btn-rejeter-avance').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Êtes-vous sûr ?',
                        text: "Voulez-vous vraiment rejeter cette demande d'avance ?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Oui, rejeter',
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(`form-rejeter-${id}`).submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
