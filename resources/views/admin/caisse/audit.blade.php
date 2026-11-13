@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-shield-check me-2"></i>Audit Global des Caisses</h1>
                <p class="text-muted small mb-0">Supervision et consultation des mouvements financiers de tous les agents.</p>
            </div>
            <div>
                <span class="badge bg-primary fs-6 px-3 py-2">
                    <i class="fas fa-calendar-alt me-1"></i> {{ $periodeLibelle }}
                </span>
            </div>
        </div>

        <!-- BARRE DE FILTRES (Avec filtre agent obligatoire ici) -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtres d'Audit</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.caisse.audit') }}" class="row g-3">

                    <!-- Filtre Agent -->
                    <div class="col-md-3">
                        <label for="user_id" class="form-label small font-weight-bold">Administrateur</label>
                        <select name="user_id" id="user_id" class="form-control">
                            <option value="">-- Tous les administrateurs --</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

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

                    <!-- Filtre Sens -->
                    <div class="col-md-3">
                        <label for="sens" class="form-label small font-weight-bold">Sens</label>
                        <select name="sens" id="sens" class="form-control">
                            <option value="">Entrées et sorties</option>
                            <option value="entree" {{ request('sens') === 'entree' ? 'selected' : '' }}>Entrées</option>
                            <option value="sortie" {{ request('sens') === 'sortie' ? 'selected' : '' }}>Sorties</option>
                        </select>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('admin.caisse.audit') }}" class="btn btn-sm btn-outline-secondary py-2 px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 shadow-sm">
                            <i class="bi bi-filter me-1"></i> Appliquer les filtres
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Résultats de l'Audit</h6>
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
                                        <span class="badge {{ $mouvement->sens === 'entree' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst(str_replace('_', ' ', $mouvement->type_operation)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $mouvement->reference ?? 'N/A' }}</strong>
                                        <div class="small text-muted">{{ $mouvement->libelle }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ ucfirst($mouvement->mode_paiement) }}</span>
                                    </td>
                                    <td class="text-end font-weight-bold text-success">
                                        {{ $mouvement->sens === 'entree' ? '+ ' . number_format($mouvement->montant, 0, ',', ' ') : '-' }}
                                    </td>
                                    <td class="text-end font-weight-bold text-danger">
                                        {{ $mouvement->sens === 'sortie' ? '- ' . number_format($mouvement->montant, 0, ',', ' ') : '-' }}
                                    </td>
                                    <td class="text-center small font-weight-bold text-primary">
                                        {{ $mouvement->user->name ?? 'Système' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                                        <p class="mb-0">Aucun mouvement trouvé pour ces critères d'audit.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
@endsection
