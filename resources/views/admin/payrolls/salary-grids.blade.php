@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="mb-4">
            <h1 class="h4 mb-1">Paramètres de la grille salariale des agents</h1>
            <p class="text-muted mb-0">Les valeurs de départ reprennent la grille transmise par le client. Les périodes et seuils sont vérifiés avant enregistrement.</p>
        </div>

        @foreach (['success' => 'success', 'error' => 'danger'] as $key => $class)
            @if (session($key))
                <div class="alert alert-{{ $class }}">{{ session($key) }}</div>
            @endif
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Ajouter une tranche</div>
            <div class="card-body">
                <form action="{{ route('admin.salary-grids.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label">Seuil minimum</label>
                        <input type="number" step="0.01" min="0" name="seuil_min" class="form-control" value="{{ old('seuil_min') }}">
                        <div class="form-text">Laisser vide pour une borne basse sans limite.</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Seuil maximum *</label>
                        <input type="number" step="0.01" min="0" name="seuil_max" class="form-control" value="{{ old('seuil_max') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Salaire de base *</label>
                        <input type="number" step="0.01" min="0" name="salaire_base" class="form-control" value="{{ old('salaire_base') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Commission travail *</label>
                        <input type="number" step="0.01" min="0" name="commission_travail" class="form-control" value="{{ old('commission_travail', 0) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Taux carnet (%) *</label>
                        <input type="number" step="0.01" min="0" max="100" name="taux_carnet" class="form-control" value="{{ old('taux_carnet', 25) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date d’effet *</label>
                        <input type="date" name="date_debut" class="form-control" value="{{ old('date_debut', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fin d’application</label>
                        <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <input type="hidden" name="est_actif" value="0">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="est_actif" value="1" class="form-check-input" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary" type="submit">Ajouter la tranche</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Seuil min.</th>
                            <th>Seuil max.</th>
                            <th>Salaire de base</th>
                            <th>Commission travail</th>
                            <th>Taux carnet</th>
                            <th>Application</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grilles as $grille)
                            <tr>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="seuil_min" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm" value="{{ $grille->seuil_min }}">
                                        <div class="form-text">Vide = sans minimum</div>
                                    </td>
                                    <td><input type="number" step="0.01" min="0" name="seuil_max" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm" value="{{ $grille->seuil_max }}" required></td>
                                    <td><input type="number" step="0.01" min="0" name="salaire_base" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm" value="{{ $grille->salaire_base }}" required></td>
                                    <td><input type="number" step="0.01" min="0" name="commission_travail" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm" value="{{ $grille->commission_travail }}" required></td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="taux_carnet" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm" value="{{ $grille->taux_carnet }}" required></td>
                                    <td>
                                        <input type="date" name="date_debut" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm mb-1" value="{{ $grille->date_debut->toDateString() }}" required>
                                        <input type="date" name="date_fin" form="grid-form-{{ $grille->id }}" class="form-control form-control-sm" value="{{ optional($grille->date_fin)->toDateString() }}">
                                    </td>
                                    <td>
                                        <input type="hidden" name="est_actif" value="0" form="grid-form-{{ $grille->id }}">
                                        <input type="checkbox" name="est_actif" value="1" form="grid-form-{{ $grille->id }}" class="form-check-input" {{ $grille->est_actif ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        @if ($grille->date_debut->isFuture())
                                            <form id="grid-form-{{ $grille->id }}" action="{{ route('admin.salary-grids.update', $grille) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                                            </form>
                                        @elseif ($grille->est_actif && (! $grille->date_fin || $grille->date_fin->isFuture()))
                                            <form action="{{ route('admin.salary-grids.close', $grille) }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="date" name="date_fin" class="form-control form-control-sm" min="{{ now()->toDateString() }}" value="{{ now()->addDay()->toDateString() }}" required>
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Clôturer</button>
                                            </form>
                                            <div class="form-text">Fin incluse ; la nouvelle tranche commence le lendemain.</div>
                                        @else
                                            <span class="text-muted small">Historique protégé</span>
                                        @endif
                                    </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucune tranche configurée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
