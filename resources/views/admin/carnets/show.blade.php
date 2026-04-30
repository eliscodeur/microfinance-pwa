@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Gestion des Carnets</h2>
            <p class="text-muted">Suivi des comptes épargne et tontines clients.</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createCarnetModal">
            <i class="fas fa-plus fa-sm text-white-50 me-2"></i> Nouveau Carnet
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Carnets</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $carnets->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4 border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Numéro</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Détails / Parent</th>
                            <th>Statut</th>
                            <th>Date Début</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($carnets as $carnet)
                        <tr>
                            <td><span class="fw-bold text-primary">{{ $carnet->numero }}</span></td>
                            <td>{{ $carnet->client->nom }} {{ $carnet->client->prenom }}</td>
                            <td>
                                @if($carnet->type === 'tontine')
                                    <span class="badge bg-info-soft text-info">Tontine</span>
                                @else
                                    <span class="badge bg-warning-soft text-warning">Compte</span>
                                @endif
                            </td>
                            <td>
                                @if($carnet->type === 'tontine')
                                    <small>{{ $carnet->categoryTontine->libelle ?? '---' }}</small>
                                @else
                                    @if($carnet->parent)
                                        <small class="text-muted">Lié à : #{{ $carnet->parent->numero }}</small>
                                    @else
                                        <small class="text-muted">Épargne libre</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $carnet->statut == 'actif' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($carnet->statut) }}
                                </span>
                            </td>
                            <td>{{ $carnet->date_debut->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.carnets.show', $carnet->id) }}" class="btn btn-sm btn-light border">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de <strong>{{ $carnets->firstItem() }}</strong> à <strong>{{ $carnets->lastItem() }}</strong> sur <strong>{{ $carnets->total() }}</strong> carnets
                </div>
                <div>
                    {{ $carnets->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createCarnetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ouvrir un nouveau carnet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.carnets.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Sélectionner le Client</label>
                            <select name="client_id" class="form-select select2" required>
                                <option value="">Choisir un client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->nom }} {{ $client->prenom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type de Carnet</label>
                            <select name="type" id="typeSelect" class="form-select" required onchange="toggleFields()">
                                <option value="tontine">Tontine (Fixe)</option>
                                <option value="compte">Compte (Libre)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de début</label>
                            <input type="date" name="date_debut" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-12" id="tontineFields">
                            <label class="form-label fw-bold text-info">Durée de la Tontine</label>
                            <select name="category_tontine_id" class="form-select">
                                <option value="">Choisir la catégorie...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 d-none" id="compteFields">
                            <label class="form-label fw-bold text-warning">Lier à une Tontine existante ? (Optionnel)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">Aucun lien (Compte indépendant)</option>
                                @foreach($carnetsTontine as $ct)
                                    <option value="{{ $ct->id }}">Carnet #{{ $ct->numero }} ({{ $ct->client->nom }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Créer le Carnet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('typeSelect').value;
    const tontineDiv = document.getElementById('tontineFields');
    const compteDiv = document.getElementById('compteFields');

    if (type === 'tontine') {
        tontineDiv.classList.remove('d-none');
        compteDiv.classList.add('d-none');
    } else {
        tontineDiv.classList.add('d-none');
        compteDiv.classList.remove('d-none');
    }
}
</script>

<style>
    /* Pour des badges plus modernes */
    .bg-info-soft { background-color: #e0f7fa; color: #00acc1; }
    .bg-warning-soft { background-color: #fff8e1; color: #ffb300; }
    .modal-content { border-radius: 15px; }
</style>
@endsection