@extends('admin.layouts.app')

@section('content')
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h2 class="card-title mb-4">{{ isset($agent) ? 'Modifier un agent' : 'Ajouter un agent' }}</h2>


            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                action="{{ isset($agent) ? route('admin.agents.update', $agent->id) : route('admin.agents.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if (isset($agent))
                    @method('PUT')
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-12 col-lg-6">
                        <label for="nom" class="form-label fw-semibold">Nom et prénom de l'agent</label>
                        <input type="text" name="nom" id="nom" class="form-control py-2"
                            value="{{ isset($agent) ? $agent->nom : old('nom') }}" required>
                    </div>
                    <div class="col-md-12 col-lg-6">
                        <label for="email" class="form-label fw-semibold">Email de l'agent</label>
                        <input type="email" name="email" id="email" class="form-control py-2"
                            value="{{ isset($agent) ? $agent->user->email : old('email') }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-12 col-lg-6">
                        <label for="telephone" class="form-label fw-semibold">Numéro de téléphone</label>
                        <input type="text" name="telephone" id="telephone" class="form-control py-2"
                            value="{{ isset($agent) ? '+228 ' . implode(' ', str_split($agent->telephone, 2)) : old('telephone') }}"
                            required>
                    </div>
                    @if (!isset($agent))
                        <div class="col-md-12 col-lg-6">
                            <label for="password" class="form-label fw-semibold">Mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control py-2" required>
                        </div>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Photo de profil</label>
                    <div class="image-upload-wrapper border-dashed text-center p-3 position-relative"
                        style="border: 2px dashed #ced4da; border-radius: 12px; background: #f8f9fa; transition: 0.3s; min-height: 120px; display: flex; align-items: center; justify-content: center;">

                        <div id="imagePreviewContainer"
                            class="position-relative d-inline-block {{ isset($agent) && $agent->image ? '' : 'd-none' }}"
                            style="cursor: pointer;" onclick="document.getElementById('photoInput').click()">
                            <img src="{{ isset($agent) && $agent->image ? asset('storage/' . $agent->image) : '#' }}"
                                id="imagePreview" alt="Aperçu" class="img-thumbnail shadow-sm"
                                style="max-height: 140px; border-radius: 10px;">

                            <button type="button" class="btn-close position-absolute bg-white shadow-sm rounded-circle p-2"
                                style="top: -10px; right: -10px; font-size: 0.7rem; z-index: 10;" aria-label="Supprimer"
                                onclick="removeImage(event)"></button>
                        </div>

                        <div id="uploadPlaceholder" class="{{ isset($agent) && $agent->image ? 'd-none' : '' }}"
                            onclick="document.getElementById('photoInput').click()" style="cursor: pointer; width: 100%;">
                            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2.5rem;"></i>
                            <p class="text-muted small mb-0 mt-1">Cliquez pour ajouter une photo</p>
                        </div>

                        <input type="file" name="image" id="photoInput" class="d-none" accept="image/*"
                            onchange="previewImage(event)">

                        <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    @if (isset($agent))
                        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-secondary px-4 py-2">
                            Annuler
                        </a>
                    @endif
                    <button type="submit" class="btn btn-success px-4 py-2">
                        {{ isset($agent) ? 'Modifier l\'agent' : 'Enregistrer l\'agent' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('js/imask.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const phoneInput = document.getElementById('telephone');
            if (phoneInput) {
                const maskOptions = {
                    mask: '+228 00 00 00 00',
                    lazy: false,
                    placeholderChar: '_'
                };
                const mask = IMask(phoneInput, maskOptions);

                // Si une valeur existe déjà au chargement en mode edit
                if (phoneInput.value) {
                    mask.unmaskedValue = phoneInput.value.replace(/\D/g, '').replace(/^228/, '');
                }
            }
        });
    </script>
    <script>
        // 1. Fonction pour l'aperçu (mise à jour pour gérer le container)
        function previewImage(event) {
            const input = event.target;
            const reader = new FileReader();
            const container = document.getElementById("imagePreviewContainer");
            const preview = document.getElementById("imagePreview");
            const placeholder = document.getElementById("uploadPlaceholder");
            const removeInput = document.getElementById("removePhotoInput");

            reader.onload = function() {
                if (reader.readyState === 2) {
                    preview.src = reader.result;
                    container.classList.remove("d-none"); // Montre l'image + la croix
                    placeholder.classList.add("d-none"); // Cache le placeholder
                    removeInput.value = "0"; // On ne supprime pas l'image existante
                }
            }

            if (input.files[0]) {
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 2. NOUVELLE Fonction pour supprimer l'image
        function removeImage(event) {
            // Empêche le clic de se propager au container (ce qui ouvrirait l'explorateur de fichiers)
            event.stopPropagation();

            const input = document.getElementById("photoInput");
            const container = document.getElementById("imagePreviewContainer");
            const preview = document.getElementById("imagePreview");
            const placeholder = document.getElementById("uploadPlaceholder");
            const removeInput = document.getElementById("removePhotoInput");

            // 1. Réinitialise l'input file (pour qu'il n'envoie rien)
            input.value = "";

            // 2. Vide l'aperçu
            preview.src = "#";

            // 3. Bascule l'affichage
            container.classList.add("d-none"); // Cache l'image et la croix
            placeholder.classList.remove("d-none"); // Re-montre le placeholder

            // 4. Signale au serveur de supprimer l'image existante si on est en édition
            removeInput.value = "1";
        }
    </script>

@endsection
