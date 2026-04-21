@extends('admin.layouts.sidebar')

@section('content')
<h2>NANA CONSULTING</h2>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <h5>Total Agents</h5>
            <h3 class="text-primary">{{ $totalAgents }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3 text-center">
            <h5>Total Clients</h5>
            <h3 class="text-success">{{ $totalClients }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3 text-center">
            <h5>Total Collecte</h5>
            <h3 class="text-warning">{{ $totalCollecte }} FCFA</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3 text-center">
            <h5>Synchros a valider</h5>
            <h3 class="text-danger">{{ $pendingSyncBatches }}</h3>
            <a href="{{ route('admin.sync-batches.index') }}" class="small text-decoration-none">Ouvrir la revue</a>
        </div>
    </div>
</div>
@endsection
