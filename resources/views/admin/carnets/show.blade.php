@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Carnet {{ $carnet->numero }}</h2>
            <p class="text-muted mb-0">Detail du carnet, client associe et cycles.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.show', $carnet->client_id) }}" class="btn btn-outline-info">Voir client</a>
            <a href="{{ route('admin.carnets.index') }}" class="btn btn-outline-secondary">Retour</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Informations carnet</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Numero</small>
                    <div class="fw-bold">{{ $carnet->numero }}</div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Reference physique</small>
                    <div class="fw-bold">{{ $carnet->reference_physique ?: '---' }}</div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Statut</small>
                    <div class="fw-bold">{{ ucfirst($carnet->statut ?? '---') }}</div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Date debut</small>
                    <div class="fw-bold">{{ optional($carnet->date_debut)->format('d/m/Y') ?? '---' }}</div>
                </div>
                <div class="col-md-8">
                    <small class="text-muted d-block">Client</small>
                    <div class="fw-bold">{{ $carnet->client->nom ?? '---' }}</div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Nombre de cycles</small>
                    <div class="fw-bold">{{ $carnet->cycles->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Cycles du carnet</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Cycle</th>
                                    <th>Agent</th>
                                    <th>Mise</th>
                                    <th>Total cycle</th>
                                    <th>Statut cycle</th>
                                    <th>Retrait</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($carnet->cycles as $cycle)
                                    @php
                                        $totalCycle = $cycle->collectes->sum('montant');
                                    @endphp
                                    <tr>
                                        <td>#{{ $cycle->id }}</td>
                                        <td>{{ $cycle->agent->nom ?? '---' }}</td>
                                        <td>{{ number_format((float) $cycle->montant_journalier, 0, ',', ' ') }} F</td>
                                        <td>
                                            {{ number_format((float) $totalCycle, 0, ',', ' ') }} F<br>
                                            <small class="text-muted">{{ $cycle->collectes->count() }} collecte(s)</small>
                                        </td>
                                        <td>
                                            @if($cycle->statut === 'en_cours')
                                                <span class="badge bg-primary">En cours</span>
                                            @else
                                                <span class="badge bg-secondary">Termine</span><br>
                                                <small class="text-muted">{{ optional($cycle->completed_at)->format('d/m/Y H:i') ?? 'Fin non renseignee' }}</small>
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
                                                    onclick="ouvrirModalRetraitCarnet(
                                                        '{{ route('admin.cycles.mark-withdrawn', $cycle) }}',
                                                        '{{ addslashes($carnet->client->nom ?? 'Client') }}',
                                                        '{{ $cycle->id }}',
                                                        '{{ number_format((float) $totalCycle, 0, ',', ' ') }} F',
                                                        '{{ number_format(max(0, (float) $totalCycle - (float) $cycle->montant_journalier), 0, ',', ' ') }} F'
                                                    )">
                                                    Retire
                                                </button>
                                            @elseif($cycle->statut === 'en_cours')
                                                <span class="text-muted small">Non termine</span>
                                            @else
                                                <span class="text-muted small">Deja retire</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Aucun cycle pour ce carnet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="withdrawCarnetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Confirmer le retrait</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawCarnetForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="mb-2"><strong>Client :</strong> <span id="withdrawCarnetClient">---</span></p>
                    <p class="mb-2"><strong>Cycle :</strong> #<span id="withdrawCarnetCycle">---</span></p>
                    <p class="mb-2"><strong>Total collecte :</strong> <span id="withdrawCarnetTotal">---</span></p>
                    <p class="mb-3"><strong>Montant net :</strong> <span id="withdrawCarnetNet">---</span></p>
                    <div class="mb-3">
                        <label class="form-label">Date de retrait</label>
                        <input type="datetime-local" name="retire_at" id="withdrawCarnetDate" class="form-control" required>
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
function ouvrirModalRetraitCarnet(action, client, cycleId, total, net) {
    document.getElementById('withdrawCarnetForm').action = action;
    document.getElementById('withdrawCarnetClient').innerText = client;
    document.getElementById('withdrawCarnetCycle').innerText = cycleId;
    document.getElementById('withdrawCarnetTotal').innerText = total;
    document.getElementById('withdrawCarnetNet').innerText = net;

    const dateField = document.getElementById('withdrawCarnetDate');
    dateField.value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);

    new bootstrap.Modal(document.getElementById('withdrawCarnetModal')).show();
}
</script>
@endsection
