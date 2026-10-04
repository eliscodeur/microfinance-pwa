@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Journal de Caisse</h1>
                <p class="text-muted small mb-0">Suivi en temps réel de tous les flux physiques de liquidités de la caisse.
                </p>
            </div>
            <div>
                <span class="badge bg-primary fs-6 px-3 py-2">
                    <i class="fas fa-calendar-alt me-1"></i> {{ $periodeLibelle }}
                </span>
            </div>
        </div>

        <!-- 1. CARTES KPI DE CAISSE -->
        <div class="row mb-4">
            <!-- Total Entrées -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Entrées
                                    (Encaissements)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($totalEntrees, 0, ',', ' ') }} FCFA</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-arrow-circle-down fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Sorties -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Sorties
                                    (Décaissements)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($totalSorties, 0, ',', ' ') }} FCFA</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-arrow-circle-up fa-2x text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Solde Théorique -->
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Solde Net de la
                                    Période</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($soldeTheorique, 0, ',', ' ') }} FCFA</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-wallet fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. BARRE DE FILTRES -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtres et Options d'Affichage</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.caisse.journal') }}" class="row g-3 align-items-end">
                    <!-- Filtre Période -->
                    <div class="col-md-4">
                        <label for="periode" class="form-label small font-weight-bold">Période</label>
                        <input type="text" class="form-control" name="periode" id="periode"
                            value="{{ request('periode') }}">
                    </div>

                    <!-- Filtre Type d'Opération -->
                    <div class="col-md-4">
                        <label for="type_operation" class="form-label small font-weight-bold">Type d'Opération</label>
                        <select name="type_operation" id="type_operation" class="form-control">
                            <option value="">-- Toutes les opérations --</option>
                            <option value="depot compte"
                                {{ request('type_operation') == 'depot compte' ? 'selected' : '' }}>Dépôt d'épargne
                            </option>
                            <option value="retrait compte"
                                {{ request('type_operation') == 'retrait compte' ? 'selected' : '' }}>Retrait d'épargne
                            </option>
                            <option value="depot tontine"
                                {{ request('type_operation') == 'depot tontine' ? 'selected' : '' }}>Dépôt de tontine
                            </option>
                            <option value="retrait tontine"
                                {{ request('type_operation') == 'retrait tontine' ? 'selected' : '' }}>Retrait de tontine
                            </option>
                            <option value="remboursement_credit"
                                {{ request('type_operation') == 'remboursement_credit' ? 'selected' : '' }}>Remboursement
                                de
                                crédit</option>
                            <option value="decaissement_credit"
                                {{ request('type_operation') == 'decaissement_credit' ? 'selected' : '' }}>Décaissement de
                                crédit</option>
                            <option value="recette" {{ request('type_operation') == 'recette' ? 'selected' : '' }}>Recette
                                d'exploitation</option>
                            <option value="depense" {{ request('type_operation') == 'depense' ? 'selected' : '' }}>Dépense
                            </option>
                        </select>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm flex-grow-1">
                            <i class="bi bi-filter me-1"></i> Filtrer
                        </button>
                        <a href="{{ route('admin.caisse.journal') }}" class="btn btn-outline-secondary py-2 px-3"
                            title="Réinitialiser">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. TABLEAU CHRONOLOGIQUE DES MOUVEMENTS -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Registre des Mouvements</h6>
                <span class="small text-muted">Total affiché : {{ $mouvements->total() }} opérations</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Heure</th>
                                <th>Type d'Opération</th>
                                <th>Libellé / Référence</th>
                                <th class="text-center">Mode</th>
                                <th class="text-end text-success">Entrée (FCFA)</th>
                                <th class="text-end text-danger">Sortie (FCFA)</th>
                                <th class="text-center">Caissier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mouvements as $mouvement)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @php
                                            $badgeClass = 'bg-secondary';
                                            if (
                                                in_array($mouvement->type_operation, [
                                                    'depot_epargne',
                                                    'remboursement_credit',
                                                    'recette',
                                                ])
                                            ) {
                                                $badgeClass = 'bg-success';
                                            } elseif (
                                                in_array($mouvement->type_operation, [
                                                    'retrait_epargne',
                                                    'decaissement_credit',
                                                    'depense',
                                                ])
                                            ) {
                                                $badgeClass = 'bg-danger';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $mouvement->type_operation)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $mouvement->reference ?? 'N/A' }}</strong>
                                        <div class="small text-muted">{{ $mouvement->libelle }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-light text-dark border">{{ ucfirst($mouvement->mode_paiement) }}</span>
                                    </td>
                                    <td class="text-end font-weight-bold text-success">
                                        @if ($mouvement->sens === 'entree')
                                            + {{ number_format($mouvement->montant, 0, ',', ' ') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end font-weight-bold text-danger">
                                        @if ($mouvement->sens === 'sortie')
                                            - {{ number_format($mouvement->montant, 0, ',', ' ') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        {{ $mouvement->user->name ?? 'Système' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                                        <p class="mb-0">Aucun mouvement de caisse enregistré pour cette période.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-end mt-3">
                    {{ $mouvements->links() }}
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/flatpickr-fr.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#periode", {
                mode: "range",
                dateFormat: "d/m/Y", // Format de la valeur envoyée au serveur (garde un format standard, ou mets "d F Y" si ton contrôleur gère les deux)
                altInput: true, // Active un input alternatif pour l'affichage visuel
                altFormat: "d F Y", // Affiche les mois en lettres dans le champ (ex: 01 octobre 2026)
                locale: "fr", // Traduction française (nécessaire pour avoir les mois en français)
                conjunction: " au " // Conjonction entre les deux dates de la plage
            });
        });
    </script>
@endsection
