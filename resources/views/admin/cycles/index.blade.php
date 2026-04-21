@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Gestion des Cycles</h2>
            <p class="text-muted mb-0">Tous les cycles terrain, y compris ceux en attente de retrait.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="{{ route('admin.cycles.index', ['filter' => 'all']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ $filter === 'all' ? 'border-primary' : '' }}">
                    <div class="card-body">
                        <small class="text-muted d-block">Tous</small>
                        <div class="h4 mb-0">{{ $totals['all'] }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.cycles.index', ['filter' => 'active']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ $filter === 'active' ? 'border-primary' : '' }}">
                    <div class="card-body">
                        <small class="text-muted d-block">En cours</small>
                        <div class="h4 mb-0">{{ $totals['active'] }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.cycles.index', ['filter' => 'awaiting_withdrawal']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ $filter === 'awaiting_withdrawal' ? 'border-warning' : '' }}">
                    <div class="card-body">
                        <small class="text-muted d-block">Retrait en attente</small>
                        <div class="h4 mb-0">{{ $totals['awaiting_withdrawal'] }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.cycles.index', ['filter' => 'withdrawn']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ $filter === 'withdrawn' ? 'border-success' : '' }}">
                    <div class="card-body">
                        <small class="text-muted d-block">Retires</small>
                        <div class="h4 mb-0">{{ $totals['withdrawn'] }}</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cycle</th>
                        <th>Client</th>
                        <th>Agent</th>
                        <th>Carnet</th>
                        <th>Mise</th>
                        <th>Total collecte</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Retrait</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cycles as $cycle)
                        <tr>
                            <td>
                                <strong>#{{ $cycle->id }}</strong><br>
                                <small class="text-muted">{{ $cycle->cycle_uid ?? '---' }}</small>
                            </td>
                            <td>
                                {{ $cycle->carnet->client->nom ?? '---' }}<br>
                                <small class="text-muted">{{ $cycle->carnet->client->telephone ?? '---' }}</small>
                            </td>
                            <td>{{ $cycle->agent->nom ?? '---' }}</td>
                            <td>{{ $cycle->carnet->numero ?? '---' }}</td>
                            <td>{{ number_format((float) $cycle->montant_journalier, 0, ',', ' ') }} F</td>
                            <td>{{ number_format((float) ($cycle->collectes_sum_montant ?? 0), 0, ',', ' ') }} F</td>
                            <td>
                                @if($cycle->completed_at)
                                    <small class="text-muted">{{ $cycle->completed_at->format('d/m/Y H:i') }}</small>
                                @elseif($cycle->statut === 'termine')
                                    <small class="text-muted">A renseigner</small>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                            <td>
                                @if($cycle->statut === 'en_cours')
                                    <span class="badge bg-primary">En cours</span>
                                @else
                                    <span class="badge bg-secondary">Termine</span>
                                @endif
                            </td>
                            <td>
                                @if($cycle->retire_at)
                                    <span class="badge bg-success">Retire</span><br>
                                    <small class="text-muted">{{ $cycle->retire_at->format('d/m/Y H:i') }}</small><br>
                                    <small class="text-muted">Par {{ $cycle->retrait->admin->name ?? '---' }}</small>
                                @elseif($cycle->statut === 'termine')
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($cycle->statut === 'termine' && !$cycle->retire_at)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success"
                                        onclick="ouvrirModalRetrait(
                                            '{{ route('admin.cycles.mark-withdrawn', $cycle) }}',
                                            '{{ addslashes($cycle->carnet->client->nom ?? 'Client') }}',
                                            '{{ $cycle->id }}',
                                            '{{ number_format((float)($cycle->collectes_sum_montant ?? 0), 0, ',', ' ') }} F',
                                            '{{ number_format(max(0, (float)($cycle->collectes_sum_montant ?? 0) - (float)$cycle->montant_journalier), 0, ',', ' ') }} F'
                                        )">
                                        Retire
                                    </button>
                                @elseif($cycle->statut === 'en_cours')
                                    <span class="text-muted small">Non terminé</span>
                                @else
                                    <span class="text-muted small">Déjà retiré</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">Aucun cycle trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $cycles->links() }}
    </div>
</div>

<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Confirmer le retrait</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="mb-2"><strong>Client :</strong> <span id="withdrawClient">---</span></p>
                    <p class="mb-2"><strong>Cycle :</strong> #<span id="withdrawCycle">---</span></p>
                    <p class="mb-2"><strong>Total collecte :</strong> <span id="withdrawTotal">---</span></p>
                    <p class="mb-3"><strong>Montant net :</strong> <span id="withdrawNet">---</span></p>
                    <div class="mb-3">
                        <label class="form-label">Date de retrait</label>
                        <input type="datetime-local" name="retire_at" id="withdrawDate" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Observation facultative"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ouvrirModalRetrait(action, client, cycleId, total, net) {
    
    console.log("Ouvrir modal retrait avec:", { action, client, cycleId, total, net });
    document.getElementById('withdrawForm').action = action;
    document.getElementById('withdrawClient').innerText = client;
    document.getElementById('withdrawCycle').innerText = cycleId;
    document.getElementById('withdrawTotal').innerText = total;
    document.getElementById('withdrawNet').innerText = net;

    const dateField = document.getElementById('withdrawDate');
    dateField.value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);

    new bootstrap.Modal(document.getElementById('withdrawModal')).show();
}
</script>
@endsection
