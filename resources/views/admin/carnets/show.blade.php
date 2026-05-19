@extends('admin.layouts.app')

@section('content')

@php
    $encoursCreditRestant = $carnet->credits
        ->whereIn('statut', ['active', 'in_arrears'])
        ->sum(function($credit) {
            return ($credit->montant_accorde + $credit->interet_total + ($credit->penalty_amount ?? 0))
                   - ($credit->montant_rembourse ?? 0);
        });

    $soldeDisponible = $carnet->type === 'tontine'
        ? $carnet->solde_tontine_non_retire
        : $carnet->solde_disponible;

    $cyclesDisponibles = $carnet->type === 'tontine'
        ? $carnet->cycles->where('statut', 'termine')->whereNull('retire_at')->values()
        : collect();

    $cyclesJs = $cyclesDisponibles->values()->map(function($cycle, $index) {
        $cumulCollectes = $cycle->collectes->sum('montant');
        $dejaRetire     = $cycle->retraits->sum('montant_net');
        $soldeBrut      = $cumulCollectes - $dejaRetire;
        $commission     = $cycle->montant_journalier ?? 0;
        $soldeNet       = $soldeBrut - $commission;
        return [
            'id'         => $cycle->id,
            'label'      => 'Cycle #' . ($index + 1) . ' — Net : ' . number_format($soldeNet, 0, ',', ' ') . ' F',
            'solde_brut' => $soldeBrut,
            'commission' => $commission,
            'solde_net'  => $soldeNet,
        ];
    });
@endphp

<div class="container-fluid">

    {{-- Entête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-0">Détails du Carnet #{{ $carnet->numero }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.carnets.index') }}">Carnets</a></li>
                    <li class="breadcrumb-item active">{{ $carnet->type === 'tontine' ? 'Tontine' : 'Épargne' }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary fw-bold" id="btnRetrait"
                data-type="{{ $carnet->type }}"
                data-solde="{{ $soldeDisponible }}"
                data-encours="{{ $encoursCreditRestant }}"
                data-carnet-id="{{ $carnet->id }}"
                data-client-id="{{ $carnet->client_id }}"
                data-solde-global="{{ $carnet->type === 'compte' ? $carnet->solde_disponible : 0 }}"
                data-csrf="{{ csrf_token() }}"
                data-retrait-url="{{ route('admin.carnets.retrait') }}">
                <i class="bi bi-box-arrow-up me-2"></i>Effectuer un Retrait
            </button>

            @if($carnet->type === 'compte')
            <button type="button" class="btn btn-success fw-bold" id="btnDepot"
                data-carnet-id="{{ $carnet->id }}"
                data-client-id="{{ $carnet->client_id }}"
                data-csrf="{{ csrf_token() }}"
                data-depot-url="{{ route('admin.carnets.depot') }}">
                <i class="bi bi-plus-lg me-2"></i>Nouveau Dépôt
            </button>
            @endif
        </div>
    </div>

    {{-- Cartes de Statistiques --}}
    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="h6 mb-1">{{ $carnet->client->nom }} {{ $carnet->client->prenom }}</div>
                <small class="text-primary mt-auto">{{ $carnet->client->telephone }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-success border-4 h-100">
                <small class="text-muted fw-bold text-uppercase">
                    {{ $carnet->type === 'tontine' ? 'Solde Tontine' : 'Solde Épargne' }}
                </small>
                <div class="h4 mb-0 text-success mt-1">
                    {{ number_format($soldeDisponible, 0, ',', ' ') }} F
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-danger border-4 h-100">
                <small class="text-muted fw-bold text-uppercase">Encours Crédit</small>
                <div class="h4 mb-0 text-danger mt-1">
                    {{ number_format($encoursCreditRestant, 0, ',', ' ') }} F
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-info border-4 h-100">
                <small class="text-muted fw-bold text-uppercase">Type de Carnet</small>
                <div class="h4 mb-0 text-info text-uppercase mt-1">{{ $carnet->type }}</div>
            </div>
        </div>
    </div>

    {{-- Onglets --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs border-0" id="detailTabs" role="tablist">
                @if($carnet->type === 'tontine')
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-cycles">
                            <i class="bi bi-arrow-repeat me-2"></i>Historique des Cycles
                        </button>
                    </li>
                @else
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-depots">
                            <i class="bi bi-journal-plus me-2"></i>Historique des Dépôts
                        </button>
                    </li>
                @endif
                <li class="nav-item">
                    <button class="nav-link px-4 py-3 fw-bold text-danger" data-bs-toggle="tab" data-bs-target="#tab-retraits">
                        <i class="bi bi-box-arrow-up me-2"></i>Historique des Retraits
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link px-4 py-3 fw-bold text-warning" data-bs-toggle="tab" data-bs-target="#tab-credits">
                        <i class="bi bi-bank me-2"></i>Crédits & Prêts
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content p-4">

            @if($carnet->type === 'tontine')
            <div class="tab-pane fade show active" id="tab-cycles">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N° Cycle</th>
                            <th>Mise Journalière</th>
                            <th>Total Collecté</th>
                            <th>Fin de cycle</th>
                            <th>État</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($carnet->cycles->sortByDesc('created_at') as $cycle)
                        <tr>
                            <td class="fw-bold">#{{ $loop->iteration }}</td>
                            <td>{{ number_format($cycle->montant_journalier, 0) }} F</td>
                            <td class="text-primary fw-bold">{{ number_format($cycle->collectes->sum('montant'), 0) }} F</td>
                            <td>{{ $cycle->retire_at ? $cycle->retire_at->format('d/m/Y') : 'En attente' }}</td>
                            <td>
                                <span class="badge {{ $cycle->statut === 'termine' ? 'bg-success' : ($cycle->statut === 'en_cours' ? 'bg-warning' : 'bg-secondary') }}">
                                    {{ ucfirst($cycle->statut) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-voir-collectes"
                                    data-cycle="{{ $loop->iteration }}"
                                    data-collectes="{{ json_encode($cycle->collectes->map(function($c) {
                                        return [
                                            'date'     => $c->created_at->format('d/m/Y H:i'),
                                            'montant'  => number_format($c->montant, 0, ',', ' ') . ' F',
                                            'pointage' => $c->pointage ?? 1,
                                            'agent'    => $c->agent ? ($c->agent->nom . ' ' . $c->agent->prenom) : 'Inconnu'
                                        ];
                                    })) }}">
                                    <i class="bi bi-list-check"></i> Collectes
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($carnet->type === 'compte')
            <div class="tab-pane fade show active" id="tab-depots">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Heure</th>
                            <th>Montant</th>
                            <th>Note / Commentaire</th>
                            <th>Enregistré par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carnet->depots->whereNull('cycle_id')->sortByDesc('date_depot') as $depot)
                        <tr>
                            <td>{{ $depot->date_depot->format('d/m/Y H:i') }}</td>
                            <td class="fw-bold text-success">{{ number_format($depot->montant, 0) }} F</td>
                            <td class="text-muted small">{{ $depot->commentaire ?? '-' }}</td>
                            <td class="text-uppercase small fw-bold">{{ $depot->user->name }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">Aucun dépôt enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            <div class="tab-pane fade" id="tab-retraits">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-end">Brut</th>
                            <th class="text-end">Commission</th>
                            <th class="text-end">Net perçu</th>
                            <th>Validé par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carnet->retraits->sortByDesc('date_retrait') as $retrait)
                        <tr>
                            <td>{{ $retrait->date_retrait->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $retrait->cycle_id ? 'bg-info' : 'bg-secondary' }}">
                                    {{ $retrait->cycle_id ? 'Tontine' : 'Épargne' }}
                                </span>
                            </td>
                            <td class="text-end text-muted">{{ number_format($retrait->montant_total, 0) }} F</td>
                            <td class="text-end text-danger">-{{ number_format($retrait->commission, 0) }} F</td>
                            <td class="text-end fw-bold text-success">{{ number_format($retrait->montant_net, 0) }} F</td>
                            <td class="small fw-bold text-uppercase">{{ $retrait->admin->name ?? 'Admin' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Aucun retrait enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="tab-credits">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Montant Prêté</th>
                            <th>Reste à payer</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carnet->credits as $credit)
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('admin.credits.show', $credit->id) }}" class="text-decoration-none">
                                    {{ number_format($credit->montant_accorde, 0) }} F
                                </a>
                            </td>
                            <td class="text-danger fw-bold">
                                {{ number_format(($credit->montant_accorde + $credit->interet_total + ($credit->penalty_amount ?? 0)) - ($credit->montant_rembourse ?? 0), 0) }} F
                            </td>
                            <td>{{ $credit->date_fin_prevue ? $credit->date_fin_prevue->format('d/m/Y') : '-' }}</td>
                            <td>
                                <span class="badge {{ in_array($credit->statut, ['closed']) ? 'bg-success' : (in_array($credit->statut, ['active', 'in_arrears']) ? 'bg-danger' : 'bg-secondary') }}">
                                    {{ $credit->statut === 'active' ? 'Actif' : ($credit->statut === 'in_arrears' ? 'En retard' : ($credit->statut === 'closed' ? 'Clôturé' : ($credit->statut === 'approved' ? 'Approuvé' : ucfirst($credit->statut)))) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">Aucun crédit enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CYCLES_DATA = @json($cyclesJs);

document.addEventListener('DOMContentLoaded', function () {

    const fmt = n => new Intl.NumberFormat('fr-FR').format(Math.round(n));

    // ══════════════════════════════════════════════════════
    // HELPER AJAX COMMUN
    // ══════════════════════════════════════════════════════
    async function envoyerFormulaire(url, csrf, data, cfg) {
        Swal.fire({
            title: 'Traitement en cours...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData();
        formData.append('_token', csrf);
        Object.entries(data).forEach(([k, v]) => formData.append(k, v ?? ''));

        try {
            const res  = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();

            if (json.success) {
                await Swal.fire({
                    icon: 'success',
                    title: cfg.titreSucces,
                    text: json.message,
                    confirmButtonColor: cfg.couleur,
                    timer: 2500,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: json.message || 'Une erreur est survenue.',
                    confirmButtonColor: '#e74a3b'
                });
            }
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur serveur',
                text: 'Veuillez réessayer.',
                confirmButtonColor: '#e74a3b'
            });
        }
    }

    // ══════════════════════════════════════════════════════
    // 1. RETRAIT
    // ══════════════════════════════════════════════════════
    const btnRetrait = document.getElementById('btnRetrait');

    if (btnRetrait) {
        btnRetrait.addEventListener('click', async function () {
            const cfg = {
                carnetType  : this.dataset.type,
                solde       : parseFloat(this.dataset.solde)      || 0,
                encours     : parseFloat(this.dataset.encours)    || 0,
                carnetId    : this.dataset.carnetId,
                clientId    : this.dataset.clientId,
                csrf        : this.dataset.csrf,
                retraitUrl  : this.dataset.retraitUrl,
                soldeGlobal : parseFloat(this.dataset.soldeGlobal) || 0,
            };

            // ── BLOCAGE : solde < encours ──────────────────────────
            if (cfg.encours > 0 && cfg.solde < cfg.encours) {
                Swal.fire({
                    icon: 'error',
                    title: 'Retrait impossible',
                    html: `
                        <p class="mb-3">Le solde est insuffisant pour couvrir le crédit en cours.</p>
                        <div class="d-flex justify-content-between px-2 mb-1">
                            <span class="text-muted">Solde disponible</span>
                            <strong class="text-success">${fmt(cfg.solde)} F</strong>
                        </div>
                        <div class="d-flex justify-content-between px-2">
                            <span class="text-muted">Encours crédit</span>
                            <strong class="text-danger">${fmt(cfg.encours)} F</strong>
                        </div>`,
                    confirmButtonText: 'Compris',
                    confirmButtonColor: '#e74a3b',
                });
                return;
            }

            // ── AVERTISSEMENT : crédit actif mais solde ok ─────────
            if (cfg.encours > 0) {
                const warn = await Swal.fire({
                    icon: 'warning',
                    title: 'Crédit en cours',
                    html: `
                        <p class="mb-3">Ce client a un crédit actif. Voulez-vous continuer ?</p>
                        <div class="d-flex justify-content-between px-2 mb-1">
                            <span class="text-muted">Solde disponible</span>
                            <strong class="text-success">${fmt(cfg.solde)} F</strong>
                        </div>
                        <div class="d-flex justify-content-between px-2">
                            <span class="text-muted">Encours crédit</span>
                            <strong class="text-danger">${fmt(cfg.encours)} F</strong>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Continuer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#f6c23e',
                    cancelButtonColor: '#858796',
                });
                if (!warn.isConfirmed) return;
            }

            await afficherFormulaireRetrait(cfg);
        });
    }

    async function afficherFormulaireRetrait(cfg) {
        const isTontine = cfg.carnetType === 'tontine';
        const now = new Date().toISOString().slice(0, 16);

        let cycleHtml = '';
        if (isTontine) {
            const options = CYCLES_DATA.map(c =>
                `<option value="${c.id}" data-solde="${c.solde_brut}" data-commission="${c.commission}" data-net="${c.solde_net}">${c.label}</option>`
            ).join('');
            cycleHtml = `
            <div class="mb-3 text-start">
                <label class="form-label fw-bold small text-muted text-uppercase">Sélection du Cycle</label>
                <select id="swal_cycle" class="form-select">
                    <option value="" disabled selected>--- Choisir le cycle ---</option>
                    ${options}
                </select>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label fw-bold small text-muted text-uppercase text-center d-block">Type de décaissement</label>
                <div class="btn-group w-100">
                    <input type="radio" class="btn-check" name="swal_type_retrait" id="swal_total" value="total" checked>
                    <label class="btn btn-outline-primary" for="swal_total">Clôture Totale</label>
                    <input type="radio" class="btn-check" name="swal_type_retrait" id="swal_partiel" value="partiel">
                    <label class="btn btn-outline-primary" for="swal_partiel">Retrait Partiel</label>
                </div>
            </div>`;
        }

        const { value: vals, isConfirmed } = await Swal.fire({
            title: `<i class="bi bi-wallet2 me-2"></i>Retrait ${isTontine ? 'Tontine' : 'Épargne'}`,
            width: 520,
            html: `
                <div class="text-start">
                    ${cycleHtml}
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Brut restant : <strong id="swal_brut">—</strong> F</span>
                                <span class="text-primary">Net dispo : <strong id="swal_net">—</strong> F</span>
                            </div>
                            <label class="form-label fw-bold small text-muted text-uppercase">Montant à verser (F)</label>
                            <input type="number" id="swal_montant" class="form-control form-control-lg fw-bold"
                                placeholder="0" min="1" ${!isTontine ? `value="${Math.round(cfg.soldeGlobal)}"` : ''}>
                            <div id="swal_err" class="text-danger small mt-1 d-none">Montant supérieur au solde disponible.</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Commission (F)</label>
                            <input type="number" id="swal_commission" class="form-control bg-light" value="0" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date d'opération</label>
                            <input type="datetime-local" id="swal_date" class="form-control" value="${now}">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-bold small text-muted text-uppercase">Note / Motif</label>
                        <textarea id="swal_note" class="form-control" rows="2" placeholder="Informations complémentaires..."></textarea>
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Valider le retrait',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#858796',
            focusConfirm: false,
            didOpen: () => {
                if (!isTontine) {
                    document.getElementById('swal_brut').innerText = fmt(cfg.soldeGlobal);
                    document.getElementById('swal_net').innerText  = fmt(cfg.soldeGlobal);
                }

                const cycleSelect   = document.getElementById('swal_cycle');
                const montantInput  = document.getElementById('swal_montant');
                const commissionInp = document.getElementById('swal_commission');
                const brutEl        = document.getElementById('swal_brut');
                const netEl         = document.getElementById('swal_net');
                const radioTotal    = document.getElementById('swal_total');
                const radioPartiel  = document.getElementById('swal_partiel');

                function recalcul() {
                    if (!isTontine || !cycleSelect || cycleSelect.selectedIndex <= 0) return;
                    const opt        = cycleSelect.options[cycleSelect.selectedIndex];
                    const soldeBrut  = parseFloat(opt.dataset.solde)     || 0;
                    const commission = parseFloat(opt.dataset.commission) || 0;
                    const soldeNet   = soldeBrut - commission;
                    brutEl.innerText    = fmt(soldeBrut);
                    netEl.innerText     = fmt(soldeNet);
                    commissionInp.value = Math.round(commission);
                    if (radioTotal && radioTotal.checked) {
                        montantInput.value    = Math.round(soldeNet);
                        montantInput.readOnly = true;
                        montantInput.classList.add('bg-light');
                    } else {
                        montantInput.readOnly = false;
                        montantInput.classList.remove('bg-light');
                    }
                }

                if (cycleSelect)  cycleSelect.addEventListener('change', recalcul);
                if (radioTotal)   radioTotal.addEventListener('change', recalcul);
                if (radioPartiel) radioPartiel.addEventListener('change', recalcul);
            },
            preConfirm: () => {
                const montant    = parseFloat(document.getElementById('swal_montant')?.value)    || 0;
                const date       = document.getElementById('swal_date')?.value;
                const note       = document.getElementById('swal_note')?.value                  || '';
                const commission = parseFloat(document.getElementById('swal_commission')?.value) || 0;

                let cycleId = null, typeRetrait = 'partiel', limiteNet = cfg.soldeGlobal;

                if (isTontine) {
                    const cycleSelect = document.getElementById('swal_cycle');
                    if (!cycleSelect || cycleSelect.selectedIndex <= 0) {
                        Swal.showValidationMessage('Veuillez sélectionner un cycle.');
                        return false;
                    }
                    const opt = cycleSelect.options[cycleSelect.selectedIndex];
                    cycleId     = opt.value;
                    limiteNet   = parseFloat(opt.dataset.net) || 0;
                    typeRetrait = document.getElementById('swal_total')?.checked ? 'total' : 'partiel';
                }

                if (montant <= 0) { Swal.showValidationMessage('Le montant doit être supérieur à 0.'); return false; }
                if (montant > limiteNet + 0.5) {
                    document.getElementById('swal_err')?.classList.remove('d-none');
                    Swal.showValidationMessage('Montant trop élevé. Maximum : ' + fmt(limiteNet) + ' F');
                    return false;
                }
                if (!date) { Swal.showValidationMessage('La date est obligatoire.'); return false; }

                return { montant, date, note, commission, cycleId, typeRetrait };
            }
        });

        if (!isConfirmed || !vals) return;

        // ── CONFIRMATION FINALE ────────────────────────────────────
        const confirm = await Swal.fire({
            icon: 'question',
            title: 'Confirmer le retrait ?',
            html: `Retrait de <strong>${fmt(vals.montant)} F</strong>.<br><span class="text-muted small">Cette action est irréversible.</span>`,
            showCancelButton: true,
            confirmButtonText: 'Oui, valider',
            cancelButtonText: 'Retour',
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#858796',
        });

        if (!confirm.isConfirmed) { await afficherFormulaireRetrait(cfg); return; }

        await envoyerFormulaire(cfg.retraitUrl, cfg.csrf, {
            carnet_id:     cfg.carnetId,
            client_id:     cfg.clientId,
            cycle_id:      vals.cycleId,
            montant_total: vals.montant,
            commission:    vals.commission,
            date_retrait:  vals.date,
            type_retrait:  vals.typeRetrait,
            note:          vals.note,
        }, { titreSucces: 'Retrait effectué !', couleur: '#4e73df' });
    }

    // ══════════════════════════════════════════════════════
    // 2. DÉPÔT
    // ══════════════════════════════════════════════════════
    const btnDepot = document.getElementById('btnDepot');

    if (btnDepot) {
        btnDepot.addEventListener('click', async function () {
            const carnetId = this.dataset.carnetId;
            const clientId = this.dataset.clientId;
            const csrf     = this.dataset.csrf;
            const depotUrl = this.dataset.depotUrl;
            const now      = new Date().toISOString().slice(0, 16);

            const { value: vals, isConfirmed } = await Swal.fire({
                title: '<i class="bi bi-piggy-bank me-2"></i>Nouveau Dépôt Épargne',
                width: 460,
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Montant du dépôt (F CFA)</label>
                            <input type="number" id="swal_depot_montant" class="form-control form-control-lg fw-bold text-success" placeholder="0" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date de l'opération</label>
                            <input type="datetime-local" id="swal_depot_date" class="form-control" value="${now}">
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-bold small text-muted text-uppercase">Note / Commentaire (Optionnel)</label>
                            <textarea id="swal_depot_note" class="form-control" rows="2" placeholder="Ex: Dépôt exceptionnel, Reliquat..."></textarea>
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Confirmer le dépôt',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                focusConfirm: false,
                didOpen: () => setTimeout(() => document.getElementById('swal_depot_montant')?.focus(), 100),
                preConfirm: () => {
                    const montant = parseFloat(document.getElementById('swal_depot_montant')?.value) || 0;
                    const date    = document.getElementById('swal_depot_date')?.value;
                    const note    = document.getElementById('swal_depot_note')?.value || '';
                    if (montant <= 0) { Swal.showValidationMessage('Le montant doit être supérieur à 0.'); return false; }
                    if (!date)        { Swal.showValidationMessage('La date est obligatoire.'); return false; }
                    return { montant, date, note };
                }
            });

            if (!isConfirmed || !vals) return;

            await envoyerFormulaire(depotUrl, csrf, {
                carnet_id:   carnetId,
                client_id:   clientId,
                montant:     vals.montant,
                date_depot:  vals.date,
                commentaire: vals.note,
            }, { titreSucces: 'Dépôt enregistré !', couleur: '#1cc88a' });
        });
    }

    // ══════════════════════════════════════════════════════
    // 3. COLLECTES
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.btn-voir-collectes').forEach(btn => {
        btn.addEventListener('click', function () {
            const cycleNum = this.dataset.cycle;
            let collectes  = [];
            try {
                collectes = JSON.parse(this.dataset.collectes);
                collectes.sort((a, b) => new Date(b.date.split('/').reverse().join('-')) - new Date(a.date.split('/').reverse().join('-')));
            } catch (e) { return; }

            const rows = collectes.length === 0
                ? `<tr><td colspan="4" class="text-center py-4 text-muted">Aucune collecte.</td></tr>`
                : collectes.map(c => `
                    <tr>
                        <td class="ps-2">
                            <div class="small fw-bold">${c.date.split(' ')[0]}</div>
                            <div class="small text-muted">${c.date.split(' ')[1]}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-2 bg-light rounded-circle text-center" style="width:25px;height:25px;line-height:25px;flex-shrink:0;">
                                    <i class="bi bi-person text-primary" style="font-size:.8rem;"></i>
                                </div>
                                <small class="fw-semibold text-truncate" style="max-width:120px;">${c.agent}</small>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-info-subtle text-info border border-info px-2">${c.pointage}</span>
                        </td>
                        <td class="text-end pe-2 fw-bold">${c.montant}</td>
                    </tr>`).join('');

            Swal.fire({
                title: `<i class="bi bi-journal-check me-2"></i>Cycle #${cycleNum} — Collectes`,
                width: 620,
                html: `
                    <div style="max-height:420px;overflow-y:auto;">
                        <table class="table table-hover align-middle mb-0 text-start">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-2">Date / Heure</th>
                                    <th>Agent</th>
                                    <th class="text-center">Pointage</th>
                                    <th class="text-end pe-2">Montant</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>`,
                confirmButtonText: 'Fermer',
                confirmButtonColor: '#4e73df',
            });
        });
    });
});
</script>

@endsection