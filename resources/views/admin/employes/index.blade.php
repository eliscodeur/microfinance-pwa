@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Personnel Administratif</h1>
                <p class="text-muted small mb-0">Gestion des dossiers, des contrats et des rémunérations administratives.</p>
            </div>
            <a href="{{ route('admin.employes.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-person-plus me-1"></i> Enregistrer un Agent
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-secondary">
                            <tr>
                                <th class="ps-4">Agent</th>
                                <th>Fonction</th>
                                <th>Téléphone / Adresse</th>
                                <th>Contrat & Statut</th>
                                <th>Salaire Effectif</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employes as $employe)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $employe->nom }} {{ $employe->prenoms }}</div>
                                        <div class="text-muted small">Embauché(e) le :
                                            {{ $employe->date_embauche ? $employe->date_embauche->format('d/m/Y') : 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-dark px-2 py-1">
                                            {{ $employe->fonction->libelle ?? 'Non définie' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-dark"><i class="bi bi-telephone text-muted me-1"></i>
                                            {{ $employe->telephone }}</div>
                                        <div class="small text-muted"><i class="bi bi-geo-alt text-muted me-1"></i>
                                            {{ $employe->adresse ?? 'Non renseignée' }}</div>
                                    </td>
                                    <td>
                                        @if ($employe->statut_contrat == 'essai')
                                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">Période
                                                d'essai</span>
                                        @elseif($employe->statut_contrat == 'confirme')
                                            <span
                                                class="badge bg-success text-white fw-bold px-2 py-1 text-uppercase">Confirmé</span>
                                        @else
                                            <span
                                                class="badge bg-info text-dark fw-bold px-2 py-1 text-uppercase">{{ $employe->statut_contrat }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">
                                            {{ number_format($employe->salaire_effectif, 0, ',', ' ') }} F CFA
                                        </span>
                                        @if (!$employe->salaire_base)
                                            <div class="text-muted fs-8 fst-italic">(Base fonction)</div>
                                        @else
                                            <div class="text-success fs-8 fst-italic">(Personnalisé/Négocié)</div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Bouton Avance sur Salaire déclenchant le modal unique -->
                                        <button type="button" class="btn btn-sm btn-light text-warning me-1"
                                            data-bs-toggle="modal" data-bs-target="#avanceModal"
                                            data-id="{{ $employe->id }}"
                                            data-code="{{ $employe->code_agent ?? $employe->nom . ' ' . $employe->prenoms }}"
                                            data-store-url="{{ route('admin.employes.avances.store', $employe->id) }}"
                                            title="Avance sur salaire">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>

                                        <a href="{{ route('admin.employes.edit', $employe->ulid) }}"
                                            class="btn btn-sm btn-light text-primary me-1" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form action="{{ route('admin.employes.destroy', $employe->ulid) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Voulez-vous vraiment supprimer cet agent ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger"
                                                title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucun agent administratif
                                        enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'octroi / modification d'avance unique -->
    <div class="modal fade" id="avanceModal" tabindex="-1" aria-labelledby="avanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="avanceModalLabel">
                        <i class="bi bi-wallet2 me-2"></i> <span id="modal-title-text">Octroyer une avance</span> -
                        <span id="modal-agent-code"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Formulaire unique dynamisé via JS -->
                <form id="formAvance" method="POST">
                    @csrf
                    <div id="method_override_container"></div>
                    <input type="hidden" name="agent_id" id="input_agent_id">

                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="montant_total" class="form-label small">Montant Total (FCFA) <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control form-control-sm" id="montant_total"
                                    name="montant_total" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nombre_tranches" class="form-label small">Nombre de tranches <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="nombre_tranches"
                                    name="nombre_tranches" required value="1" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="montant_mensuel_apercu" class="form-label small">Montant mensuel
                                    estimé</label>
                                <input type="text" class="form-control form-control-sm bg-white"
                                    id="montant_mensuel_apercu" readonly placeholder="Auto">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="motif" class="form-label small">Motif</label>
                                <input type="text" class="form-control form-control-sm" id="motif"
                                    name="motif">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitAvance">
                            <i class="bi bi-check-lg"></i> <span id="btn-submit-text">Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const avanceModal = document.getElementById('avanceModal');

            // Calcul automatique du montant mensuel estimé
            const montantTotalInput = document.getElementById('montant_total');
            const nombreTranchesInput = document.getElementById('nombre_tranches');
            const montantMensuelApercu = document.getElementById('montant_mensuel_apercu');

            function calculerMensualite() {
                const total = parseFloat(montantTotalInput.value) || 0;
                const tranches = parseInt(nombreTranchesInput.value) || 1;
                if (tranches > 0 && total > 0) {
                    const mensualite = total / tranches;
                    montantMensuelApercu.value = new Intl.NumberFormat('fr-FR').format(mensualite.toFixed(2)) +
                        ' FCFA';
                } else {
                    montantMensuelApercu.value = '';
                }
            }

            if (montantTotalInput && nombreTranchesInput) {
                montantTotalInput.addEventListener('input', calculerMensualite);
                nombreTranchesInput.addEventListener('input', calculerMensualite);
            }

            // Injection des données de l'employé dans le modal lors de son ouverture
            if (avanceModal) {
                avanceModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget; // Le bouton qui a ouvert le modal

                    const agentId = button.getAttribute('data-id');
                    const agentCode = button.getAttribute('data-code');
                    const storeUrl = button.getAttribute('data-store-url');

                    // Mise à jour des champs du formulaire et des libellés du modal
                    document.getElementById('modal-agent-code').textContent = agentCode;
                    document.getElementById('input_agent_id').value = agentId;
                    document.getElementById('formAvance').action = storeUrl;

                    // Réinitialisation optionnelle des champs du formulaire
                    document.getElementById('montant_total').value = '';
                    document.getElementById('nombre_tranches').value = '1';
                    document.getElementById('montant_mensuel_apercu').value = '';
                    document.getElementById('motif').value = '';
                });
            }
        });
    </script>
@endpush
