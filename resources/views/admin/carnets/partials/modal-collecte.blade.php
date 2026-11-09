@php
    $totalPointagesActuels = $cycleEnCours ? $cycleEnCours->collectes->sum('pointage') : 0;
    $objectif = (int) ($cycleEnCours?->nombre_jours_objectif ?? 31);
    $restants = max(0, $objectif - $totalPointagesActuels);
@endphp
<div class="modal fade" id="modalCollecteBureau" tabindex="-1" aria-labelledby="modalCollecteBureauLabel" aria-hidden="true"
    data-mise="{{ $cycleEnCours?->montant_journalier ?? 0 }}" data-restants="{{ $restants }}"
    data-objectif="{{ $objectif }}">
    <style>
        #modalCollecteBureau .modal-content {
            max-height: calc(100vh - 2rem);
            max-height: calc(100dvh - 2rem);
        }

        #modalCollecteBureau .modal-content > form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        #modalCollecteBureau .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        #modalCollecteBureau .modal-footer {
            flex: 0 0 auto;
        }

        @media (max-width: 575.98px) {
            #modalCollecteBureau .modal-dialog {
                margin: 0.5rem;
            }
        }
    </style>
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
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

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
                                        $objectif = (int) ($cycleEnCours?->nombre_jours_objectif ?? 31);
                                        $restants = max(0, $objectif - $totalPointagesActuels);
                                    @endphp
                                    {{ $restants }} / {{ $objectif }} restants
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

                    <div class="mb-3">
                        <label for="col_payeur_type" class="form-label fw-bold text-uppercase text-muted"
                            style="font-size: 0.75rem;">Personne ayant remis les fonds</label>
                        <select class="form-select" id="col_payeur_type" name="payeur_type" required>
                            <option value="client" selected>Le titulaire du carnet</option>
                            <option value="tiers">Une autre personne</option>
                        </select>
                    </div>
                    <div id="col_payeur_tiers" class="row g-2 mb-3 d-none">
                        <div class="col-md-6">
                            <label for="col_payeur_nom" class="form-label small fw-bold">Nom du payeur</label>
                            <input type="text" class="form-control" id="col_payeur_nom" name="payeur_nom" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label for="col_payeur_telephone" class="form-label small fw-bold">Téléphone (facultatif)</label>
                            <input type="tel" class="form-control" id="col_payeur_telephone" name="payeur_telephone" maxlength="30">
                        </div>
                        <div class="col-12">
                            <label for="col_payeur_lien" class="form-label small fw-bold">Lien avec le titulaire</label>
                            <input type="text" class="form-control" id="col_payeur_lien" name="payeur_lien" maxlength="80"
                                placeholder="Ex. conjoint, parent, représentant">
                        </div>
                        <div class="col-12 small text-muted">Identité déclarée lors de l’opération ; ne vaut pas vérification d’identité.</div>
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
