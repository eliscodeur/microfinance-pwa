@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        {{-- En-tête avec les actions --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1">Fiche Client : {{ $client->nom }} {{ $client->prenom }}</h2>
                <p class="text-muted mb-0">Détails personnels, numéros de carnets et historique d'affectation.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Retour</a>
                <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn btn-warning text-white">Modifier la
                    fiche</a>
                {{-- Bouton déclenchant la modale d'ajout de numéro de carnet --}}
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCarnetModal">
                    <i class="bi bi-journal-plus me-1"></i> Nouveau numéro de carnet
                </button>
            </div>
        </div>

        {{-- Alertes de succès ou d'erreur --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4 mb-4">
            {{-- Colonne de gauche : Profil --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            @if ($client->photo)
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
                            <p class="small text-muted mb-1"><i class="bi bi-telephone me-2"></i>{{ $client->telephone }}
                            </p>
                            <p class="small text-muted mb-1"><i
                                    class="bi bi-geo-alt me-2"></i>{{ $client->adresse ?: 'Aucune adresse' }}</p>
                            {{-- <p class="small text-muted mb-0"><i class="bi bi-person-badge me-2"></i>Agent :
                                <strong>{{ $client->agent->nom ?? 'Aucun' }}</strong>
                            </p> --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne de droite : Infos complémentaires --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold d-flex align-items-center">
                        <i class="bi bi-card-text me-2"></i> Informations complémentaires
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-6 mb-3">
                                <label class="text-muted small d-block">Date de naissance</label>
                                <span
                                    class="fw-semibold">{{ $client->date_naissance ? \Carbon\Carbon::parse($client->date_naissance)->format('d/m/Y') : '---' }}</span>
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
                            <h6 class="fw-bold mb-3 small text-uppercase text-primary"><i
                                    class="bi bi-exclamation-triangle me-2"></i>Personne de référence (Urgence)</h6>
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

        {{-- Section : Liste des numéros de carnets du client --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-3">
                        <span><i class="bi bi-journal-text me-2"></i> Numéros de carnets du client
                            ({{ $client->carnetNumbers->count() ?? 0 }})</span>
                    </div>
                    <div class="card-body">
                        @if (isset($client->carnetNumbers) && $client->carnetNumbers->count() > 0)
                            <div class="table-responsive">
                                <table id="carnetsTable" class="table table-hover align-middle w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Numéro de carnet</th>
                                            <th>Type</th>
                                            <th>Date de création</th>
                                            <th>Statut</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($client->carnetNumbers as $carnetNumber)
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $carnetNumber->numero }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-secondary text-capitalize">{{ $carnetNumber->type_carnet }}</span>
                                                </td>
                                                <td>{{ $carnetNumber->created_at ? $carnetNumber->created_at->format('d/m/Y H:i') : '---' }}
                                                </td>
                                                <td>
                                                    @if ($carnetNumber->statut === 'disponible')
                                                        <span class="badge bg-success">Disponible</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Utilisé</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-secondary"
                                                        disabled>Détails</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">Aucun numéro de carnet généré pour ce client pour le
                                moment.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALE : Génération ou Création d'un numéro de carnet --}}
    <div class="modal fade" id="createCarnetModal" tabindex="-1" aria-labelledby="createCarnetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <form action="{{ route('admin.client-carnets.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">

                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="createCarnetModalLabel"><i
                                class="bi bi-journal-plus me-2 text-primary"></i>Générer un numéro de carnet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="type_carnet" class="form-label fw-semibold">Type de carnet <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="type_carnet" name="type_carnet" required>
                                <option value="tontine">Tontine</option>
                                <option value="compte">Épargne / Standard</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quantite" class="form-label fw-semibold">Quantité à générer</label>
                            <input type="number" class="form-control" id="quantite" name="quantite" value="1"
                                min="1" max="10" required>
                            <div class="form-text">Le système générera automatiquement les numéros uniques correspondants.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Générer le(s) numéro(s)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $('#carnetsTable').DataTable({
                language: {
                    processing: "Traitement en cours...",
                    search: "Rechercher&nbsp;:",
                    lengthMenu: "Afficher _MENU_ éléments",
                    info: "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
                    infoEmpty: "Affichage de l'élément 0 à 0 sur 0 élément",
                    infoFiltered: "(filtré à partir de _MAX_ éléments au total)",
                    loadingRecords: "Chargement en cours...",
                    zeroRecords: "Aucun élément trouvé",
                    emptyTable: "Aucune donnée disponible dans le tableau",
                    paginate: {

                        previous: "Précédent",
                        next: "Suivant",

                    },
                    aria: {
                        sortAscending: ": activer pour trier la colonne par ordre croissant",
                        sortDescending: ": activer pour trier la colonne par ordre décroissant"
                    }
                },
                order: [
                    [2, 'desc']
                ],
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                responsive: true
            });
        });
    </script>
@endpush
