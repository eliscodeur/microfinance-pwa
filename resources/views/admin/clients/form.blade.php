@extends('admin.layouts.sidebar')

@section('content')

<h2>{{ isset($client) ? 'Modifier Client' : 'Ajouter Client' }}</h2>

<form method="POST" action="{{ isset($client) ? route('admin.clients.update', $client->id) : route('admin.clients.store') }}">
@csrf
@if(isset($client))
@method('PUT')
@endif

<div class="mb-3">
    <label>Nom</label>
    <input type="text" name="nom" class="form-control" value="{{ isset($client) ? $client->nom : old('nom') }}" required>
</div>
<div class="mb-3">
    <label>Téléphone</label>
    <input type="text" name="telephone" class="form-control" value="{{ isset($client) ? $client->telephone : old('telephone') }}" required>
</div>
<div class="mb-3">
    <label>Adresse</label>
    <input type="text" name="adresse" class="form-control" value="{{ isset($client) ? $client->adresse : old('adresse') }}">
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

<button class="btn btn-success">{{ isset($client) ? 'Modifier' : 'Enregistrer' }}</button>

</form>

@endsection