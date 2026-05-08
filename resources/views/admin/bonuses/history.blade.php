@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Historique des Paiements</h2>
        <a href="{{ route('admin.bonuses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour aux attentes
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Référence</th>
                            <th>Date & Heure</th>
                            <th>Agent</th>
                            <th class="text-center">Éléments payés</th>
                            <th class="text-end">Montant Total</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paiements as $p)
                        <tr>
                            <td class="ps-4">
                                <code class="fw-bold text-primary">{{ $p->reference }}</code>
                            </td>
                            <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="fw-bold">{{ $p->agent->nom }}</div>
                                <small class="text-muted">{{ $p->agent->code_agent }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">
                                    {{ $p->bonuses->count() }} ligne(s)
                                </span>
                            </td>
                            <td class="text-end fw-bold fs-5 text-dark">
                                {{ number_format($p->montant_total, 0, ',', ' ') }} F
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border" title="Voir le reçu">
                                    <i class="bi bi-file-earmark-pdf text-danger"></i> Reçu
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Aucun paiement effectué pour le moment.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paiements->hasPages())
        <div class="card-footer bg-white">
            {{ $paiements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection