@extends('admin.layouts.sidebar')

@section('content')
@php
    $legacyPermissionMap = ['Supprimer Données' => 'Supprimer données'];
@endphp

<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Rôles et permissions</h2>
            <p class="text-muted small mb-0">Créez des profils d’accès et attribuez les droits aux administrateurs.</p>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        {{-- Formulaire création / édition --}}
        <div class="col-12 col-xl-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="h6 fw-bold mb-0 text-primary">
                        <i class="bi bi-{{ $roleToEdit ? 'pencil-square' : 'plus-circle' }} me-2"></i>
                        {{ $roleToEdit ? 'Modifier le rôle' : 'Nouveau rôle' }}
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ $roleToEdit ? route('admin.roles.update', $roleToEdit->id) : route('admin.roles.store') }}" method="post" novalidate>
                        @csrf
                        @if($roleToEdit)
                            @method('PATCH')
                        @endif

                        <div class="mb-3">
                            <label for="role-nom" class="form-label small fw-bold">Nom du rôle</label>
                            <input type="text"
                                   name="nom"
                                   id="role-nom"
                                   class="form-control @error('nom') is-invalid @enderror"
                                   value="{{ old('nom', $roleToEdit->nom ?? '') }}"
                                   placeholder="Ex. Superviseur"
                                   required
                                   maxlength="255"
                                   autocomplete="off">
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @include('admin.roles.partials.permission-checkboxes', [
                            'permissionLabels' => $permissionLabels,
                            'roleToEdit' => $roleToEdit,
                            'legacyPermissionMap' => $legacyPermissionMap,
                        ])

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-{{ $roleToEdit ? 'success' : 'primary' }}">
                                {{ $roleToEdit ? 'Enregistrer les modifications' : 'Créer le rôle' }}
                            </button>
                            @if($roleToEdit)
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">
                                    Annuler la modification
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Liste --}}
        <div class="col-12 col-xl-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="h6 fw-bold mb-0 text-dark">
                        <i class="bi bi-shield-lock me-2"></i>Rôles enregistrés
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" scope="col">Nom</th>
                                    <th scope="col">Permissions</th>
                                    <th class="text-end pe-4" style="width: 8rem;" scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark">
                                            <i class="bi bi-shield-lock-fill text-primary me-2" aria-hidden="true"></i>{{ $role->nom }}
                                        </td>
                                        <td>
                                            @if(!empty($role->permissions))
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($role->permissions as $permission)
                                                        <span class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle small">
                                                            {{ $permission }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted small fst-italic">Aucune permission</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex justify-content-end gap-1">
                                                @can('Modifier données')
                                                    <a href="{{ route('admin.roles.index', ['id' => $role->id]) }}"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                @endcan
                                                @can('Supprimer données')
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Supprimer"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteRoleModal"
                                                            data-delete-url="{{ route('admin.roles.destroy', $role->id) }}"
                                                            data-role-name="{{ $role->nom }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Aucun rôle configuré pour le moment.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal suppression --}}
<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRoleModalLabel">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-1">Supprimer le rôle suivant&nbsp;?</p>
                <p id="roleNameDisplay" class="fw-bold text-danger mb-0"></p>
                <p class="text-muted small mt-3 mb-0">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteRoleForm" method="post" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('deleteRoleModal');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            var url = btn.getAttribute('data-delete-url');
            var name = btn.getAttribute('data-role-name') || '';
            var form = document.getElementById('deleteRoleForm');
            var label = document.getElementById('roleNameDisplay');
            if (form && url) form.action = url;
            if (label) label.textContent = name;
        });
    });
</script>
@endsection
