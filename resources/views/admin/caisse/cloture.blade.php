@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Clôture de session de caisse</h5>
                </div>
                <div class="card-body">
                    <!-- Résumé Théorique -->
                    <div class="row mb-4 text-center">
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block">Fond initial</span>
                                <h4>{{ number_format($summary['fond_initial'], 0, ',', ' ') }} FCFA</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block">Total entrées (+)</span>
                                <h4 class="text-success">+ {{ number_format($summary['total_entrees'], 0, ',', ' ') }} FCFA</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block">Total sorties (-)</span>
                                <h4 class="text-danger">- {{ number_format($summary['total_sorties'], 0, ',', ' ') }} FCFA</h4>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info text-center">
                        <strong>Solde théorique attendu :</strong>
                        <span class="fs-4 fw-bold">{{ number_format($summary['solde_theorique'], 0, ',', ' ') }} FCFA</span>
                        <div class="small">Calculé sur les espèces de cette session : fonds initial + entrées − sorties.</div>
                    </div>
                    @if ($summary['total_entrees_hors_especes'] > 0 || $summary['total_sorties_hors_especes'] > 0)
                        <div class="alert alert-secondary">
                            <strong>Opérations hors espèces, exclues du comptage du tiroir :</strong>
                            Entrées : {{ number_format($summary['total_entrees_hors_especes'], 0, ',', ' ') }} FCFA ·
                            Sorties : {{ number_format($summary['total_sorties_hors_especes'], 0, ',', ' ') }} FCFA
                        </div>
                    @endif

                    <!-- Formulaire de Clôture -->
                    <form id="formCloture" action="{{ route('admin.caisse.cloture.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="solde_physique" class="form-label">Comptage Physique Réel (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" name="solde_physique" id="solde_physique" class="form-control form-control-lg" value="{{ old('solde_physique') }}" required>
                            <small class="text-muted">Comptez uniquement les billets et pièces présents dans le tiroir.</small>
                            @error('solde_physique')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="commentaire" class="form-label">Commentaire / Justification d'écart</label>
                            <textarea name="commentaire" id="commentaire" class="form-control" rows="3" placeholder="Obligatoire en cas d'écart constaté...">{{ old('commentaire') }}</textarea>
                            @error('commentaire')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="button" id="btnCloturer" class="btn btn-danger btn-lg">
                                <i class="fas fa-lock"></i> Valider et clôturer la caisse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const soldeTheorique = {{ $summary['solde_theorique'] }};

    document.getElementById('btnCloturer').addEventListener('click', function() {
        const soldePhysiqueInput = document.getElementById('solde_physique').value;
        const commentaire = document.getElementById('commentaire').value.trim();

        if (!soldePhysiqueInput) {
            Swal.fire('Erreur', 'Veuillez saisir le solde physique compté.', 'error');
            return;
        }

        const soldePhysique = parseFloat(soldePhysiqueInput);
        const ecart = soldePhysique - soldeTheorique;

        if (Math.abs(ecart) >= 0.005 && !commentaire) {
            Swal.fire('Justification requise', 'Veuillez expliquer l’écart avant de clôturer la caisse.', 'warning');
            return;
        }

        let iconType = 'warning';
        let messageText = `Solde théorique : ${soldeTheorique.toLocaleString()} FCFA<br>Solde physique : ${soldePhysique.toLocaleString()} FCFA<br>`;

        if (Math.abs(ecart) < 0.005) {
            iconType = 'success';
            messageText += '<b class="text-success">Aucun écart constaté. Caisse parfaite !</b>';
        } else if (ecart < 0) {
            iconType = 'error';
            messageText += `<b class="text-danger">Attention : Manquant de ${Math.abs(ecart).toLocaleString()} FCFA !</b>`;
        } else {
            iconType = 'info';
            messageText += `<b class="text-info">Excédent de ${ecart.toLocaleString()} FCFA.</b>`;
        }

        Swal.fire({
            title: 'Confirmer la clôture ?',
            html: messageText,
            icon: iconType,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, clôturer définitivement',
            cancelButtonText: 'Vérifier à nouveau'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formCloture').submit();
            }
        });
    });
</script>
@endpush
