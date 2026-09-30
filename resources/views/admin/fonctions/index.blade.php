@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Postes & Fonctions Administratives</h1>
                <p class="text-muted small mb-0">Définissez les fonctions et les salaires de base applicables dans
                    l'institution.</p>
            </div>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#fonctionModal"
                onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Nouvelle Fonction
            </button>
        </div>

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

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7 text-secondary">
                            <tr>
                                <th class="ps-4">Intitulé du Poste</th>
                                <th>Salaire de Base par Défaut</th>
                                <th>Description</th>
                                <th class="text-center">Effectif</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fonctions as $fonction)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $fonction->libelle }}</td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-2 py-1">
                                            {{ number_format($fonction->salaire_base_defaut, 0, ',', ' ') }} F CFA
                                        </span>
                                    </td>
                                    <td class="text-muted small text-truncate" style="max-width: 250px;">
                                        {{ $fonction->description ?? 'Aucune description' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">
                                            {{ $fonction->employes_count }} agent(s)
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-light text-primary me-1"
                                            onclick="editFonction({{ json_encode($fonction) }})" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.fonctions.destroy', $fonction->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette fonction ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger"
                                                title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucune fonction enregistrée pour
                                        le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale Ajout / Modification -->
    <div class="modal fade" id="fonctionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="fonctionForm" action="{{ route('admin.fonctions.store') }}" method="POST">
                    @csrf
                    <div id="methodField"></div>
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="modalTitle">Nouvelle Fonction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Intitulé du poste <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="libelle" id="libelle" required
                                placeholder="Ex: Secrétaire, Comptable...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Salaire de Base par Défaut (F CFA) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="salaire_base_defaut" id="salaire_base_defaut"
                                required value="35000">
                            <div class="form-text small">Ex: 35000 pour la période d'essai standard.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description des tâches</label>
                            <textarea class="form-control" name="description" id="description" rows="3"
                                placeholder="Rôle et responsabilités principales..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function resetForm() {
            document.getElementById('modalTitle').innerText = 'Nouvelle Fonction';
            document.getElementById('fonctionForm').action = "{{ route('admin.fonctions.store') }}";
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('libelle').value = '';
            document.getElementById('salaire_base_defaut').value = '35000';
            document.getElementById('description').value = '';
        }

        function editFonction(fonction) {
            document.getElementById('modalTitle').innerText = 'Modifier la Fonction';
            document.getElementById('fonctionForm').action = "/admin/fonctions/" + fonction.id;
            document.getElementById('methodField').innerHTML = '@method('PUT')';
            document.getElementById('libelle').value = fonction.libelle;
            document.getElementById('salaire_base_defaut').value = fonction.salaire_base_defaut;
            document.getElementById('description').value = fonction.description || '';

            var myModal = new bootstrap.Modal(document.getElementById('fonctionModal'));
            myModal.show();
        }
    </script>
@endsection
