@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">

        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('admin.payrolls.employes.index', ['mois' => $mois, 'annee' => $annee]) }}"
                    class="btn btn-sm btn-outline-secondary mb-2">
                    <i class="fas fa-arrow-left me-1"></i> Retour au tableau de paie
                </a>
                <h2 class="fw-bold text-dark mb-1">Bulletin détaillé & traçabilité analytique</h2>
                <p class="text-muted mb-0">Période :
                    <strong>{{ ucfirst(\Carbon\Carbon::create()->setMonth($mois)->translatedFormat('F')) }}
                        {{ $annee }}</strong>
                </p>
            </div>
            <div>
                <span
                    class="badge bg-{{ strtolower($payrollDetails->statut) == 'brouillon' ? 'warning text-dark' : (strtolower($payrollDetails->statut) == 'validé' ? 'success' : 'secondary') }} px-3 py-2 fs-6">
                    Statut : {{ $payrollDetails->statut }}
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Ligne supérieure : Informations agent & Traçabilité -->
        <div class="row g-4 mb-4">
            <!-- Carte Informations Agent -->
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <span class="text-uppercase fw-bold text-muted small tracking-wide">Informations de l'agent</span>
                    </div>
                    <div class="card-body">
                        <h4 class="fw-bold text-dark mb-2">{{ $employe->nom }} {{ $employe->prenoms }}</h4>
                        <p class="text-muted mb-2"><i class="fas fa-briefcase me-2 text-primary"></i> <strong>Fonction
                                :</strong> {{ $employe->fonction->libelle ?? 'Non assignée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Carte Traçabilité & Sécurité Financière -->
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <span class="text-uppercase fw-bold text-muted small tracking-wide">Traçabilité & Sécurité
                            Financière</span>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Statut global :</span>
                                <span class="fw-bold text-dark">{{ $payrollDetails->statut }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Généré le :</span>
                                <span
                                    class="fw-bold text-dark">{{ $payrollDetails->salaire_id ? optional($payrollDetails)->created_at ?? now()->format('d/m/Y H:i') : 'En cours de calcul' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Validé par :</span>
                                <span class="fw-bold text-dark">En attente de validation</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-muted">Date de validation :</span>
                                <span class="fw-bold text-dark">---</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire et Décomposition Analytique -->
        <form action="{{ route('admin.payrolls.employe.store-brouillon', $employe->ulid) }}" method="POST"
            id="form-brouillon">
            @csrf
            <input type="hidden" name="mois" value="{{ $mois }}">
            <input type="hidden" name="annee" value="{{ $annee }}">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fs-6"><i class="fas fa-calculator me-2"></i> Décomposition analytique du salaire &
                        saisie des éléments</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7">
                                <tr>
                                    <th class="py-3 ps-4">Composante</th>
                                    <th class="py-3">Base / Source</th>
                                    <th class="py-3">Règle / Taux / Saisie</th>
                                    <th class="py-3 text-end pe-4">Montant (FCFA)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Salaire de base -->
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">Salaire de base</td>
                                    <td><span class="badge bg-light text-dark border">Grille salariale active (Employe /
                                            Fonction)</span></td>
                                    <td>Fixe contractuel</td>
                                    <td class="text-end pe-4 fw-bold">
                                        {{ number_format($payrollDetails->salaire_base, 0, ',', ' ') }}</td>
                                </tr>

                                <!-- Primes Totales (Modifiable) -->
                                <tr>
                                    <td class="ps-4 fw-bold text-primary">Primes totales</td>
                                    <td>Primes validées sur la période</td>
                                    <td style="width: 250px;">
                                        <input type="number" step="any" class="form-control form-control-sm"
                                            name="primes_totales"
                                            value="{{ old('primes_totales', $payrollDetails->primes_totales) }}">
                                    </td>
                                    <td class="text-end pe-4 text-primary fw-bold">+
                                        {{ number_format($payrollDetails->primes_totales, 0, ',', ' ') }}</td>
                                </tr>

                                <!-- Indemnités Totales (Modifiable) -->
                                <tr>
                                    <td class="ps-4 fw-bold text-info">Indemnités Totales</td>
                                    <td>Indemnités forfaitaires ou effectives</td>
                                    <td style="width: 250px;">
                                        <input type="number" step="any" class="form-control form-control-sm"
                                            name="indemnites_totales"
                                            value="{{ old('indemnites_totales', $payrollDetails->indemnites_totales) }}">
                                    </td>
                                    <td class="text-end pe-4 text-info fw-bold">+
                                        {{ number_format($payrollDetails->indemnites_totales, 0, ',', ' ') }}</td>
                                </tr>

                                <!-- Avances sur Salaire (Automatique / Déduites) -->
                                <tr>
                                    <td class="ps-4 fw-bold text-danger">Avances sur salaire & prêts</td>
                                    <td>Prélèvements validés sur la période (Tranches en cours)</td>
                                    <td>Déduction automatique</td>
                                    <td class="text-end pe-4 text-danger fw-bold">-
                                        {{ number_format($payrollDetails->avance_deduite, 0, ',', ' ') }}</td>
                                </tr>

                                <!-- Autres Retenues (Modifiable) -->
                                <tr>
                                    <td class="ps-4 fw-bold text-danger">Autres retenues</td>
                                    <td>Retenues diverses (Absences, sanctions, etc.)</td>
                                    <td style="width: 250px;">
                                        <input type="number" step="any" class="form-control form-control-sm"
                                            name="autres_retenues"
                                            value="{{ old('autres_retenues', $payrollDetails->autres_retenues) }}">
                                    </td>
                                    <td class="text-end pe-4 text-danger fw-bold">-
                                        {{ number_format($payrollDetails->autres_retenues, 0, ',', ' ') }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end py-3 fw-bold text-uppercase fs-6">Salaire Net à
                                        Verser :</td>
                                    <td class="text-end py-3 pe-4 text-success fw-bold fs-5">
                                        {{ number_format($payrollDetails->salaire_net, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 text-end">
                    <button type="button" id="btn-save-brouillon" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Enregistrer en brouillon & recalculer
                    </button>
                </div>
            </div>
        </form>

        <!-- Section Historique des avances sur salaire déduites -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-file-invoice-dollar me-2"></i> Historique des
                    avances sur salaire déduites pour ce mois</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7">
                            <tr>
                                <th class="py-3 ps-4">Date de demande / création</th>
                                <th class="py-3">Motif</th>
                                <th class="py-3">Montant Tranche / Déduit</th>
                                <th class="py-3">Statut</th>
                                <th class="py-3 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrollDetails->avances_concernes as $avance)
                                <tr>
                                    <td class="ps-4">
                                        {{ $avance->created_at ? $avance->created_at->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $avance->motif ?? 'Avance sur salaire' }}</td>
                                    <td class="text-danger fw-bold">-
                                        {{ number_format($avance->montant_mensuel ?? 0, 0, ',', ' ') }} FCFA</td>
                                    <td>
                                        <span
                                            class="badge bg-warning text-dark">{{ ucfirst($avance->statut ?? 'en_cours') }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#modalAvance-{{ $avance->id }}">
                                            <i class="fas fa-list me-1"></i> Gérer les tranches
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucune avance ou prêt actif
                                        déduit pour cette période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    @foreach ($avancesList ?? $payrollDetails->avances_concernes as $avance)
        <div class="modal fade" id="modalAvance-{{ $avance->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    {{-- Le formulaire englobe tout le contenu pour envoyer les choix au contrôleur --}}
                    <form action="{{ route('admin.payrolls.avances.employe.updateTranches', $avance->id) }}"
                        method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Paramètres cachés nécessaires au traitement --}}
                        <input type="hidden" name="mois" value="{{ $mois }}">
                        <input type="hidden" name="annee" value="{{ $annee }}">
                        <input type="hidden" name="employe_administratif_id"
                            value="{{ $payrollDetails->employe->id }}">


                        <div class="modal-header bg-light">
                            <h5 class="modal-title h6 fw-bold">
                                <i class="bi bi-wallet2 text-danger me-2"></i> Gestion des tranches -
                                {{ $avance->motif ?? 'Avance sur salaire' }}
                                <span
                                    class="text-muted fs-7">({{ number_format($avance->montant_total ?? ($avance->montant ?? 0), 0, ',', ' ') }}
                                    FCFA)</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <p class="text-muted small">Cochez ou décochez les tranches à déduire pour ce bulletin de paie.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light fs-7">
                                        <tr>
                                            <th style="width: 70px;" class="text-center">Sélection</th>
                                            <th>Tranche</th>
                                            <th>Mois concerné</th>
                                            <th>Montant</th>
                                            <th>État</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($avance->tranches_formatees ?? [] as $tranche)
                                            <tr class="{{ $tranche['selectionne'] ?? false ? 'table-active' : '' }}">
                                                {{-- COLONNE DES CHECKBOX --}}
                                                <td class="text-center">
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input type="checkbox" name="tranches_ids[]"
                                                            value="{{ $tranche['numero'] }}" class="form-check-input"
                                                            {{ $tranche['selectionne'] ?? false ? 'checked' : '' }}
                                                            {{ $tranche['deja_paye'] ?? false ? 'disabled' : '' }}>
                                                    </div>
                                                </td>
                                                <td class="fw-bold">
                                                    Tranche {{ $tranche['numero'] }} / {{ $avance->nombre_tranches ?? 1 }}
                                                </td>
                                                <td class="text-muted">
                                                    {{ ucfirst($tranche['mois'] ?? '') }}
                                                </td>
                                                <td class="font-monospace text-danger">
                                                    {{ number_format($tranche['montant'] ?? 0, 0, ',', ' ') }} FCFA
                                                </td>
                                                <td>
                                                    @if ($tranche['deja_paye'] ?? false)
                                                        <span class="badge bg-success">Déjà réglé</span>
                                                    @elseif ($tranche['selectionne'] ?? false)
                                                        <span class="badge bg-primary">Sélectionné ce mois</span>
                                                    @else
                                                        <span class="badge bg-secondary text-light">À venir</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger btn-sm">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.getElementById('btn-save-brouillon').addEventListener('click', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: "Voulez-vous enregistrer ce bulletin en mode brouillon et recalculer les montants ?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, enregistrer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('form-brouillon').submit();
                    }
                });
            });
        </script>
    @endpush
@endsection
