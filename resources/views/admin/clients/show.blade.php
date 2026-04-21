@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Client : {{ $client->nom }}</h2>
            <p class="text-muted mb-0">Fiche client, carnets et historique d'affectation.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Retour</a>
            <a href="{{ route('admin.clients.exportHistory', $client->id) }}" class="btn btn-outline-primary">Exporter historique</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Informations client</div>
                <div class="card-body">
                    <p><strong>Nom :</strong> {{ $client->nom }}</p>
                    <p><strong>Telephone :</strong> {{ $client->telephone ?: '---' }}</p>
                    <p><strong>Adresse :</strong> {{ $client->adresse ?: '---' }}</p>
                    <p><strong>Agent actuel :</strong> {{ $client->agent->nom ?? 'Aucun' }}</p>
                    <p class="mb-0"><strong>Carnets :</strong> {{ $client->carnets->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Historique des agents</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Attribution</th>
                                    <th>Fin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($client->agentHistory as $history)
                                <tr>
                                    <td>{{ $history->agent->nom ?? 'Agent supprime' }}</td>
                                    <td>{{ optional($history->assigned_at)->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td>{{ optional($history->unassigned_at)->format('d/m/Y H:i') ?? 'Actuel' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Aucun historique disponible.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Carnets du client</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Numero</th>
                            <th>Reference</th>
                            <th>Statut</th>
                            <th>Cycles</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($client->carnets as $carnet)
                            <tr>
                                <td><strong>{{ $carnet->numero }}</strong></td>
                                <td>{{ $carnet->reference_physique ?: '---' }}</td>
                                <td>{{ ucfirst($carnet->statut ?? '---') }}</td>
                                <td>{{ $carnet->cycles->count() }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.carnets.show', $carnet->id) }}" class="btn btn-sm btn-outline-primary">Voir carnet</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Aucun carnet lie a ce client.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
