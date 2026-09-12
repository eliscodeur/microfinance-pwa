<div class="modal fade" id="modalOuvrirCycle" tabindex="-1" aria-labelledby="modalOuvrirCycleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalOuvrirCycleLabel">
                    <i class="bi bi-play-circle me-2"></i>Ouvrir un nouveau cycle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Fermer"></button>
            </div>
            <form id="formOuvrirCycle" action="{{ route('admin.cycles.store') }}" method="POST">
                @csrf
                <input type="hidden" name="carnet_id" id="cycle_carnet_id" value="{{ $carnet->id }}">

                <div class="modal-body p-4">
                    {{-- Informations contextuelles --}}
                    <div class="alert alert-light border mb-3">
                        <div class="mb-2">
                            <small class="text-muted d-block text-uppercase fw-bold"
                                style="font-size: 0.7rem;">Client</small>
                            <span class="fw-bold text-dark fs-6" id="info_client_nom">{{ $carnet->client->nom }}
                                {{ $carnet->client->prenom }}</span>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Agent
                                lié au carnet</small>
                            <span class="text-secondary fw-semibold" id="info_agent_nom">
                                {{ $carnet->agent ? $carnet->agent->nom . ' ' . $carnet->agent->prenom : 'Aucun agent assigné' }}
                            </span>
                        </div>
                    </div>

                    {{-- Saisie de la mise journalière --}}
                    <div class="mb-3">
                        <label for="montant_journalier" class="form-label fw-bold text-uppercase small text-muted">Mise
                            journalière (F)</label>
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control fw-bold text-success" id="montant_journalier"
                                name="montant_journalier" min="1" required>
                            <span class="input-group-text bg-light">F CFA</span>
                        </div>
                        {{-- <div class="form-text text-muted">Ce cycle comportera par défaut une base de 31 parts.</div> --}}
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success fw-bold">Valider l'ouverture</button>
                </div>
            </form>
        </div>
    </div>
</div>
