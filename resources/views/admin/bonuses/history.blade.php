@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Historique des Paiements</h2>
            <p class="text-muted mb-0 small">Suivi des bonus et commissions décaissés</p>
        </div>
        <a href="{{ route('admin.bonuses.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour aux attentes
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small fw-bold">
                            <th class="ps-4 py-3">Référence & Type</th>
                            <th class="py-3">Date & Heure</th>
                            <th class="py-3">Agent</th>
                            <th class="text-center py-3">Éléments payés</th>
                            <th class="text-end py-3">Montant Total</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paiements as $p)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">
                                    {{ $p->reference }}
                                </span>
                                <div class="small text-muted">
                                    <i class="bi bi-tag-fill me-1"></i>{{ ucfirst($p->type) }}
                                </div>
                            </td>
                            <td class="text-secondary small">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $p->agent->nom }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $p->agent->code_agent }}</div>
                            </td>
                            <td class="text-center">
                                <!-- Déclencheur SweetAlert via le Badge -->
                                <button type="button" 
                                        class="btn btn-link p-0 text-decoration-none btn-show-details"
                                        data-ref="{{ $p->reference }}"
                                        data-agent="{{ $p->agent->nom }}"
                                        data-type="{{ $p->type }}"
                                        data-items='@json($p->bonuses)'>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i> {{ $p->bonuses->count() }} ligne(s)
                                    </span>
                                </button>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                {{ number_format($p->montant_total, 0, ',', ' ') }} F
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-danger" title="Télécharger le reçu">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <!-- Déclencheur SweetAlert via le bouton Info -->
                                    <button class="btn btn-sm btn-outline-primary btn-show-details" 
                                            data-ref="{{ $p->reference }}" 
                                            data-agent="{{ $p->agent->nom }}" 
                                            data-type="{{ $p->type }}"
                                            data-items='@json($p->bonuses)'>
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-inbox text-muted fs-1 d-block mb-3"></i>
                                <span class="text-muted">Aucun paiement effectué pour le moment.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paiements->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $paiements->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    /**
     * GESTION UNIQUE DES DÉTAILS DE PAIEMENT
     */
    const detailButtons = document.querySelectorAll('.btn-show-details');

    detailButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const d = this.dataset;
            let items = [];
            
            try {
                items = JSON.parse(d.items);
            } catch (error) {
                console.error("Erreur de parsing JSON:", error);
                return;
            }

            // Construction des lignes du tableau
            let tableRows = items.map(item => {
                const dateAttrib = item.date_attribution ? new Date(item.date_attribution).toLocaleDateString('fr-FR') : '-';
                const montant = parseInt(item.montant).toLocaleString('fr-FR');
                const badgeType = item.cycle_id 
                    ? '<span class="badge bg-info-subtle text-info border border-info-subtle small">Auto</span>'
                    : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle small">Manuel</span>';
                
                // Logique dynamique pour le statut
                // On vérifie le type passé dans les dataset du bouton (d.type)
                const isRejet = d.type.toLowerCase() === 'rejet';
                const statusBadge = isRejet 
                    ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Rejeté</span>'
                    : '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Payé</span>';

                return `
                    <tr class="align-middle">
                        <td class="text-start small text-secondary py-2">${dateAttrib}</td>
                        <td class="text-start py-2">
                            <div class="fw-bold" style="font-size: 0.85rem;">${item.motif || 'Bonus sans motif'}</div>
                            ${badgeType}
                        </td>
                        <td class="text-center">
                            ${statusBadge}
                        </td>
                        <td class="text-end fw-bold text-dark py-2">${montant} F</td>
                    </tr>`;
            }).join('');

            // Affichage SweetAlert
            Swal.fire({
                title: `<div class="fs-6 text-muted mb-1 text-uppercase">Référence de Paiement</div><div class="fw-bold text-primary">${d.ref}</div>`,
                width: '800px',
                html: `
                    <div class="d-flex align-items-center p-3 bg-light border rounded-3 mb-4 text-start">
                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 50px; height: 50px;">
                            <i class="bi bi-person-check fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0 text-muted small">Agent bénéficiaire</p>
                            <h6 class="mb-0 fw-bold">${d.agent}</h6>
                        </div>
                        <div class="text-end">
                            <p class="mb-0 text-muted small">Type d'opération</p>
                            <span class="badge bg-dark rounded-pill">${d.type.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-hover border-top">
                            <thead class="table-light">
                                <tr style="font-size: 0.75rem;" class="text-uppercase">
                                    <th class="text-start py-2 ps-3">Date Attribution</th>
                                    <th class="text-start py-2">Motif & Source</th>
                                    <th class="text-center py-2">État</th>
                                    <th class="text-end py-2 pe-3">Montant</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.9rem;">
                                ${tableRows}
                            </tbody>
                        </table>
                    </div>`,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    container: 'my-swal-container',
                    popup: 'rounded-4 shadow-lg',
                    title: 'pt-4 border-bottom pb-3'
                }
            });
        });
    });
});
</script>

<style>
    /* Optionnel : Ajustements visuels pour SweetAlert */
    .swal2-html-container { margin: 1.5rem 1rem !important; }
    .table-responsive::-webkit-scrollbar { width: 6px; }
    .table-responsive::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 10px; }
</style>
@endsection