@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">NANA CONSULTING</h2>
            <p class="text-muted">Tableau de bord de gestion de la collecte</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary-subtle text-primary p-2 px-3 rounded-pill">
                <i class="bi bi-calendar3 me-2"></i> {{ now()->format('d F Y') }}
            </span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary text-white rounded-3 p-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Agents terrain</h6>
                        <h3 class="mb-0 fw-bold">{{ $totalAgents }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success text-white rounded-3 p-3">
                        <i class="bi bi-person-check-fill fs-4"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Clients actifs</h6>
                        <h3 class="mb-0 fw-bold">{{ $totalClients }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 p-3 text-white bg-warning bg-gradient">
                <div class="d-flex align-items-center">
                    <div class="ms-2">
                        <h6 class="mb-1 text-uppercase small fw-bold text-dark">Collecte Totale</h6>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalCollecte, 0, ',', ' ') }} <small>FCFA</small></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 p-3 {{ $pendingSyncBatches > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase small fw-bold">Sync. en attente</h6>
                        <h3 class="mb-0 fw-bold {{ $pendingSyncBatches > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $pendingSyncBatches }}
                        </h3>
                    </div>
                    <a href="{{ route('admin.sync-batches.index') }}" class="btn btn-sm btn-light border-0 rounded-circle p-2">
                        <i class="bi bi-arrow-right-short fs-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Évolution de la collecte</h5>
                    <select class="form-select form-select-sm w-auto">
                        <option>7 derniers jours</option>
                        <option>Ce mois</option>
                    </select>
                </div>
                <div style="height: 300px;" class="d-flex align-items-center justify-content-center bg-light rounded border border-dashed">
                    <p class="text-muted">Graphique de performance (Chart.js)</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h5 class="fw-bold mb-4">Actions Rapides</h5>
                <div class="d-grid gap-3">
                    <a href="{{ route('admin.clients.create') }}" class="btn btn-outline-primary text-start p-3">
                        <i class="bi bi-person-plus me-2"></i> Nouveau Client
                    </a>
                    <a href="{{ route('admin.carnets.index') }}" class="btn btn-outline-dark text-start p-3">
                        <i class="bi bi-journal-bookmark-fill me-2"></i> Gérer les Carnets
                    </a>
                    <a href="#" class="btn btn-outline-secondary text-start p-3">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Rapports du jour
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card { transition: transform 0.2s; }
    .card:hover { transform: translateY(-5px); }
    .bg-info-subtle { background-color: #e0f7fa; }
</style>
@endsection