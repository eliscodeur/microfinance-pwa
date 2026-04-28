@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Catégories de Tontine</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle me-2"></i> Nouvelle Catégorie
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Libellé</th>
                            <th>Montant Journalier</th>
                            <th>Montant Total (Mois)</th>
                            <th>Commission</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td><span class="fw-bold">{{ $category->libelle }}</span></td>
                            <td>{{ number_format($category->montant_journalier, 0, ',', ' ') }} FCFA</td>
                            <td><span class="badge bg-info-subtle text-info">{{ number_format($category->montant_total, 0, ',', ' ') }} FCFA</span></td>
                            <td>{{ number_format($category->commission, 0, ',', ' ') }} FCFA</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary edit-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editCategoryModal"
                                    data-id="{{ $category->id }}"
                                    data-libelle="{{ $category->libelle }}"
                                    data-montant="{{ $category->montant_journalier }}"
                                    data-commission="{{ $category->commission }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette catégorie ?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.categories.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Libellé (ex: Type 500)</label>
                    <input type="text" name="libelle" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Montant Journalier</label>
                    <input type="number" name="montant_journalier" class="form-control" placeholder="ex: 500" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Commission (Frais de carnet)</label>
                    <input type="number" name="commission" class="form-control" placeholder="ex: 500" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editForm" method="POST" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Modifier la catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Libellé</label>
                    <input type="text" name="libelle" id="edit_libelle" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Montant Journalier</label>
                    <input type="number" name="montant_journalier" id="edit_montant" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Commission</label>
                    <input type="number" name="commission" id="edit_commission" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-success">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Récupération des données depuis les attributs data-
            const id = this.getAttribute('data-id');
            const libelle = this.getAttribute('data-libelle');
            const montant = this.getAttribute('data-montant');
            const commission = this.getAttribute('data-commission');

            // Remplissage du formulaire
            document.getElementById('edit_libelle').value = libelle;
            document.getElementById('edit_montant').value = montant;
            document.getElementById('edit_commission').value = commission;

            // Mise à jour de l'URL de l'action du formulaire
            document.getElementById('editForm').action = `/admin/categories/${id}`;
        });
    });
</script>
@endpush