@extends('admin.layouts.sidebar')

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

<div class="mb-3">
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
</div>

@if(!isset($agent))
<div class="mb-3">
    <label>Mot de passe</label>
    <input type="password" name="password" class="form-control" required>
</div>
@endif

<button class="btn btn-success">{{ isset($agent) ? 'Modifier' : 'Enregistrer' }}</button>

</form>

<script>
    function previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('preview');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
        }
    }
</script>

@endsection