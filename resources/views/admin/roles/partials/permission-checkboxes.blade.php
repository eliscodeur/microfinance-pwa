{{--
  $permissionLabels : liste depuis config('role_permissions.labels')
  $roleToEdit : modèle Role|null
  $legacyPermissionMap : optionnel, ex. ['Supprimer Données' => 'Supprimer données']
--}}
@php
    $legacyPermissionMap = $legacyPermissionMap ?? ['Supprimer Données' => 'Supprimer données'];
    $saved = old('permissions', $roleToEdit ? ($roleToEdit->permissions ?? []) : []);
    $normalizedSaved = collect($saved)->map(fn ($p) => $legacyPermissionMap[$p] ?? $p)->all();
@endphp
<fieldset class="border rounded p-3 mb-3 bg-light">
    <legend class="float-none w-auto px-1 fs-6 mb-2 fw-bold">Permissions</legend>
    <div class="row g-2 permission-scroll" style="max-height: 220px; overflow-y: auto;">
        @foreach($permissionLabels as $perm)
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="form-check">
                    <input class="form-check-input @error('permissions') is-invalid @enderror @error('permissions.*') is-invalid @enderror"
                           type="checkbox"
                           name="permissions[]"
                           value="{{ $perm }}"
                           id="perm-{{ $loop->index }}"
                           @checked(in_array($perm, $normalizedSaved, true))>
                    <label class="form-check-label small" for="perm-{{ $loop->index }}">{{ $perm }}</label>
                </div>
            </div>
        @endforeach
    </div>
    @error('permissions')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('permissions.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</fieldset>
