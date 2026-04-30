@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Fiche Client : {{ $client->nom }} {{ $client->prenom }}</h2>
            <p class="text-muted mb-0">Détails personnels, carnets et historique d'affectation.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Retour</a>
            <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn btn-warning text-white">Modifier la fiche</a>
            <a href="{{ route('admin.clients.exportHistory', $client->id) }}" class="btn btn-outline-primary">Exporter historique</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="card-body">
                    <div class="mb-3">
                        @if($client->photo)
                            <img src="{{ asset('storage/' . $client->photo) }}" alt="Photo {{ $client->nom }}" 
                                 class="rounded-circle img-thumbnail shadow-sm" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto shadow-sm" 
                                 style="width: 150px; height: 150px; border: 2px dashed #ccc;">
                                <i class="bi bi-person text-muted" style="font-size: 4rem;"></i>
                            </div>
                        @endif
                    </div>
                    <h5 class="fw-bold mb-1">{{ $client->nom }} {{ $client->prenom }}</h5>
                    <span class="badge bg-primary mb-3">{{ $client->profession ?: 'Profession non définie' }}</span>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p class="small text-muted mb-1"><i class="bi bi-telephone me-2"></i>{{ $client->telephone }}</p>
                        <p class="small text-muted mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $client->adresse ?: 'Aucune adresse' }}</p>
                        <p class="small text-muted mb-0"><i class="bi bi-person-badge me-2"></i>Agent : <strong>{{ $client->agent->nom ?? 'Aucun' }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold d-flex align-items-center">
                    <i class="bi bi-card-text me-2"></i> Informations complémentaires
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">Date de naissance</label>
                            <span class="fw-semibold">{{ $client->date_naissance ? \Carbon\Carbon::parse($client->date_naissance)->format('d/m/Y') : '---' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">Lieu de naissance</label>
                            <span class="fw-semibold">{{ $client->lieu_naissance ?: '---' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">Genre</label>
                            <span class="fw-semibold text-capitalize">{{ $client->genre ?: '---' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">Statut matrimonial</label>
                            <span class="fw-semibold text-capitalize">{{ $client->statut_matrimonial ?: '---' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">Nationalité</label>
                            <span class="fw-semibold">{{ $client->nationalite ?: '---' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small d-block">Profession</label>
                            <span class="fw-semibold">{{ $client->profession ?: '---' }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded shadow-sm">
                        <h6 class="fw-bold mb-3 small text-uppercase text-primary"><i class="bi bi-exclamation-triangle me-2"></i>Personne de référence (Urgence)</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <label class="text-muted small d-block">Nom du référent</label>
                                <span class="fw-semibold">{{ $client->reference_nom ?: 'Non renseigné' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block">Téléphone du référent</label>
                                <span class="fw-semibold">{{ $client->reference_telephone ?: 'Non renseigné' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Historique des affectations d'agents</div>
                <div class="card-body p-0 text-center">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Début de mission</th>
                                    <th>Fin de mission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($client->agentHistory as $history)
                                <tr>
                                    <td>{{ $history->agent->nom ?? 'Agent supprimé' }}</td>
                                    <td>{{ optional($history->assigned_at)->format('d/m/Y H:i') ?? '---' }}</td>
                                    <td>
                                        @if($history->unassigned_at)
                                            {{ $history->unassigned_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="badge bg-success">Actuel</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-muted">Aucun historique disponible.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Carnets actifs et clôturés</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Numéro</th>
                            <!-- <th>Référence Physique</th> -->
                            <th>Statut</th>
                            <th>Nombre de Cycles</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($client->carnets as $carnet)
                            <tr>
                                <td><strong>{{ $carnet->numero }}</strong></td>
                                <!-- <td>{{ $carnet->reference_physique ?: '---' }}</td> -->
                                <td>
                                    @php
                                        $color = match($carnet->statut) {
                                            'actif' => 'success',
                                            'clôturé' => 'secondary',
                                            default => 'info'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($carnet->statut) }}</span>
                                </td>
                                <td>{{ $carnet->cycles->count() }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.carnets.show', $carnet->id) }}" class="btn btn-sm btn-outline-primary">Détails du carnet</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Aucun carnet lié à ce client.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection