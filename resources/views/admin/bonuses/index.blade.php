@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-cash-stack text-success me-2"></i>
            Gestion des Bonus et Commissions
        </h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBonusModal">
            <i class="bi bi-plus-circle me-2"></i>Attribuer un Bonus
        </button>
        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Retour aux Agents
        </a>
    </div>

    {{-- Filtres --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-funnel me-2 text-primary"></i>Filtres de recherche</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.bonuses.index') }}" class="row g-3">
                <!-- Agent : Plus large sur tablette, plein écran sur mobile -->
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="agent_id" class="form-label small fw-bold">Agent</label>
                    <select name="agent_id" id="agent_id" class="form-select">
                        <option value="">Tous les agents</option>
                        @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>
                            {{ $agent->nom }} ({{ $agent->code_agent }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dates : Groupées par deux sur tablette -->
                <div class="col-6 col-lg-2">
                    <label for="date_debut" class="form-label small fw-bold">Date début</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ request('date_debut') }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="date_fin" class="form-label small fw-bold">Date fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ request('date_fin') }}">
                </div>

                <!-- Motif -->
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="motif" class="form-label small fw-bold">Motif</label>
                    <input type="text" name="motif" id="motif" class="form-control" placeholder="Rechercher..." value="{{ request('motif') }}">
                </div>

                <!-- Boutons d'action : Centrés sur mobile, alignés à droite sur desktop -->
                <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('admin.bonuses.index') }}" class="btn btn-outline-secondary w-100" title="Réinitialiser">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h4 class="text-success">{{ number_format($bonuses->sum('montant'), 0, ',', ' ') }} F</h4>
                    <p class="text-muted mb-0">Total des bonus affichés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h4 class="text-primary">{{ $bonuses->count() }}</h4>
                    <p class="text-muted mb-0">Nombre de bonus</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h4 class="text-info">{{ $bonuses->where('motif', 'like', '%Commission%')->count() }}</h4>
                    <p class="text-muted mb-0">Commissions automatiques</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h4 class="text-warning">{{ $bonuses->where('motif', 'not like', '%Commission%')->count() }}</h4>
                    <p class="text-muted mb-0">Bonus manuels</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Table des bonus --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des Bonus/Commissions</h5>
            <span class="badge bg-secondary">{{ $bonuses->total() }} résultat(s)</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Agent</th>
                            <th>Montant</th>
                            <th>Motif</th>
                            <th>Attribué par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bonuses as $bonus)
                        <tr>
                            <td>{{ $bonus->date_attribution->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $bonus->agent->nom }}</div>
                                <small class="text-muted">{{ $bonus->agent->code_agent }}</small>
                            </td>
                            <td>
                                <span class="badge bg-success fs-6">{{ number_format($bonus->montant, 0, ',', ' ') }} F</span>
                            </td>
                            <td>
                                <span class="badge {{ str_contains($bonus->motif, 'Commission') ? 'bg-primary' : 'bg-info' }}">
                                    {{ $bonus->motif }}
                                </span>
                            </td>
                            <td>{{ $bonus->admin->name ?? 'Système' }}</td>
                            <td>
                                <a href="{{ route('admin.agents.show', $bonus->agent_id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-person"></i> Voir Agent
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-cash-stack text-muted fs-1"></i>
                                <p class="text-muted mt-2">Aucun bonus trouvé avec ces critères.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($bonuses->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $bonuses->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
<!-- Modal Ajouter Bonus -->
<div class="modal fade" id="addBonusModal" tabindex="-1" aria-labelledby="addBonusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBonusModalLabel">Attribuer un Bonus Manuel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.bonuses.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Sélection de l'Agent -->
                    <div class="mb-3">
                        <label for="modal_agent_id" class="form-label fw-bold">Agent bénéficiaire</label>
                        <select name="agent_id" id="modal_agent_id" class="form-select" required>
                            <option value="">Choisir un agent...</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->nom }} ({{ $agent->code_agent }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Montant -->
                    <div class="mb-3">
                        <label for="montant" class="form-label fw-bold">Montant du bonus (FCFA)</label>
                        <input type="number" name="montant" id="montant" class="form-control" placeholder="Ex: 5000" required min="1">
                    </div>

                    <!-- Motif -->
                    <div class="mb-3">
                        <label for="motif_select" class="form-label fw-bold">Motif du bonus</label>
                        <select name="motif" id="motif_select" class="form-select" required>
                            <option value="Prime d'excellence">Prime d'excellence</option>
                            <option value="Challenge atteint">Challenge atteint</option>
                            <option value="Régularisation">Régularisation</option>
                            <option value="Autre">Autre (préciser en note)</option>
                        </select>
                    </div>

                    <!-- Date d'attribution -->
                    <div class="mb-3">
                        <label for="date_attribution" class="form-label fw-bold">Date d'effet</label>
                        <input type="date" name="date_attribution" id="date_attribution" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Confirmer l'attribution</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection