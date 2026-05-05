<div class="modal fade" id="modalRetrait" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Nouveau Retrait</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.carnets.retrait') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="carnet_id" value="{{ $carnet->id }}">
                    <input type="hidden" name="client_id" value="{{ $carnet->client_id }}">

                    @if($carnet->type === 'tontine')
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Cycle à décaisser</label>
                            <select name="cycle_id" class="form-select" required>
                                <option value="">Sélectionner le cycle terminé</option>
                                @foreach($carnet->cycles->where('statut', 'termine')->whereNull('retire_at') as $cycle)
                                    <option value="{{ $cycle->id }}">Cycle #{{ $loop->iteration }} - Solde: {{ number_format($cycle->collectes->sum('montant'), 0) }} F</option>
                                @endforeach
                            </select>
                            <div class="form-text">Seuls les cycles terminés et non retirés apparaissent ici.</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Montant à retirer (F CFA)</label>
                        <input type="number" name="montant_total" class="form-control form-control-lg fw-bold text-danger" 
                               max="{{ $carnet->solde_disponible }}" placeholder="0" required>
                        <div class="form-text text-end">Max disponible: <strong>{{ number_format($carnet->solde_disponible, 0) }} F</strong></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Commission</label>
                            <input type="number" name="commission" class="form-control" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date</label>
                            <input type="datetime-local" name="date_retrait" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted text-uppercase">Note</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Confirmer le Décaissement</button>
                </div>
            </form>
        </div>
    </div>
</div>