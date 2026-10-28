@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-unlock-alt"></i> Ouverture de Session de Caisse</h5>
                </div>
                <div class="card-body">
                    <form id="formOuverture" action="{{ route('admin.caisse.ouvrir.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="fond_caisse_initial" class="form-label">Fond de Caisse Initial (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" step="1" name="fond_caisse_initial" id="fond_caisse_initial" class="form-control form-control-lg" value="{{ $dernierSolde ?? 0 }}" required>
                            <small class="text-muted">Montant physique disponible dans le tiroir au démarrage de la journée.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle"></i> Ouvrir ma Caisse
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
    document.getElementById('formOuverture').addEventListener('submit', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Confirmer l\'ouverture ?',
            text: "Voulez-vous vraiment ouvrir votre session de caisse avec ce montant ?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, ouvrir',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
</script>
@endpush
