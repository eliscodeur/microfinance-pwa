@php
    // Force la récupération si le contrôleur échoue (Solution de secours Windows)
    if (!isset($categories)) { $categories = \App\Models\CategoryTontine::all(); }
    if (!isset($clients)) { $clients = \App\Models\Client::all(); }
    if (!isset($tontinesActives)) { $tontinesActives = \App\Models\Carnet::where('type', 'tontine')->get(); }
    if (!isset($carnets)) { $carnets = \App\Models\Carnet::with(['client', 'categoryTontine'])->latest()->get(); }
@endphp

@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4"><i class="bi bi-bank me-2"></i>Administration des Carnets</h2>
    </div>

    <ul class="nav nav-tabs mb-4" id="carnetTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content" type="button" role="tab">
                <i class="bi bi-journal-text"></i> Liste des Carnets
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link text-primary" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-content" type="button" role="tab">
                <i class="bi bi-journal-plus"></i> Ajouter carnet
            </button>
        </li>
       
    </ul>

    <div class="tab-content mt-3" id="carnetTabsContent">
        
        <div class="tab-pane fade show active" id="list-content" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Numéro</th>
                                <th>Client / Téléphone</th>
                                <th>Type</th>
                                <th>Détails</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($carnets as $carnet)
                            <tr>
                                <td><strong class="text-primary">{{ $carnet->numero }}</strong></td>
                                <td>
                                    {{ $carnet->client->nom }} {{ $carnet->client->prenom }} <br>
                                    <small class="text-muted">{{ $carnet->client->telephone }}</small>
                                </td>
                                <td>
                                    @if($carnet->type === 'tontine')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">Tontine</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Compte</span>
                                    @endif
                                </td>
                                <td>
                                    @if($carnet->type === 'tontine')
                                        <small>{{ $carnet->categoryTontine->libelle ?? 'Non défini' }}</small>
                                    @else
                                        <small class="text-muted">{{ $carnet->parent ? 'Lié à #'.$carnet->parent->numero : 'Indépendant' }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-{{ $carnet->statut == 'actif' ? 'success' : 'dark' }} border">
                                        {{ ucfirst($carnet->statut) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.carnets.show', $carnet->id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                            data-id="{{ $carnet->id }}" data-type="{{ $carnet->type }}" 
                                            data-category="{{ $carnet->category_tontine_id }}" data-parent="{{ $carnet->parent_id }}"
                                            data-client="{{ $carnet->client_id }}" data-client-name="{{ $carnet->client->nom }} {{ $carnet->client->prenom }}"
                                            data-date="{{ $carnet->date_debut->format('Y-m-d') }}" data-numero="{{ $carnet->numero }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">Aucun carnet enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="add-content" role="tabpanel">
            <div class="card shadow-sm border-0 p-4">
                <h5 class="mb-4" id="form-title">Ouverture d'un nouveau carnet</h5>
                <form action="{{ route('admin.carnets.store') }}" method="POST" id="carnet-form">
                    @csrf
                    <div id="method-field"></div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Client</label>
                            <input list="clientList" id="field-client-search" class="form-control" placeholder="Rechercher un client..." required>
                            <input type="hidden" name="client_id" id="field-client-id">
                            <datalist id="clientList">
                                @foreach($clients as $client)
                                    <option data-id="{{ $client->id }}" value="{{ $client->nom }} {{ $client->prenom }} ({{ $client->telephone }})">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type de Carnet</label>
                            <select name="type" id="typeSelect" class="form-select" required onchange="toggleFields()">
                                <option value="tontine">Tontine (Standard)</option>
                                <option value="compte">Compte Épargne (Alphanumérique)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de début</label>
                            <input type="date" name="date_debut" id="field-date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-12" id="tontineFields">
                            <label class="form-label fw-bold text-info">Durée / Catégorie</label>
                            <select name="category_tontine_id" id="field-category" class="form-select">
                                <option value="">Choisir une catégorie...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->libelle }} ({{ $cat->nombre_jours }} jours)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="compteFields">
                            <label class="form-label fw-bold text-warning">Lier à une tontine (Optionnel)</label>
                            <select name="parent_id" id="field-parent" class="form-select">
                                <option value="">Compte indépendant</option>
                                @foreach($tontinesActives as $tontine)
                                    <option value="{{ $tontine->id }}">#{{ $tontine->numero }} - {{ $tontine->client->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" id="submit-btn" class="btn btn-primary px-4">Enregistrer</button>
                        <button type="button" id="cancel-edit" class="btn btn-light border d-none">Annuler</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="tab-pane fade" id="cat-content" role="tabpanel">
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-3">Nouvelle Catégorie</h6>
                        <form action="{{ route('admin.categories.store') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="small fw-bold">Libellé</label>
                                <input type="text" name="libelle" class="form-control form-control-sm" placeholder="ex: Tontine Mensuelle" required>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Nombre de cycles</label>
                                <input type="number" min="1" name="nombre_cycles" class="form-control form-control-sm" value="1" required>
                            </div>
                            <!-- <div class="mb-3">
                                <label class="small fw-bold">Description</label>
                                <textarea name="description" class="form-control form-control-sm" rows="2"></textarea>
                            </div> -->
                            <button type="submit" class="btn btn-info btn-sm w-100 text-white">Ajouter la catégorie</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Libellé</th>
                                        <th>Nombre de cycles</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $cat)
                                    <tr>
                                        <td><strong>{{ $cat->libelle }}</strong></td>
                                        <td><span class="badge bg-secondary">{{ $cat->nombre_cycles }} cycles</span></td>
                                      
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center py-4">Aucune catégorie.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Logique identique à la précédente pour le toggle et l'édition...
function toggleFields() {
    const type = document.getElementById('typeSelect').value;
    document.getElementById('tontineFields').classList.toggle('d-none', type !== 'tontine');
    document.getElementById('compteFields').classList.toggle('d-none', type === 'tontine');
}

document.addEventListener('DOMContentLoaded', function() {
    const clientSearchInput = document.getElementById('field-client-search');
    const clientIdInput = document.getElementById('field-client-id');

    // Validation du client lors de la saisie dans le datalist
    clientSearchInput.addEventListener('change', function() {
        const list = document.getElementById('clientList');
        const option = Array.from(list.options).find(opt => opt.value === this.value);
        if (option) clientIdInput.value = option.getAttribute('data-id');
    });
});
</script>
@endsection