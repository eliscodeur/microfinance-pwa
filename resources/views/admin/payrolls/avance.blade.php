@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4">
        @php $cashWarningMessage = "Le décaissement est impossible sans une caisse ouverte. L’approbation ne décaisse pas l’argent."; @endphp
        @include('admin.partials.cash-warning-banner')
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h1 class="h3 text-dark fw-bold mb-0">Gestion des avances sur salaire</h1>
                <p class="text-muted small mb-0">Le demandeur, l’approbateur et le caissier sont tracés séparément.</p>
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
        @inject('sessionService', 'App\Services\CashSessionService')
        @php
            $hasCashOpen = auth()->check() && $sessionService->hasActiveSession(auth()->id());
        @endphp
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
                                <th>Traçabilité</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($avances as $avance)
                                @php
                                    // Détermination dynamique du nom et du code/identifiant selon la relation chargée
                                    $beneficiaireNom = $avance->agent?->nom
                                        ?? trim(($avance->employe?->nom ?? '') . ' ' . ($avance->employe?->prenoms ?? ''))
                                        ?: 'N/A';
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
                                        @if ($avance->statut === 'en_attente' && $avance->approval_status === 'approved')
                                            <span class="badge bg-primary">Approuvée - à décaisser</span>
                                        @elseif ($avance->statut === 'en_attente')
                                            <span class="badge bg-warning text-dark">En attente d’approbation</span>
                                        @elseif($avance->statut === 'en_cours')
                                            <span class="badge bg-info text-dark">Décaissée - remboursement en cours</span>
                                        @elseif($avance->statut === 'soldee')
                                            <span class="badge bg-success">Soldée</span>
                                        @elseif($avance->statut === 'rejetee')
                                            <span class="badge bg-danger">Rejetée</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($avance->statut) }}</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <div>Demande : {{ $avance->creator?->name ?? '—' }}</div>
                                        @if($avance->approved_at)
                                            <div>Approbation : {{ $avance->approver?->name ?? '—' }} ({{ $avance->approved_at->format('d/m/Y H:i') }})</div>
                                        @endif
                                        @if($avance->rejected_at)
                                            <div>Rejet : {{ $avance->rejecter?->name ?? '—' }} ({{ $avance->rejected_at->format('d/m/Y H:i') }})</div>
                                            @if($avance->rejection_reason)
                                                <div>Motif : {{ $avance->rejection_reason }}</div>
                                            @endif
                                        @endif
                                        @if($avance->disbursed_at)
                                            <div>Décaissement : {{ $avance->disburser?->name ?? '—' }} ({{ $avance->disbursed_at->format('d/m/Y H:i') }})</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($avance->statut === 'en_attente' && $avance->approval_status === 'pending')
                                            @can('advances.approve')
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-valider-avance me-1"
                                                    data-id="{{ $avance->advance_uid }}">Approuver</button>
                                            @endcan
                                            @can('advances.reject')
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-rejeter-avance"
                                                    data-id="{{ $avance->advance_uid }}">Rejeter</button>
                                            @endcan
                                        @elseif ($avance->statut === 'en_attente' && $avance->approval_status === 'approved')
                                            @can('advances.disburse')
                                                @if($hasCashOpen)
                                                    <button type="button" class="btn btn-success btn-sm btn-decaisser-avance"
                                                        data-id="{{ $avance->advance_uid }}">Décaisser</button>
                                                @else
                                                    <span class="text-muted small">Caisse à ouvrir</span>
                                                @endif
                                            @endcan
                                        @else
                                            <span class="text-muted small">Aucune action</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Aucune avance sur salaire trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $avances->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaires cachés pour soumettre les requêtes PATCH via SweetAlert -->
    @foreach ($avances as $avance)
        @if ($avance->statut === 'en_attente' && $avance->approval_status === 'pending')
            <form id="form-valider-{{ $avance->advance_uid }}"
                action="{{ route('admin.payrolls.avance.valider', $avance->advance_uid) }}" method="POST" class="d-none">
                @csrf
                @method('PATCH')
            </form>
            <form id="form-rejeter-{{ $avance->advance_uid }}"
                action="{{ route('admin.payrolls.avance.rejeter', $avance->advance_uid) }}" method="POST" class="d-none">
                @csrf
                @method('PATCH')
                <input type="hidden" name="motif_rejet">
            </form>
        @elseif ($avance->statut === 'en_attente' && $avance->approval_status === 'approved')
            <form id="form-decaisser-{{ $avance->advance_uid }}"
                action="{{ route('admin.payrolls.avance.decaisser', $avance->advance_uid) }}" method="POST" class="d-none">
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
                        cancelButtonText: 'Annuler',
                        input: 'textarea',
                        inputLabel: 'Motif du rejet',
                        inputPlaceholder: 'Expliquez pourquoi la demande est rejetée...',
                        inputValidator: (value) => !value || value.trim().length < 3 ? 'Veuillez saisir un motif (3 caractères minimum).' : undefined
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById(`form-rejeter-${id}`);
                            form.querySelector('[name="motif_rejet"]').value = result.value.trim();
                            form.submit();
                        }
                    });
                });
            });

            // Le gestionnaire de décaissement est indépendant de celui du rejet.
            document.querySelectorAll('.btn-decaisser-avance').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const form = document.getElementById(`form-decaisser-${id}`);
                    if (!form) {
                        window.alert('Le formulaire de décaissement est introuvable. Actualisez la page.');
                        return;
                    }

                    const submitForm = () => form.submit();
                    if (typeof Swal === 'undefined') {
                        if (window.confirm('La dépense et le mouvement de caisse seront créés immédiatement. Confirmer le décaissement ?')) {
                            submitForm();
                        }
                        return;
                    }

                    Swal.fire({
                        title: 'Confirmer le décaissement ?',
                        text: "La dépense et le mouvement de caisse seront créés immédiatement.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Oui, décaisser',
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitForm();
                        }
                    });
                });
            });
        });
    </script>
@endpush
