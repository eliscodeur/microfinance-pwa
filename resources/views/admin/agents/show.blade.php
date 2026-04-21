@extends('admin.layouts.sidebar')

@section('content')

<!-- <h2>Détails de l'Agent : {{ $agent->nom }}</h2> -->

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Agent : {{ $agent->code_agent }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center">
                @if($agent->image)
                    <img src="{{ asset('storage/' . $agent->image) }}" alt="Photo de l'agent" class="img-fluid rounded-circle border shadow" style="width: 150px; height: 150px; object-fit: cover; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal">
                @else
                    <div class="bg-light border rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                        <i class="bi bi-person-circle" style="font-size: 80px; color: #ccc;"></i>
                    </div>
                @endif
                <p class="mt-2 text-muted">Photo</p>
            </div>
            <div class="col-md-8">
                <h4 class="text-primary">{{ $agent->nom }}</h4>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <p><strong>Email :</strong> {{ $agent->user->email ?? 'Pas d\'email' }}</p>
                        <p><strong>Téléphone :</strong> {{ $agent->telephone }}</p>
                        <p><strong>Actif :</strong> 
                            <span class="badge {{ $agent->actif ? 'bg-success' : 'bg-danger' }}">
                                {{ $agent->actif ? 'Oui' : 'Non' }}
                            </span>
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p><strong>Clients Gérés :</strong> {{ $clientsCount }}</p>
                        <p><strong>Créé le :</strong> {{ $agent->created_at->format('d/m/Y') }}</p>
                        <p><strong>Mis à jour :</strong> {{ $agent->updated_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Historique des Attributions de Clients
    </div>
    <div class="card-body">
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Date d'Attribution</th>
                    <th>Date de Désattribution</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $entry)
                <tr>
                    <td>{{ $entry->client->nom ?? 'Client supprimé' }}</td>
                    <td>{{ optional($entry->assigned_at)->format('d/m/Y H:i') ?? 'N/A' }}</td>
                    <td>{{ optional($entry->unassigned_at)->format('d/m/Y H:i') ?? 'En cours' }}</td>
                    <td>
                        @if($entry->unassigned_at)
                            <span class="badge bg-secondary">Désassigné</span>
                        @else
                            <span class="badge bg-success">Actif</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">Aucun historique d'attribution.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<!-- <div class="mb-3">
    Statut actuel : 
    @if($agent->actif)
        <span class="badge bg-success">Actif</span>
    @else
        <span class="badge bg-danger">Inactif / Suspendu</span>
    @endif
</div> -->
<div class="d-flex gap-2 mt-3">
    @can('Activer/Désactiver')
    <button type="button" class="btn {{ $agent->actif ? 'btn-warning' : 'btn-success' }}" data-bs-toggle="modal" data-bs-target="#toggleModal">
        {{ $agent->actif ? 'Désactiver' : 'Activer' }} Agent
    </button>
    @endcan
    <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary">Retour à la liste</a>
    @can('Modifier données')
    <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-primary">Modifier</a>
    @endcan
</div>

<!-- Modal pour toggle status -->
<div class="modal fade" id="toggleModal" tabindex="-1" aria-labelledby="toggleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleModalLabel">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir {{ $agent->actif ? 'désactiver' : 'activer' }} cet agent ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="{{ route('admin.agents.toggleStatus', $agent->id) }}" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $agent->actif ? 'btn-warning' : 'btn-success' }}">
                        Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection