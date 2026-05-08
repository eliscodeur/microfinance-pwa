<style>
    /* Palette personnalisée */
    :root {
        --primary-soft: #4e73df; /* Bleu doux */
        --secondary-soft: #f8f9fc;
        --text-dark: #2e3759;
    }

    #modalRetrait .modal-content {
        max-height: 85vh !important;
        overflow: hidden;
        border-radius: 12px; /* Coins plus arrondis pour le côté soft */
    }

    #modalRetrait .modal-header {
        background: var(--primary-soft) !important;
        border-bottom: none;
    }

    #modalRetrait .modal-body {
        overflow-y: auto !important;
        max-height: calc(85vh - 120px);
        background-color: #fff;
    }

    /* Style des boutons radio (Option de retrait) */
    #modalRetrait .btn-outline-danger {
        border-color: var(--primary-soft);
        color: var(--primary-soft);
    }

    #modalRetrait .btn-check:checked + .btn-outline-danger {
        background-color: var(--primary-soft);
        border-color: var(--primary-soft);
        color: #fff;
    }

    /* Input montant plus sobre */
    #montant_total {
        border: 2px solid #eaecf4;
        color: var(--text-dark) !important;
    }

    #montant_total:focus {
        border-color: var(--primary-soft);
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    }
</style>
<!-- <div class="modal fade" id="modalRetrait" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: #4e73df;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-wallet2 me-2"></i>Opération de Retrait
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="{{ route('admin.carnets.retrait') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="carnet_id" value="{{ $carnet->id }}">
                    <input type="hidden" name="client_id" value="{{ $carnet->client_id }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Sélection du Cycle</label>
                        <select name="cycle_id" id="cycle_select" class="form-select border-0 bg-light" required>
                            <option value="" selected disabled>--- Choisir le cycle ---</option>
                            @foreach($carnet->cycles->where('statut', 'termine')->whereNull('retire_at') as $cycle)
                                @php
                                    // CALCUL DYNAMIQUE DU SOLDE RESTANT
                                    $cumulCollectes = $cycle->collectes->sum('montant');
                                    // On déduit les retraits partiels déjà effectués sur ce cycle
                                    $dejaRetire = $cycle->retraits->sum('montant_net'); 
                                    
                                    $soldeBrutActuel = $cumulCollectes - $dejaRetire;
                                    $commission = $cycle->montant_journalier ?? 0;
                                    $soldeNetDisponible = $soldeBrutActuel - $commission;
                                @endphp
                                <option value="{{ $cycle->id }}" 
                                        data-solde="{{ $soldeBrutActuel }}" 
                                        data-commission="{{ $commission }}">
                                    Cycle #{{ $loop->iteration }} (Restant Net : {{ number_format($soldeNetDisponible, 0, ',', ' ') }} F)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase text-center d-block">Type de décaissement</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type_retrait" id="retrait_total" value="total" checked>
                            <label class="btn btn-outline-primary shadow-sm" for="retrait_total">Clôture Totale</label>

                            <input type="radio" class="btn-check" name="type_retrait" id="retrait_partiel" value="partiel">
                            <label class="btn btn-outline-primary shadow-sm" for="retrait_partiel">Retrait Partiel</label>
                        </div>
                    </div>

                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold small text-muted text-uppercase" id="label_montant">
                                Montant à verser au client
                            </label>
                            <input type="number" name="montant_total" id="montant_total" 
                                   class="form-control form-control-lg fw-bold bg-white" placeholder="0" required>
                            
                            <div class="mt-2 d-flex justify-content-between small">
                                <span class="text-muted">Brut restant: <strong id="brut_view">0</strong> F</span>
                                <span class="text-primary font-weight-bold">Net Dispo: <strong id="net_view">0</strong> F</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Commission</label>
                            <div class="input-group">
                                <input type="number" name="commission" id="commission_input" class="form-control bg-white border-0" value="0" readonly>
                                <span class="input-group-text bg-white border-0 text-muted">F</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date d'opération</label>
                            <input type="datetime-local" name="date_retrait" class="form-control border-0 bg-light" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted text-uppercase">Note / Motif</label>
                        <textarea name="note" class="form-control border-0 bg-light" rows="2" placeholder="Informations complémentaires..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-light fw-bold text-muted" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" id="submit_retrait" class="btn btn-primary px-5 fw-bold shadow">
                        Valider l'opération
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> -->
<div class="modal fade" id="modalRetrait" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            {{-- Le titre et la couleur changent selon le type --}}
            <div class="modal-header text-white" style="background: {{ $carnet->type === 'tontine' ? '#4e73df' : '#1cc88a' }};">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-wallet2 me-2"></i>Retrait : {{ $carnet->type === 'tontine' ? 'Tontine' : 'Épargne' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="{{ route('admin.carnets.retrait') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="carnet_id" value="{{ $carnet->id }}">
                    <input type="hidden" name="client_id" value="{{ $carnet->client_id }}">
                    <input type="hidden" id="carnet_type_hidden" value="{{ $carnet->type }}">

                    {{-- BLOC TONTINE : On ne l'affiche que si c'est une tontine --}}
                    @if($carnet->type === 'tontine')
                    <div id="bloc_tontine">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Sélection du Cycle</label>
                            <select name="cycle_id" id="cycle_select" class="form-select border-0 bg-light" required>
                                <option value="" selected disabled>--- Choisir le cycle ---</option>
                                @foreach($carnet->cycles->where('statut', 'termine')->whereNull('retire_at') as $cycle)
                                    @php
                                        $cumulCollectes = $cycle->collectes->sum('montant');
                                        $dejaRetire = $cycle->retraits->sum('montant_net'); 
                                        $soldeBrutActuel = $cumulCollectes - $dejaRetire;
                                        $commission = $cycle->montant_journalier ?? 0;
                                        $soldeNetDisponible = $soldeBrutActuel - $commission;
                                    @endphp
                                    <option value="{{ $cycle->id }}" 
                                            data-solde="{{ $soldeBrutActuel }}" 
                                            data-commission="{{ $commission }}">
                                        Cycle #{{ $loop->iteration }} (Net : {{ number_format($soldeNetDisponible, 0, ',', ' ') }} F)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase text-center d-block">Type de décaissement</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type_retrait" id="retrait_total" value="total" checked>
                                <label class="btn btn-outline-primary shadow-sm" for="retrait_total">Clôture Totale</label>

                                <input type="radio" class="btn-check" name="type_retrait" id="retrait_partiel" value="partiel">
                                <label class="btn btn-outline-primary shadow-sm" for="retrait_partiel">Retrait Partiel</label>
                            </div>
                        </div>
                    </div>
                    @else
                        {{-- BLOC ÉPARGNE : On affiche juste un rappel du solde --}}
                        <!-- <div class="alert alert-success border-0 mb-4 py-2"> -->
                            <!-- <small class="text-uppercase fw-bold opacity-75">Solde Épargne Disponible</small>
                            <div class="h4 mb-0 fw-bold">{{ number_format($carnet->solde_disponible, 0, ',', ' ') }} F</div> -->
                            <input type="hidden" name="cycle_id" value="">
                            <input type="hidden" name="type_retrait" value="partiel">
                        <!-- </div> -->
                    @endif

                    {{-- BLOC MONTANT (COMMUN) --}}
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold small text-muted text-uppercase" id="label_montant">
                                Montant à verser au client
                            </label>
                            <input type="number" name="montant_total" id="montant_total" 
                            class="form-control form-control-lg fw-bold bg-white" 
                            placeholder="0" 
                            {{-- Cette ligne est la clé --}}
                            data-solde-global="{{ $carnet->type === 'compte' ? $carnet->solde_disponible : 0 }}" 
                            required>
                            
                            <div class="mt-2 d-flex justify-content-between small">
                                <span class="text-muted">Brut restant: <strong id="brut_view">0</strong> F</span>
                                <span class="text-primary font-weight-bold">Net Dispo: <strong id="net_view">0</strong> F</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Commission</label>
                            <div class="input-group">
                                <input type="number" name="commission" id="commission_input" class="form-control bg-white border-0" value="0" readonly>
                                <span class="input-group-text bg-white border-0 text-muted">F</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date d'opération</label>
                            <input type="datetime-local" name="date_retrait" class="form-control border-0 bg-light" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted text-uppercase">Note / Motif</label>
                        <textarea name="note" class="form-control border-0 bg-light" rows="2" placeholder="Informations complémentaires..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-light fw-bold text-muted" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" id="submit_retrait" class="btn btn-primary px-5 fw-bold shadow">
                        Valider l'opération
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>