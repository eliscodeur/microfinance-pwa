@extends('admin.layouts.sidebar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Validation des Synchros</h2>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Retour dashboard</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">En attente</div><div class="fs-4 fw-bold text-warning">{{ $totals['pending_review'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Validées</div><div class="fs-4 fw-bold text-success">{{ $totals['approved'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Refusées</div><div class="fs-4 fw-bold text-danger">{{ $totals['rejected'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Annulées</div><div class="fs-4 fw-bold text-secondary">{{ $totals['cancelled'] }}</div></div></div>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="{{ route('admin.sync-batches.index', ['status' => 'pending_review']) }}" class="btn btn-sm {{ $status === 'pending_review' ? 'btn-warning' : 'btn-outline-warning' }}">En attente</a>
    <a href="{{ route('admin.sync-batches.index', ['status' => 'approved']) }}" class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-outline-success' }}">Validées</a>
    <a href="{{ route('admin.sync-batches.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ $status === 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">Refusées</a>
    <a href="{{ route('admin.sync-batches.index', ['status' => 'cancelled']) }}" class="btn btn-sm {{ $status === 'cancelled' ? 'btn-secondary' : 'btn-outline-secondary' }}">Annulées</a>
    <a href="{{ route('admin.sync-batches.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">Toutes</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Agent</th>
                    <!-- <th>Batch</th> -->
                    <th>Cycles</th>
                    <th>Collectes</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $batch->agent->nom ?? 'Agent' }}</div>
                            <div class="small text-muted">{{ $batch->agent->code_agent ?? '--' }}</div>
                        </td>
                        <!-- <td class="small">{{ $batch->sync_uuid }}</td> -->
                        <td>{{ $batch->nb_cycles }}</td>
                        <td>{{ $batch->nb_collectes }}</td>
                        <td>{{ number_format((float) $batch->total_montant, 0, ',', ' ') }} FCFA</td>
                        <td>
                           @php
                                $title = "en attente"; // Valeur par défaut
                                if($batch->status === "approved") {
                                    $title = "Approuvée";
                                } elseif($batch->status === "rejected") {
                                    $title = "rejetée";
                                } elseif($batch->status ==="cancelled") {
                                    $title = "Annulée";
                                }
                            @endphp
                            <span class="badge bg-{{ $batch->status === 'pending_review' ? 'warning text-dark' : ($batch->status === 'approved' ? 'success' : ($batch->status === 'rejected' ? 'danger' : 'secondary')) }}">
                                {{ $title }}
                            </span>
                        </td>
                        <td class="small">{{ $batch->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.sync-batches.show', $batch) }}" class="btn btn-sm btn-primary">Verifier</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">Aucun lot de synchronisation pour ce filtre.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $batches->links('pagination::bootstrap-5') }}
</div>
@endsection
