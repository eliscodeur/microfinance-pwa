@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Gestion des Charges</h1>
                <p class="text-muted small">Organisation des types et des postes de dépenses de l'entreprise.</p>
            </div>
            <div class="d-flex gap-2">
                <!-- Bouton déclenchant le Modal d'ajout de Type -->
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#typeModal">
                    <i class="bi bi-folder-plus me-1"></i> Ajouter un Type
                </button>
                <!-- Bouton déclenchant le Modal d'ajout de Catégorie -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="bi bi-plus-lg me-1"></i> Ajouter une Catégorie
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            @forelse($typesCharges as $type)
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                            <h5 class="card-title mb-0 text-primary fw-bold">
                                <i class="bi bi-folder2-open me-2"></i>{{ $type->libelle }}
                            </h5>
                            <span class="badge bg-secondary rounded-pill">
                                {{ $type->categories->count() }} catégorie{{ $type->categories->count() > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="card-body">
                            @if ($type->description)
                                <p class="text-muted small mb-3">{{ $type->description }}</p>
                            @endif

                            @if ($type->categories->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach ($type->categories as $cat)
                                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-semibold text-dark">{{ $cat->libelle }}</span>
                                                @if ($cat->actif == 0)
                                                    <span class="badge bg-danger ms-2"
                                                        style="font-size: 0.65rem;">Inactif</span>
                                                @endif
                                            </div>
                                            <div>
                                                <form action="{{ route('admin.charges.categories.toggle', $cat->ulid) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm {{ $cat->actif == 0 ? 'btn-outline-success' : 'btn-outline-warning' }}"
                                                        title="{{ $cat->actif == 0 ? 'Activer' : 'Désactiver' }}">
                                                        <i
                                                            class="bi {{ $cat->actif == 0 ? 'bi-play-fill' : 'bi-pause-fill' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted fst-italic small mb-0 text-center py-3">Aucune catégorie pour ce type.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">Aucun type de charge enregistré pour le moment.</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL D'AJOUT DE TYPE DE CHARGE -->

    <div class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.charges.types.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="typeModalLabel">Ajouter un Type de Charge</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="type_libelle" class="form-label fw-semibold">Libellé du type <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="libelle" id="type_libelle" class="form-control" required>
                        </div>

                        <!-- AJOUT DU CHAMP CODE REQUIS PAR LA MIGRATION -->
                        <div class="mb-3">
                            <label for="type_code" class="form-label fw-semibold">Code unique <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="code" id="type_code" class="form-control" required>
                            <div class="form-text text-muted">Un code court unique en majuscules (ex: EXP).</div>
                        </div>

                        <div class="mb-3">
                            <label for="type_description" class="form-label fw-semibold">Description (Optionnel)</label>
                            <textarea name="description" id="type_description" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL D'AJOUT DE CATEGORIE -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.charges.categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="categoryModalLabel">Ajouter une Catégorie de Charge</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- CORRECTION : name="types_charge_id" avec un 's' -->
                        <div class="mb-3">
                            <label for="types_charge_id" class="form-label fw-semibold">Type de Charge <span
                                    class="text-danger">*</span></label>
                            <select name="types_charge_id" id="types_charge_id" class="form-select" required>
                                <option value="">-- Choisir un type --</option>
                                @foreach ($typesCharges as $type)
                                    <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="libelle" class="form-label fw-semibold">Libellé de la catégorie <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="libelle" id="libelle" class="form-control" required>
                        </div>

                        <!-- AJOUT : Code analytique présent dans le modèle -->
                        <div class="mb-3">
                            <label for="code_analytique" class="form-label fw-semibold">Code Analytique
                                (Optionnel)</label>
                            <input type="text" name="code_analytique" id="code_analytique" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description (Optionnel)</label>
                            <textarea name="description" id="description" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
