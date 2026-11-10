{{--
  $permissionGroups : catalogue hiérarchique des permissions
  $roleToEdit : modèle Role|null
--}}
@php
    $saved = old('permissions', $roleToEdit ? ($roleToEdit->permissions ?? []) : []);
    $normalizedSaved = \App\Support\PermissionCatalog::expand($saved);
@endphp
<div class="permission-scroll" style="max-height: 520px; overflow-y: auto;">
    @foreach($permissionGroups as $groupKey => $group)
        <fieldset class="border rounded p-3 mb-3 bg-light">
            <legend class="float-none w-auto px-2 fs-6 mb-2 fw-bold">{{ $group['label'] }}</legend>
            <div class="row g-3">
                @foreach($group['modules'] as $moduleKey => $module)
                    <div class="col-12 col-md-6">
                        <div class="border rounded bg-white h-100 p-3">
                            <h4 class="h6 fw-semibold mb-2">{{ $module['label'] }}</h4>
                            @foreach($module['permissions'] as $permission => $label)
                                @php($permissionId = 'perm-' . $groupKey . '-' . $moduleKey . '-' . $loop->index)
                                <div class="form-check">
                                    <input class="form-check-input @error('permissions') is-invalid @enderror @error('permissions.*') is-invalid @enderror"
                                           type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permission }}"
                                           id="{{ $permissionId }}"
                                           @checked(in_array($permission, $normalizedSaved, true))>
                                    <label class="form-check-label small" for="{{ $permissionId }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </fieldset>
    @endforeach
    @error('permissions')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('permissions.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
