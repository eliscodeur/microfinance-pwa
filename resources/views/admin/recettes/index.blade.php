@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <!-- KPI / Cartes de Synthèse Financière -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <!-- TITRE DYNAMIQUE DE LA PERIODE -->
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                    Total Recettes <span class="text-muted fw-normal lowercase"
                                        style="font-size: 0.75rem;">({{ $periodeLibelle }})</span>
                                </div>
                                <div class="h5 mb-0 fw-bold text-dark">
                                    {{ number_format($totalMois, 0, ',', ' ') }} <small class="text-muted">FCFA</small>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-cash-stack fa-2x text-success fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Nombre d'Opérations</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $nombreOperationsMois }} encaissement(s)</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-receipt-cutoff fa-2x text-primary fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card border-0 shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Traçabilité & Intégrité</div>
                                <div class="h5 mb-0 fw-bold text-dark">Synchro Caisse OK</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-arrow-repeat fa-2x text-info fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de Filtres -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light rounded">
                <form method="GET" action="{{ route('admin.recettes.index') }}" class="row g-3 align-items-end">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="type_recette" class="form-label fw-bold">Filtrer par type de recette</label>
                            <select name="type_recette" id="type_recette" class="form-select py-2">
                                <option value="">Tous les types</option>
                                @foreach ($typesRecettes as $type)
                                    <option value="{{ $type }}"
                                        {{ request('type_recette') == $type ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="periode" class="form-label fw-bold">Période (Du - Au)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white px-3"><i class="bi bi-calendar-range"></i></span>
                                <input type="text" name="periode" id="periode"
                                    class="form-control py-2 date-range-picker" placeholder="Sélectionner une période"
                                    value="{{ request('periode') }}">
                            </div>
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                                <i class="bi bi-filter me-1"></i> Filtrer
                            </button>
                            <a href="{{ route('admin.recettes.index') }}" class="btn btn-outline-secondary py-2">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau Principal des Recettes -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success">Journal des Entrées & Recettes</h6>
            </div>
            <div class="card-body px-0 pb-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Type de Recette</th>
                                <th>Client / Acteur</th>
                                <th>Commentaire & Référence</th>
                                <th>Mode</th>
                                <th class="text-end">Montant (FCFA)</th>
                                <th class="text-center">Auteur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recettes as $recette)
                                <tr>
                                    <td class="ps-4 fw-semibold text-secondary">
                                        {{ $recette->date_recette ? \Carbon\Carbon::parse($recette->date_recette)->format('d/m/Y') : $recette->created_at->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-success text-success border bg-light">
                                            {{ ucfirst(str_replace('_', ' ', $recette->type_recette)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $recette->client->nom ?? 'N/A' }}
                                            {{ $recette->client->prenom ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-dark">
                                            {{ Str::limit($recette->commentaire ?? $recette->motif, 35) }}</div>
                                        @if ($recette->reference)
                                            <small class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>Réf:
                                                {{ $recette->reference }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $recette->mode_paiement ?? 'espèces' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success fs-6">
                                            + {{ number_format($recette->montant, 0, ',', ' ') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-muted border"
                                            title="{{ $recette->user->name ?? 'Système' }}">
                                            {{ Str::limit($recette->user->name ?? 'Système', 10) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <div class="mb-2"><i class="bi bi-inbox fs-1 text-muted"></i></div>
                                        <p class="mb-0">Aucune recette enregistrée pour le moment.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-end">
                {{ $recettes->links() }}
            </div>
        </div>
    </div>

    <script src="{{ asset('js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/flatpickr-fr.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr(".date-range-picker", {
                mode: "range",
                locale: "fr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                conjunction: " au "
            });
        });
    </script>
@endsection
