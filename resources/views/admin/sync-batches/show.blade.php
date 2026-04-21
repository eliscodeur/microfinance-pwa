@extends('admin.layouts.sidebar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Batch de synchro</h2>
        <div class="text-muted small">{{ $syncBatch->sync_uuid }}</div>
    </div>
    <a href="{{ route('admin.sync-batches.index') }}" class="btn btn-outline-secondary btn-sm">Retour liste</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Agent</div><div class="fw-bold">{{ $syncBatch->agent->nom ?? '--' }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Clients</div><div class="fw-bold">{{ $resume['clients'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Pointages</div><div class="fw-bold">{{ $resume['total_pointages'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Montant total</div><div class="fw-bold">{{ number_format((float) $resume['total_montant'], 0, ',', ' ') }} FCFA</div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Etat du batch</h5>
            <span class="badge bg-{{ $syncBatch->status === 'pending_review' ? 'warning text-dark' : ($syncBatch->status === 'approved' ? 'success' : ($syncBatch->status === 'rejected' ? 'danger' : 'secondary')) }}">
                {{ $syncBatch->status }}
            </span>
        </div>

        @if($syncBatch->review_note)
            <div class="alert alert-light border small mb-3">{{ $syncBatch->review_note }}</div>
        @endif

        @if($syncBatch->status === 'pending_review')
            <div class="row g-3">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('admin.sync-batches.approve', $syncBatch) }}" class="card border-success">
                        @csrf
                        <div class="card-body">
                            <h6 class="text-success">Valider la synchro</h6>
                            <textarea name="review_note" class="form-control mb-3" rows="3" placeholder="Note optionnelle de validation"></textarea>
                            <button type="submit" class="btn btn-success">Valider et integrer</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="POST" action="{{ route('admin.sync-batches.reject', $syncBatch) }}" class="card border-danger">
                        @csrf
                        <div class="card-body">
                            <h6 class="text-danger">Refuser la synchro</h6>
                            <textarea name="review_note" class="form-control mb-3" rows="3" placeholder="Motif du refus"></textarea>
                            <button type="submit" class="btn btn-danger">Refuser</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Cycles proposes</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Carnet</th>
                            <th>Mise</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($syncBatch->cycles as $cycle)
                            <tr>
                                <td>{{ $cycle->client->nom ?? '--' }}</td>
                                <td>{{ $cycle->carnet->numero ?? '--' }}</td>
                                <td>{{ number_format($cycle->montant_journalier, 0, ',', ' ') }}</td>
                                <td>{{ $cycle->statut }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucun cycle dans ce batch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Collectes proposees</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Pointage</th>
                            <th>Montant</th>
                            <th>Date saisie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($syncBatch->collectes as $collecte)
                            <tr>
                                <td>{{ $collecte->client->nom ?? '--' }}</td>
                                <td>{{ $collecte->pointage }}</td>
                                <td>{{ number_format((float) $collecte->montant, 0, ',', ' ') }} FCFA</td>
                                <td>{{ $collecte->date_saisie?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucune collecte dans ce batch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
