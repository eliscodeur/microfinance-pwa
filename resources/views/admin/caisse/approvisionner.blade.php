@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-hand-holding-usd"></i> Approvisionner la caisse</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert {{ $summary['solde_theorique'] < 0 ? 'alert-danger' : 'alert-info' }}">
                            <div>Solde espèces théorique actuel</div>
                            <strong class="fs-4">{{ number_format($summary['solde_theorique'], 0, ',', ' ') }} FCFA</strong>
                            <div class="small">L’approvisionnement est une entrée distincte : il ne modifie pas le fonds initial d’ouverture.</div>
                        </div>

                        <form action="{{ route('admin.caisse.approvisionner.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="montant" class="form-label">Montant reçu en espèces (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" min="1" step="1" name="montant" id="montant" class="form-control form-control-lg" value="{{ old('montant') }}" required>
                                @error('montant')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="origine" class="form-label">Origine des fonds <span class="text-danger">*</span></label>
                                <input type="text" maxlength="120" name="origine" id="origine" class="form-control" value="{{ old('origine') }}" placeholder="Ex. coffre principal, banque, transfert d’une autre caisse" required>
                                @error('origine')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="motif" class="form-label">Motif de l’approvisionnement <span class="text-danger">*</span></label>
                                <input type="text" maxlength="255" name="motif" id="motif" class="form-control" value="{{ old('motif') }}" placeholder="Ex. réapprovisionnement pour les opérations du jour" required>
                                @error('motif')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="reference" class="form-label">Référence du justificatif</label>
                                <input type="text" maxlength="100" name="reference" id="reference" class="form-control" value="{{ old('reference') }}" placeholder="N° de bordereau, reçu ou transfert">
                                @error('reference')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('admin.caisse.journal') }}" class="btn btn-outline-secondary">Retour au journal</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-1"></i> Enregistrer l’approvisionnement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
