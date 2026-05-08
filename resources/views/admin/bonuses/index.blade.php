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
    </div>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success shadow-sm">
                <div class="card-body text-center">
                    <h4 class="text-success fw-bold">{{ number_format($bonusesByAgent->sum('total_global'), 0, ',', ' ') }} F</h4>
                    <p class="text-muted mb-0 small">TOTAL GLOBAL À PAYER</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary shadow-sm">
                <div class="card-body text-center">
                    <h4 class="text-primary fw-bold">{{ $bonusesByAgent->sum('nb_items') }}</h4>
                    <p class="text-muted mb-0 small">TRANSACTIONS EN ATTENTE</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info shadow-sm">
                <div class="card-body text-center">
                    <h4 class="text-info fw-bold">{{ $bonusesByAgent->count() }}</h4>
                    <p class="text-muted mb-0 small">AGENTS À RÉGLER</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Table des bonus --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold">Liste des Bonus / Commissions</h5>
            <span class="badge bg-dark">{{ $bonusesByAgent->count() }} Agent(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Agent</th>
                            <th class="text-center">Commissions (Auto)</th>
                            <th class="text-center">Bonus (Manuels)</th>
                            <th class="text-center">Total à Valider</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bonusesByAgent as $data)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-capitalize">{{ $data->agent->nom }}</div>
                                <small class="text-muted">{{ $data->agent->code_agent }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info border border-info px-3">
                                    {{ number_format($data->total_commissions, 0, ',', ' ') }} F
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary px-3">
                                    {{ number_format($data->total_manuels, 0, ',', ' ') }} F
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-success fs-5">{{ number_format($data->total_global, 0, ',', ' ') }} F</div>
                                <small class="text-muted">{{ $data->nb_items }} élément(s)</small>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#modalDetails{{ $data->agent_id }}">
                                        <i class="bi bi-eye"></i> Détails
                                    </button>
                                    
                                    <form action="{{ route('admin.bonuses.bulk-approve') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="agent_id" value="{{ $data->agent_id }}">
                                        <button type="submit" class="btn btn-sm btn-success px-3" onclick="return confirm('Valider et payer tout pour cet agent ?')">
                                            <i class="bi bi-check-all"></i> Tout Payer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle display-4 text-success d-block mb-2"></i>
                                Aucun paiement en attente.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODALS : On les place ICI, hors du tableau, pour éviter les bugs d'affichage --}}
@foreach($bonusesByAgent as $data)
    @include('admin.bonuses.partials.modal-details', ['data' => $data])
@endforeach

{{-- Modal Ajout Manuel --}}
<div class="modal fade" id="addBonusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Attribuer un Bonus Manuel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.bonuses.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Agent</label>
                        <select name="agent_id" class="form-select" required>
                            <option value="">Sélectionner l'agent...</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->nom }} ({{ $agent->code_agent }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant (FCFA)</label>
                        <input type="number" name="montant" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motif</label>
                        <input type="text" name="motif" class="form-control" placeholder="Ex: Prime d'excellence" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Date d'effet</label>
                        <input type="date" name="date_attribution" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection