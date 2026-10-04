@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Trésorerie & Dépenses</h1>
                <p class="text-muted small mb-0">Suivi rigoureux des décaissements, charges opérationnelles et notes de
                    frais.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#depenseModal">
                    <i class="bi bi-plus-circle me-1"></i> Enregistrer une dépense
                </button>
            </div>
        </div>

        <!-- KPI / Cartes de Synthèse Financière -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Dépenses (Mois en cours)
                                </div>
                                <div class="h5 mb-0 fw-bold text-dark">
                                    {{ number_format($depenses->sum('montant'), 0, ',', ' ') }} <small
                                        class="text-muted">FCFA</small>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-wallet2 fa-2x text-primary fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Nombre d'Opérations</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $depenses->count() }} décaissement(s)</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-receipt fa-2x text-success fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card border-0 shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Conformité SYSCOHADA</div>
                                <div class="h5 mb-0 fw-bold text-dark">Actif & Paramétré</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-shield-check fa-2x text-info fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Formulaire de Filtres -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light rounded">
                <form method="GET" action="{{ route('admin.depenses.index') }}" class="row g-3 align-items-end">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="categorie_id" class="form-label fw-bold">Filtrer par catégorie</label>
                            <select name="categorie_id" id="categorie_id" class="form-select py-2">
                                <option value="">Toutes les catégories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->ulid }}"
                                        {{ request('categorie_id') == $cat->ulid ? 'selected' : '' }}>
                                        {{ $cat->libelle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="periode" class="form-label fw-bold">Période (Du - Au)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white px-3"><i class="bi bi-calendar-range"></i></span>
                                <input type="text" name="periode" id="periode"
                                    class="form-control py-2 date-range-picker" placeholder="Sélectionner une période"
                                    value="{{ request('periode') }}">
                            </div>
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                                <i class="bi bi-filter me-1"></i> Filtrer
                            </button>
                            <a href="{{ route('admin.depenses.index') }}" class="btn btn-outline-secondary py-2">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Tableau Principal des Dépenses -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Journal des Décaissements</h6>
                {{-- <span class="badge bg-light text-secondary border">Mise à jour en temps réel</span> --}}
            </div>
            <div class="card-body px-0 pb-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Poste Analytique / Catégorie</th>
                                <th>Bénéficiaire</th>
                                <th>Motif & Référence</th>
                                <th>Mode</th>
                                <th class="text-end">Montant (FCFA)</th>
                                <th class="text-center">Auteur</th>
                                {{-- <th class="text-end pe-4">Actions</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($depenses as $depense)
                                <tr>
                                    <td class="ps-4 fw-semibold text-secondary">
                                        {{ $depense->date_depense->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $depense->categorie->libelle ?? 'N/A' }}</div>
                                        <small class="text-muted">
                                            <i
                                                class="bi bi-folder2 me-1"></i>{{ $depense->categorie->typeCharge->libelle ?? 'Type inconnu' }}
                                            @if ($depense->categorie->code_analytique)
                                                <span class="badge bg-light text-dark border ms-1"
                                                    style="font-size: 0.65rem;">{{ $depense->categorie->code_analytique }}</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $depense->beneficiaire ?: 'Non spécifié' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-dark">{{ Str::limit($depense->motif, 35) }}</div>
                                        @if ($depense->reference_piece)
                                            <small class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>Réf:
                                                {{ $depense->reference_piece }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-secondary text-dark border bg-light">
                                            {{ $depense->mode_paiement }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-danger fs-6">
                                            - {{ number_format($depense->montant, 0, ',', ' ') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-muted border"
                                            title="{{ $depense->user->name ?? 'Système' }}">
                                            {{ Str::limit($depense->user->name ?? 'Admin', 10) }}
                                        </span>
                                    </td>
                                    {{-- <td class="text-end pe-4">
                                        <div class="btn-group" role="group">
                                            <form action="{{ route('admin.depenses.destroy', $depense->ulid) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Attention : Voulez-vous vraiment supprimer cette écriture de dépense ? Cette action modifiera la comptabilité.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"
                                                    title="Supprimer l'écriture">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td> --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <div class="mb-2"><i class="bi bi-inbox fs-1 text-muted"></i></div>
                                        <p class="mb-0">Aucun décaissement enregistré pour le moment.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-end">
                {{ $depenses->links() }}
            </div>
        </div>
    </div>

    <!-- MODAL D'ENREGISTREMENT DE DÉPENSE -->
    <div class="modal fade" id="depenseModal" tabindex="-1" aria-labelledby="depenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('admin.depenses.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold text-primary" id="depenseModalLabel">
                            <i class="bi bi-cash-coin me-2"></i>Nouveau décaissement / Dépense
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label for="categories_charge_id" class="form-label fw-semibold">Poste de charge /
                                    Catégorie <span class="text-danger">*</span></label>
                                <select name="categories_charge_id" id="categories_charge_id" class="form-select select2"
                                    required>
                                    <option value="">-- Sélectionner un poste comptable --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">
                                            [{{ $cat->typeCharge->libelle ?? 'Général' }}] {{ $cat->libelle }}
                                            @if ($cat->code_analytique)
                                                ({{ $cat->code_analytique }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label for="montant" class="form-label fw-semibold">Montant (FCFA) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="montant" id="montant" class="form-control" required
                                        placeholder="0">
                                    <span class="input-group-text bg-light">XOF</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_depense" class="form-label fw-semibold">Date de l'opération <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="date_depense" id="date_depense" class="form-control"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="mode_paiement" class="form-label fw-semibold">Mode de décaissement <span
                                        class="text-danger">*</span></label>
                                <select name="mode_paiement" id="mode_paiement" class="form-select" required>
                                    <option value="Espèces">Espèces (Caisse)</option>
                                    <option value="Virement Bancaire">Virement bancaire</option>
                                    <option value="Chèque">Chèque</option>
                                    <option value="Mobile Money">Mobile money (T-Money / Flooz)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="beneficiaire" class="form-label fw-semibold">Bénéficiaire /
                                    Fournisseur</label>
                                <input type="text" name="beneficiaire" id="beneficiaire" class="form-control"
                                    placeholder="Nom de l'entreprise ou personne payée">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="reference_piece" class="form-label fw-semibold">Pièce justificative (N°
                                    Facture/Reçu)</label>
                                <input type="text" name="reference_piece" id="reference_piece" class="form-control"
                                    placeholder="Ex: FAC-2026-894">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="motif" class="form-label fw-semibold">Motif / Justification détaillée <span
                                    class="text-danger">*</span></label>
                            <textarea name="motif" id="motif" rows="3" class="form-control" required
                                placeholder="Décrivez clairement l'objet de la dépense pour l'audit interne..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4">Valider le décaissement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/flatpickr-fr.js') }}"></script>
    <script src="{{ asset('js/imask.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            IMask(document.getElementById('montant'), {
                mask: Number,
                scale: 2,
                signed: false,
                thousandsSeparator: ' ',
                padFractionalZeros: false,
                normalizeZeros: true,
                min: 0
            });
            flatpickr(".date-range-picker", {
                mode: "range",
                locale: "fr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                conjunction: " au "
            });
        });
    </script>
@endsection

@section('scripts')
    @if ($errors->any())
        <script>
            // Réouverture automatique du modal en cas d'erreur de validation
            var depenseModal = new bootstrap.Modal(document.getElementById('depenseModal'));
            depenseModal.show();
        </script>
    @endif
@endsection
