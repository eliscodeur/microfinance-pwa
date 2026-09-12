@php
    $totalPointagesActuels = $cycleEnCours ? $cycleEnCours->collectes->sum('pointage') : 0;
    $restants = max(0, 31 - $totalPointagesActuels);
@endphp
<div class="modal fade" id="modalCollecteBureau" tabindex="-1" aria-labelledby="modalCollecteBureauLabel" aria-hidden="true"
    data-mise="{{ $cycleEnCours?->montant_journalier ?? 0 }}" data-restants="{{ $restants }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow">

            {{-- En-tête du modal --}}
            <div class="modal-header bg-success text-white py-2 px-3">
                <h5 class="modal-title fw-bold fs-6" id="modalCollecteBureauLabel">
                    <i class="bi bi-cash-stack me-2"></i>Enregistrer une Collecte (Bureau)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Fermer"></button>
            </div>

            {{-- Formulaire --}}
            <form id="formCollecteBureau" action="{{ route('admin.collectes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="cycle_id" id="collecte_cycle_id" value="{{ $cycleEnCours?->id }}">

                <div class="modal-body p-3">
                    {{-- Informations contextuelles & Mise fixe en haut --}}
                    <div class="alert alert-light border mb-3 p-3">
                        <div class="row align-items-center">
                            <div class="col-7">
                                <small class="text-muted d-block text-uppercase fw-bold"
                                    style="font-size: 0.65rem;">Client</small>
                                <span class="fw-bold text-dark fs-6" id="col_client_nom">{{ $carnet->client->nom }}
                                    {{ $carnet->client->prenom }}</span>
                            </div>
                            <div class="col-5 text-end">
                                <small class="text-muted d-block text-uppercase fw-bold"
                                    style="font-size: 0.65rem;">Mise Journalière (Fixe)</small>
                                <span class="fw-bold text-success fs-5" id="col_mise_journaliere_text">
                                    {{ number_format($cycleEnCours?->montant_journalier ?? 0, 0, ',', ' ') }} F
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted" style="font-size: 0.8rem;">Agent lié au carnet :</span>
                                <span class="fw-semibold small text-secondary" id="col_agent_nom">
                                    {{ $carnet->agent ? $carnet->agent->nom . ' ' . $carnet->agent->prenom : 'Aucun agent assigné' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="small text-muted" style="font-size: 0.8rem;">Pointages restants (sur 31)
                                    :</span>
                                <span class="badge bg-info text-dark fw-bold" id="col_pointages_restants">
                                    @php
                                        $totalPointagesActuels = $cycleEnCours
                                            ? $cycleEnCours->collectes->sum('pointage')
                                            : 0;
                                        $restants = max(0, 31 - $totalPointagesActuels);
                                    @endphp
                                    {{ $restants }} / 31 restants
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Saisie du Nombre de parts / Pointages --}}
                    <div class="mb-3">
                        <label for="col_pointage" class="form-label fw-bold text-uppercase text-muted"
                            style="font-size: 0.75rem;">Nombre de parts / Pointages</label>
                        <input type="number" class="form-control fw-bold" id="col_pointage" name="pointage"
                            value="1" min="1" max="31" required>
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Modifiez le nombre de parts pour
                            recalculer automatiquement le montant.</div>
                    </div>

                    {{-- Montant total calculé automatiquement --}}
                    <div class="mb-3">
                        <label for="col_montant" class="form-label fw-bold text-uppercase text-muted"
                            style="font-size: 0.75rem;">Montant total à percevoir (F)</label>
                        <div class="input-group">
                            <input type="number" class="form-control fw-bold text-success bg-white" id="col_montant"
                                name="montant" value="{{ $cycleEnCours?->montant_journalier ?? 0 }}" readonly required>
                            <span class="input-group-text bg-light">F CFA</span>
                        </div>
                    </div>

                </div>

                {{-- Pied du modal avec les boutons --}}
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success fw-bold btn-sm px-3">Enregistrer la collecte</button>
                </div>
            </form>
        </div>
    </div>
</div>
