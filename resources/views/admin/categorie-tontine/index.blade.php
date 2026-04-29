@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Catégories de Tontine</h4>
            <p class="text-muted small">Configuration des types de carnets et cycles</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-lg me-2"></i> Nouvelle Catégorie
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Libellé</th>
                            <th>Prix (FCFA)</th>
                            <th>Cycles</th>
                            <th>Description</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $category->libelle }}</td>
                            <td>
                                <span class="badge bg-success-subtle text-success px-3">
                                    {{ number_format($category->prix, 0, ',', ' ') }} F
                                </span>
                            </td>
                            <td><i class="bi bi-arrow-repeat me-1"></i> {{ $category->nombre_cycles }} cycles</td>
                            <td class="text-muted small">{{ Str::limit($category->description, 40) }}</td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light edit-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editCategoryModal"
                                        data-id="{{ $category->id }}"
                                        data-libelle="{{ $category->libelle }}"
                                        data-prix="{{ $category->prix }}"
                                        data-cycles="{{ $category->nombre_cycles }}"
                                        data-description="{{ $category->description }}">
                                        <i class="bi bi-pencil text-primary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light delete-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteCategoryModal"
                                        data-id="{{ $category->id }}"
                                        data-libelle="{{ $category->libelle }}">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Aucune catégorie enregistrée.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addCategoryForm" action="{{ route('admin.categories.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Nouvelle Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Libellé</label>
                    <input type="text" name="libelle" class="form-control" placeholder="ex: Pack Standard" required>
                    </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Prix (FCFA)</label>
                        <input type="number" name="prix" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Nombre de cycles</label>
                        <input type="number" name="nombre_cycles" class="form-control" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editForm" method="POST" class="modal-content border-0 shadow">
            @csrf @method('PUT')
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Modifier Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Libellé</label>
                    <input type="text" name="libelle" id="edit_libelle" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Prix (FCFA)</label>
                        <input type="number" name="prix" id="edit_prix" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Cycles</label>
                        <input type="number" name="nombre_cycles" id="edit_cycles" class="form-control" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-success">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Supprimer ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-danger display-4 mb-3"></i>
                <p class="mb-0">Voulez-vous vraiment supprimer la catégorie <br><strong id="delete_category_name"></strong> ?</p>
                <small class="text-muted">Cette action est irréversible.</small>
            </div>
            <div class="modal-footer border-top-0 justify-content-center">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-btn');
        if (btn) {
            const editForm = document.getElementById('editForm');
            if (!editForm) return;

            // Nettoyage des anciennes erreurs avant d'ouvrir
            clearErrors(editForm);

            // Remplissage des champs
            document.getElementById('edit_libelle').value = btn.dataset.libelle || '';
            document.getElementById('edit_prix').value = btn.dataset.prix || 0;
            document.getElementById('edit_cycles').value = btn.dataset.cycles || 0;
            
            const desc = btn.dataset.description;
            document.getElementById('edit_description').value = (desc === 'null' || !desc) ? '' : desc;

            // Mise à jour dynamique de l'action du formulaire avec l'ID
            editForm.action = `/admin/categories/${btn.dataset.id}`;
        }
    });

    const handleAjaxForm = (formId) => {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            clearErrors(form); // On enlève les messages rouges

            const formData = new FormData(this);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';

            fetch(this.action, {
                method: 'POST', // Le @method('PUT') dans le HTML est inclus dans formData
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                // On vérifie si la réponse est du JSON (évite l'erreur SyntaxError <!)
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    const data = await response.json();

                    if (response.status === 422) {
                        displayErrors(form, data.errors);
                    } else if (response.ok) {
                        window.location.reload(); // Succès : rafraîchissement
                    }
                } else {
                    // Erreur serveur (HTML renvoyé au lieu de JSON)
                    console.error("Le serveur a renvoyé du HTML. Vérifiez vos routes ou le contrôleur.");
                    alert("Une erreur serveur est survenue. Vérifiez la console.");
                }
            })
            .catch(error => {
                console.error('Erreur technique:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    };


    // --- 3. FONCTIONS UTILITAIRES (ERREURS) ---

    // Affiche les messages d'erreurs Laravel sous les bons inputs
    const displayErrors = (form, errors) => {
        Object.keys(errors).forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block';
                errorDiv.innerText = errors[fieldName][0];
                input.after(errorDiv);
            }
        });
    };

    // Nettoie les styles et messages rouges
    const clearErrors = (form) => {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    };
    // --- 4. INITIALISATION ---
    handleAjaxForm('addCategoryForm');
    handleAjaxForm('editForm');

    // Reset complet lors de la fermeture manuelle des modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                clearErrors(form);
            }
        });
    });
    // --- GESTION DE LA SUPPRESSION ---
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-btn');
        if (btn) {
            const deleteForm = document.getElementById('deleteForm');
            const deleteName = document.getElementById('delete_category_name');
            
            // On remplit le nom dans le modal pour confirmer à l'utilisateur
            deleteName.innerText = btn.dataset.libelle;
            
            // On met à jour l'URL d'action du formulaire de suppression
            deleteForm.action = `/admin/categories/${btn.dataset.id}`;
        }
    });

// On initialise l'AJAX pour le formulaire de suppression
    handleAjaxForm('deleteForm');
    });

</script>
@endsection