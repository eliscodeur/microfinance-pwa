@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Journal de Caisse</h1>
                <p class="text-muted small mb-0">Registre des mouvements financiers enregistrés, avec leurs modes de paiement et sessions de caisse.
                </p>
            </div>
            <div>
                <span class="badge bg-primary fs-6 px-3 py-2">
                    <i class="fas fa-calendar-alt me-1"></i> {{ $periodeLibelle }}
                </span>
            </div>
        </div>

        @if ($session && $sessionSummary)
            <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <strong>Votre session de caisse est ouverte</strong>
                    <div class="small">Ouverte le {{ $session->date_ouverture->format('d/m/Y à H:i') }} · Fonds initial : {{ number_format($sessionSummary['fond_initial'], 0, ',', ' ') }} FCFA</div>
                </div>
                <div class="text-md-end">
                    <span class="small d-block">Solde espèces théorique de la session</span>
                    <strong class="fs-5 {{ $sessionSummary['solde_theorique'] < 0 ? 'text-danger' : '' }}">{{ number_format($sessionSummary['solde_theorique'], 0, ',', ' ') }} FCFA</strong>
                </div>
                <a href="{{ route('admin.caisse.approvisionner') }}" class="btn btn-success">Approvisionner</a>
                <a href="{{ route('admin.caisse.cloture') }}" class="btn btn-outline-primary">Clôturer la caisse</a>
            </div>
            @if ($sessionSummary['solde_theorique'] < 0)
                <div class="alert alert-danger">
                    <strong>Décaissements espèces suspendus :</strong> le solde de caisse est négatif. Vérifiez le journal et enregistrez un approvisionnement réel avant toute nouvelle sortie.
                </div>
            @elseif ($sessionSummary['solde_theorique'] == 0)
                <div class="alert alert-warning">
                    Le solde espèces est épuisé. Enregistrez un approvisionnement avant tout décaissement en espèces.
                </div>
            @endif
        @else
            <div class="alert alert-warning mb-4">
                Aucune session de caisse n’est ouverte pour votre compte. Le journal reste consultable, mais le solde net affiché n’est pas le solde physique d’une caisse.
                <a href="{{ route('admin.caisse.ouvrir') }}" class="alert-link">Ouvrir une session</a>
            </div>
        @endif

        <!-- 1. CARTES KPI DE CAISSE -->
        <div class="row mb-4">
            <!-- Total Entrées -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total entrées (tous modes)</div>
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
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total sorties (tous modes)</div>
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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Solde net des flux filtrés</div>
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
                    <div class="col-md-3">
                        <label for="periode" class="form-label small font-weight-bold">Période</label>
                        <input type="text" class="form-control" name="periode" id="periode"
                            value="{{ request('periode') }}" placeholder="JJ/MM/AAAA au JJ/MM/AAAA">
                    </div>

                    <!-- Filtre Type d'Opération -->
                    <div class="col-md-3">
                        <label for="type_operation" class="form-label small font-weight-bold">Type d'Opération</label>
                        <select name="type_operation" id="type_operation" class="form-control">
                            <option value="">-- Toutes les opérations --</option>
                            @foreach ($operations as $operation)
                                <option value="{{ $operation }}" {{ request('type_operation') === $operation ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $operation)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="sens" class="form-label small font-weight-bold">Sens</label>
                        <select name="sens" id="sens" class="form-control">
                            <option value="">Entrées et sorties</option>
                            <option value="entree" {{ request('sens') === 'entree' ? 'selected' : '' }}>Entrées</option>
                            <option value="sortie" {{ request('sens') === 'sortie' ? 'selected' : '' }}>Sorties</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="mode_paiement" class="form-label small font-weight-bold">Mode de paiement</label>
                        <select name="mode_paiement" id="mode_paiement" class="form-control">
                            <option value="">Tous les modes</option>
                            @foreach ($modesPaiement as $mode)
                                <option value="{{ $mode }}" {{ request('mode_paiement') === $mode ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $mode)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
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
                                <th class="text-center">Session</th>
                                <th class="text-center">Caissier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mouvements as $mouvement)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($mouvement->date_mouvement)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @php
                                            $badgeClass = $mouvement->sens === 'entree' ? 'bg-success' : 'bg-danger';
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
                                        {{ $mouvement->cashSession ? 'Session #' . $mouvement->cashSession->id : 'Non rattaché' }}
                                    </td>
                                    <td class="text-center small">
                                        {{ $mouvement->user->name ?? 'Système' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
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

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dix dernières clôtures</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date de clôture</th>
                                <th>Caissier</th>
                                <th class="text-end">Solde théorique</th>
                                <th class="text-end">Solde compté</th>
                                <th class="text-end">Écart</th>
                                <th>Justification</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clotures as $cloture)
                                <tr>
                                    <td>{{ $cloture->date_cloture->format('d/m/Y H:i') }}</td>
                                    <td>{{ $cloture->user->name ?? 'Utilisateur supprimé' }}</td>
                                    <td class="text-end">{{ number_format($cloture->solde_theorique, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end">{{ number_format($cloture->solde_physique, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end {{ $cloture->ecart < 0 ? 'text-danger' : ($cloture->ecart > 0 ? 'text-success' : '') }}">
                                        {{ $cloture->ecart > 0 ? '+' : '' }}{{ number_format($cloture->ecart, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td>{{ $cloture->commentaire ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Aucune clôture enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
