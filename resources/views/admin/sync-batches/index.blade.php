@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Validation des Synchros</h2>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Retour dashboard</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">En attente</div><div class="fs-4 fw-bold text-warning" id="pending-review-count">{{ $totals['pending_review'] }}</div></div></div>
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
            <tbody id="sync-batches-table-body">
                <tr id="empty-row" style="display: {{ $batches->isEmpty() ? 'table-row' : 'none' }};">
                    <td colspan="8" class="text-center text-muted py-5">Aucun lot de synchronisation pour ce filtre.</td>
                </tr>
                @include('admin.partials.sync-table-rows', ['batches' => $batches])
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $batches->links('pagination::bootstrap-5') }}
</div>
<script>
    // On initialise avec l'heure actuelle côté serveur
    let lastRefreshTime = "{{ now()->toDateTimeString() }}";
    
    function refreshTable() {
        fetch(`/api/sync-batches/partial?since=${encodeURIComponent(lastRefreshTime)}`)
            .then(response => response.json()) // On attend du JSON maintenant
            .then(data => {
                if (data.html && data.html.trim() !== "") {
                    const tbody = document.getElementById('sync-batches-table-body');
                    tbody.insertAdjacentHTML('afterbegin', data.html);
                    const rows = Array.from(tbody.getElementsByTagName('tr'));
                    const actualRowCount = rows.filter(row => row.id !== 'empty-row' && row.style.display !== 'none');
                    document.getElementById('pending-review-count').textContent = actualRowCount.length;
                    const emptyRow = document.getElementById('empty-row');
                    if (emptyRow) {
                        emptyRow.style.display = 'none';
                    }
                    // On met à jour avec l'heure exacte du serveur !
                    lastRefreshTime = data.serverTime; 
                }
            })
            .catch(err => console.error("Erreur :", err));
    }

    setInterval(refreshTable, 3000);
</script>
@endsection

