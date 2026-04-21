@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Clients</h2>
            <p class="text-muted mb-0">Suivi des clients, de leurs affectations et de leurs carnets.</p>
        </div>
        <div class="d-flex gap-2">
            @can('GÃ©rer Clients')
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Ajouter client</a>
            @endcan
            <a href="{{ route('admin.clients.export', 'csv') }}" class="btn btn-outline-secondary">Exporter CSV</a>
            <a href="{{ route('admin.clients.export', 'excel') }}" class="btn btn-outline-success">Exporter Excel</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.clients.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small text-muted fw-bold">Recherche</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Nom, telephone, adresse, agent, numero carnet..."
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">Agent</label>
                    <select name="agent_id" class="form-select">
                        <option value="">Tous les agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ (string) $agentId === (string) $agent->id ? 'selected' : '' }}>
                                {{ $agent->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Adresse</th>
                        <th>Agent</th>
                        <th>Carnets</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td>
                                <strong>{{ $client->nom }}</strong><br>
                                <small class="text-muted">#{{ $client->id }}</small>
                            </td>
                            <td>{{ $client->telephone ?: '---' }}</td>
                            <td>{{ $client->adresse ?: '---' }}</td>
                            <td>{{ $client->agent->nom ?? 'Non affecte' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $client->carnets_count }}</span>
                                @if($client->carnets_count > 0)
                                    <div class="small text-muted mt-1">
                                        {{ $client->carnets->take(2)->pluck('numero')->implode(', ') }}
                                        @if($client->carnets_count > 2)
                                            ...
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.clients.show', $client->id) }}" class="btn btn-sm btn-outline-info">Details</a>
                                    @can('Modifier donnÃ©es')
                                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn btn-sm btn-outline-warning">Modifier</a>
                                    @endcan
                                    @can('GÃ©rer Clients')
                                    <form method="POST" action="{{ route('admin.clients.destroy', $client->id) }}" onsubmit="return confirm('Supprimer ce client ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Aucun client enregistre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $clients->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
