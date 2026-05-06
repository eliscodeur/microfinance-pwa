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

<!-- Carte Gains et Commissions -->
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Gains et Commissions</h5>
        <div>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#bonusModal">
                <i class="bi bi-plus-circle"></i> Attribuer un bonus
            </button>
            <form method="POST" action="{{ route('admin.agents.calculateCommissions', $agent) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm ms-2">
                    <i class="bi bi-calculator"></i> Calculer Commissions
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="text-center">
                    <h4 class="text-success">{{ number_format($agent->portefeuille_virtuel, 0, ',', ' ') }} F</h4>
                    <p class="text-muted">Portefeuille Virtuel</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <h4 class="text-primary">{{ number_format($agent->bonuses()->where('motif', 'like', '%Commission%')->sum('montant'), 0, ',', ' ') }} F</h4>
                    <p class="text-muted">Commissions Automatiques</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <h4 class="text-info">{{ number_format($agent->bonuses()->where('motif', 'not like', '%Commission%')->sum('montant'), 0, ',', ' ') }} F</h4>
                    <p class="text-muted">Bonus Manuels</p>
                </div>
            </div>
        </div>
        <hr>
        <h6>Historique des 5 derniers bonus</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Motif</th>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agent->bonuses()->latest()->take(5)->get() as $bonus)
                    <tr>
                        <td>{{ $bonus->date_attribution->format('d/m/Y') }}</td>
                        <td class="text-success fw-bold">{{ number_format($bonus->montant, 0, ',', ' ') }} F</td>
                        <td>{{ $bonus->motif }}</td>
                        <td>{{ $bonus->admin->name ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Aucun bonus enregistré.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <hr>
        <h6>Évolution des Gains (12 derniers mois)</h6>
        <canvas id="gainsChart" width="400" height="200"></canvas>
        @if($agent->checkPlafondCaisse())
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i> Alerte : Le portefeuille virtuel dépasse le plafond de caisse (1 000 000 F). Veuillez reverser les fonds.
        </div>
        @endif
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

<!-- Modal pour attribuer un bonus -->
<div class="modal fade" id="bonusModal" tabindex="-1" aria-labelledby="bonusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bonusModalLabel">Attribuer un Bonus à {{ $agent->nom }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.agents.storeBonus', $agent) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant (FCFA)</label>
                        <input type="number" class="form-control" id="montant" name="montant" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="motif" class="form-label">Motif</label>
                        <input type="text" class="form-control" id="motif" name="motif" required maxlength="255" placeholder="Ex: Performance exceptionnelle">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Attribuer le Bonus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Données pour le graphique des gains
    const bonusData = @json($agent->bonuses()->selectRaw('YEAR(date_attribution) as year, MONTH(date_attribution) as month, SUM(montant) as total')
        ->where('date_attribution', '>=', now()->subMonths(12))
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get()
        ->map(function($item) {
            return {
                label: $item->year . '-' + String($item->month).padStart(2, '0'),
                value: $item->total
            };
        }));

    const ctx = document.getElementById('gainsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: bonusData.map(d => d.label),
            datasets: [{
                label: 'Gains (FCFA)',
                data: bonusData.map(d => d.value),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

@endsection