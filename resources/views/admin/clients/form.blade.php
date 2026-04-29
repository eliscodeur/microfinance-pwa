@extends('admin.layouts.sidebar')

@section('content')
<style>
    .image-upload-wrapper:hover {
        border-color: #0d6efd !important;
        background-color: #f1f7ff !important;
    }
    .border-dashed {
        border-style: dashed !important;
    }
</style>
<h2>{{ isset($client) ? 'Modifier Client' : 'Ajouter Client' }}</h2>

<form method="POST" action="{{ isset($client) ? route('admin.clients.update', $client->id) : route('admin.clients.store') }}" enctype="multipart/form-data" class="mt-4">
@csrf
@if(isset($client))
@method('PUT')
@endif
<div class="row mb-3">
    <div class="col-md-6">
        <label>Nom</label>
        <input type="text" name="nom" class="form-control" value="{{ isset($client) ? $client->nom : old('nom') }}" required>
    </div>
    <div class="col-md-6 ">
        <label>Prénom</label>
        <input type="text" name="prenom" class="form-control" value="{{ isset($client) ? $client->prenom : old('prenom') }}" required>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label>Date de naissance</label>
        <input type="date" name="date_naissance" class="form-control" value="{{ isset($client) ? $client->date_naissance : old('date_naissance') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label>Lieu de naissance</label>
        <input type="text" name="lieu_naissance" class="form-control" value="{{ isset($client) ? $client->lieu_naissance : old('lieu_naissance') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label>Genre</label>
        <select name="genre" class="form-control">
            <option value="">Sélectionner un genre</option>
            <option value="masculin" {{ (isset($client) && $client->genre == 'masculin') ? 'selected' : '' }}>Masculin</option>
            <option value="féminin" {{ (isset($client) && $client->genre == 'féminin') ? 'selected' : '' }}>Féminin</option>
            <option value="autre" {{ (isset($client) && $client->genre == 'autre') ? 'selected' : '' }}>Autre</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label>Statut matrimonial</label>
        <select name="statut_matrimonial" class="form-control">
            <option value="">Sélectionner un statut</option>
            <option value="célibataire" {{ (isset($client) && $client->statut_matrimonial == 'célibataire') ? 'selected' : '' }}>Célibataire</option>
            <option value="marié(e)" {{ (isset($client) && $client->statut_matrimonial == 'marié(e)') ? 'selected' : '' }}>Marié(e)</option>
            <option value="divorcé(e)" {{ (isset($client) && $client->statut_matrimonial == 'divorcé(e)') ? 'selected' : '' }}>Divorcé(e)</option>
            <option value="veuf(ve)" {{ (isset($client) && $client->statut_matrimonial == 'veuf(ve)') ? 'selected' : '' }}>Veuf(ve)</option>
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label>Nationalité</label>
        <input type="text" name="nationalite" class="form-control" value="{{ isset($client) ? $client->nationalite : old('nationalite') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label>Profession</label>
        <input type="text" name="profession" class="form-control" value="{{ isset($client) ? $client->profession : old('profession') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>Téléphone</label>
        <input type="text" name="telephone" class="form-control" value="{{ isset($client) ? $client->telephone : old('telephone') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label>Adresse du lieu d'activité</label>
        <input type="text" name="adresse" class="form-control" value="{{ isset($client) ? $client->adresse : old('adresse') }}">
    </div>
</div>
<div class="mb-4">
    <label class="form-label fw-bold">Photo du client</label>
    
    <div class="image-upload-wrapper border-dashed text-center p-2 position-relative" 
         style="border: 2px dashed #ddd; border-radius: 15px; background: #f9f9f9; transition: 0.3s; min-height: 100px;">
        
        <div id="imagePreviewContainer" 
             class="position-relative d-inline-block {{ isset($client) && $client->photo ? '' : 'd-none' }}"
             style="cursor: pointer;"
             onclick="document.getElementById('photoInput').click()">
            <img src="{{ (isset($client) && $client->photo) ? asset('storage/' . $client->photo) : '#' }}" 
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
             class="p-3 {{ isset($client) && $client->photo ? 'd-none' : '' }}"
             onclick="document.getElementById('photoInput').click()"
             style="cursor: pointer;">
            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2.5rem;"></i>
            <p class="text-muted small mb-0">Cliquez pour ajouter une photo</p>
        </div>

        <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*" onchange="previewImage(event)">
        
        <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">
    </div>
</div>
<div class="p-3 mb-3 bg-light" style="border-radius: 10px; border: 1px solid #ddd;">
    <h6 class="text-muted mb-3"><i class="bi bi-person-badge me-1"></i> Personne de référence (Urgence)</h6>
    <div class="mb-3">
        <label>Nom complet du référent</label>
        <input type="text" name="reference_nom" class="form-control" value="{{ isset($client) ? $client->reference_nom : old('reference_nom') }}">
    </div>
    <div class="mb-2">
        <label>Téléphone du référent</label>
        <input type="text" name="reference_telephone" class="form-control" value="{{ isset($client) ? $client->reference_telephone : old('reference_telephone') }}">
    </div>
</div>

<div class="mb-3">
    <label>Agent</label>
    <select name="agent_id" class="form-control" required>
        <option value="">Sélectionner un agent</option>
        @foreach($agents as $agent)
        <option value="{{ $agent->id }}" {{ (isset($client) && $client->agent_id == $agent->id) ? 'selected' : '' }}>{{ $agent->nom }}</option>
        @endforeach
    </select>
</div>

<div class="d-flex justify-content-end">
    <button type="submit" class="btn btn-success px-4">
        {{ isset($client) ? 'Modifier le client' : 'Enregistrer le client' }}
    </button>

    @if(isset($client))
        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary ms-2 px-4">
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