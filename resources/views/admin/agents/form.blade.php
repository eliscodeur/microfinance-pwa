@extends('admin.layouts.app')

@section('content')

<h2>{{ isset($agent) ? 'Modifier Agent' : 'Ajouter Agent' }}</h2>

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ isset($agent) ? route('admin.agents.update', $agent->id) : route('admin.agents.store') }}" enctype="multipart/form-data">
@csrf
@if(isset($agent))
@method('PUT')
@endif

<div class="mb-3">
    <label>Nom</label>
    <input type="text" name="nom" class="form-control" value="{{ isset($agent) ? $agent->nom : old('nom') }}" required>
</div>
<div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" value="{{ isset($agent) ? $agent->user->email : old('email') }}" required>
</div>
<div class="mb-3">
    <label>Téléphone</label>
    <input type="text" name="telephone" class="form-control" value="{{ isset($agent) ? $agent->telephone : old('telephone') }}" required>
</div>

<!-- <div class="mb-3">
    <label for="image" class="form-label">Image de l'Agent</label>
    <div class="input-group">
        <input type="file" name="image" id="image" class="form-control" accept="image/*" onchange="previewImage(event)">
        <label class="input-group-text" for="image">
            <i class="bi bi-upload"></i> Choisir une image
        </label>
    </div>
    <div id="image-preview" class="mt-3">
        @if(isset($agent) && $agent->image)
            <img src="{{ asset('storage/' . $agent->image) }}" alt="Image actuelle" class="img-thumbnail" style="max-width: 200px;">
        @else
            <img id="preview" src="#" alt="Prévisualisation" class="img-thumbnail d-none" style="max-width: 200px;">
        @endif
    </div>
</div> -->

<div class="image-upload-wrapper border-dashed text-center p-2 position-relative" 
        style="border: 2px dashed #ddd; border-radius: 15px; background: #f9f9f9; transition: 0.3s; min-height: 100px;">
    
    <div id="imagePreviewContainer" 
            class="position-relative d-inline-block {{ isset($agent) && $agent->image ? '' : 'd-none' }}"
            style="cursor: pointer;"
            onclick="document.getElementById('photoInput').click()">
        <img src="{{ (isset($agent) && $agent->image) ? asset('storage/' . $agent->image) : '#' }}" 
                id="imagePreview"
                alt="Aperçu" 
                class="img-thumbnail shadow-sm" 
                style="max-height: 150px; border-radius: 10px;">
        
        <button type="button" 
                class="btn-close position-absolute bg-white shadow-sm rounded-circle p-2" 
                style="top: -10px; right: -10px; font-size: 0.7rem; z-index: 10;" 
                aria-label="Supprimer"
                onclick="removeImage(event)"></button>
    </div>

    <div id="uploadPlaceholder" 
            class="p-3 {{ isset($agent) && $agent->image ? 'd-none' : '' }}"
            onclick="document.getElementById('photoInput').click()"
            style="cursor: pointer;">
        <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2.5rem;"></i>
        <p class="text-muted small mb-0">Cliquez pour ajouter une photo</p>
    </div>

    <input type="file" name="image" id="photoInput" class="d-none" accept="image/*" onchange="previewImage(event)">
    <!-- <input type="file" name="image" id="image" class="form-control" accept="image/*" onchange="previewImage(event)"> -->
    
    <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">
</div>
@if(!isset($agent))
<div class="mb-3">
    <label>Mot de passe</label>
    <input type="password" name="password" class="form-control" required>
</div>
@endif

<!-- <button class="btn btn-success">{{ isset($agent) ? 'Modifier' : 'Enregistrer' }}</button> -->
<div class="d-flex justify-content-end mt-2">
    <button type="submit" class="btn btn-success px-4">
        {{ isset($agent) ? 'Modifier l\'agent' : 'Enregistrer l\'agent' }}
    </button>

    @if(isset($agent))
        <a href="{{ route('admin.agents.index') }}" class="btn btn-outline-secondary ms-2 px-4">
            Annuler
        </a>
    @endif
</div>
</form>

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
                placeholder.classList.add("d-none");   // Cache le placeholder
                removeInput.value = "0";             // On ne supprime pas l'image existante
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
        container.classList.add("d-none");     // Cache l'image et la croix
        placeholder.classList.remove("d-none"); // Re-montre le placeholder
        
        // 4. Signale au serveur de supprimer l'image existante si on est en édition
        removeInput.value = "1";
    }
</script>

@endsection