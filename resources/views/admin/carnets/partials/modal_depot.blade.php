<!-- Modal Dépôt Épargne -->
<div class="modal fade" id="modalDepot" tabindex="-1" aria-labelledby="modalDepotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalDepotLabel">
                    <i class="bi bi-piggy-bank me-2"></i>Nouveau Dépôt - Épargne
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="modal" data-bs-target="#modalDepot" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('admin.carnets.depot') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <!-- Informations masquées pour la liaison -->
                    <input type="hidden" name="carnet_id" value="{{ $carnet->id }}">
                    <input type="hidden" name="client_id" value="{{ $carnet->client_id }}">
                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                    {{-- cycle_id reste null pour l'épargne --}}

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Montant du dépôt (F CFA)</label>
                        <div class="input-group">
                            <input type="number" name="montant" class="form-control form-control-lg fw-bold text-success" 
                                   placeholder="0" min="1" required autofocus>
                            <span class="input-group-text bg-light fw-bold">F</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Date de l'opération</label>
                        <input type="datetime-local" name="date_depot" class="form-control" 
                               value="{{ date('Y-m-d\TH:i') }}" required>
                        <div class="form-text">Utile pour enregistrer une collecte passée.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-uppercase text-muted">Note / Commentaire (Optionnel)</label>
                        <textarea name="commentaire" class="form-control" rows="2" 
                                  placeholder="Ex: Dépôt exceptionnel, Reliquat..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light border fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-check-circle me-1"></i>Confirmer le dépôt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>