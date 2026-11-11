@extends('admin.layouts.app')

@section('content')

    @php
        $encoursCreditRestant = $carnet->credits->whereIn('statut', ['active', 'in_arrears'])->sum(function ($credit) {
            return $credit->montant_accorde +
                $credit->interet_total +
                ($credit->penalty_amount ?? 0) -
                ($credit->montant_rembourse ?? 0);
        });

        $soldeDisponible = $carnet->type === 'tontine' ? $carnet->solde_tontine_non_retire : $carnet->solde_disponible;

        $cyclesDisponibles =
            $carnet->type === 'tontine'
                ? $carnet->cycles->where('statut', 'termine')->whereNull('retire_at')->values()
                : collect();

        $cycleEnCours = $carnet->type === 'tontine' ? $carnet->cycles->where('statut', 'en_cours')->first() : null;

        $cyclesJs = $cyclesDisponibles->values()->map(function ($cycle, $index) {
            $cumulCollectes = $cycle->collectes->sum('montant');
            $dejaRetire = $cycle->retraits->sum('montant_net');
            $commissionDejaPrelevee = $cycle->retraits->sum('commission')
                + $cycle->creditSettlementSources->sum('commission');
            $montantAffecteCredit = $cycle->creditSettlementSources->sum('montant_affecte');
            $soldeBrut = $cumulCollectes - $dejaRetire;
            $cycleAlreadyUsed = $cycle->retraits->isNotEmpty() || $cycle->creditSettlementSources->isNotEmpty();
            $commission = $cycleAlreadyUsed ? 0 : ($cycle->montant_journalier ?? 0);
            $soldeBrut -= $montantAffecteCredit;
            $soldeNet = $soldeBrut - $commissionDejaPrelevee - $commission;
            return [
                'id' => $cycle->id,
                'label' => 'Cycle #' . ($index + 1) . ' — Net : ' . number_format($soldeNet, 0, ',', ' ') . ' F',
                'solde_brut' => $soldeBrut,
                'commission' => $commission,
                'commission_deja_prelevee' => $commissionDejaPrelevee,
                'solde_net' => $soldeNet,
            ];
        });
    @endphp

    <div class="container-fluid">
        @php $cashWarningMessage = "Impossible d'enregistrer des dépôts ou retraits sur les carnets sans une caisse ouverte."; @endphp
        @include('admin.partials.cash-warning-banner')
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
            @inject('sessionService', 'App\Services\CashSessionService')
            @php
                $hasCashOpen = auth()->check() && $sessionService->hasActiveSession(auth()->id());
            @endphp
            @if($hasCashOpen)
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary fw-bold" id="btnRetrait" data-type="{{ $carnet->type }}"
                    data-solde="{{ $soldeDisponible }}" data-encours="{{ $encoursCreditRestant }}"
                    data-carnet-id="{{ $carnet->id }}" data-client-id="{{ $carnet->client_id }}"
                    data-solde-global="{{ $carnet->type === 'compte' ? $carnet->solde_disponible : 0 }}"
                    data-csrf="{{ csrf_token() }}" data-retrait-url="{{ route('admin.carnets.retrait') }}"
                    data-idempotency-key="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <i class="bi bi-box-arrow-up me-2"></i>Effectuer un Retrait
                </button>

                @if ($carnet->type === 'compte')
                    {{-- Bouton Dépôt pour Épargne/Compte --}}
                    <button type="button" class="btn btn-success fw-bold" id="btnDepot"
                        data-carnet-id="{{ $carnet->id }}" data-client-id="{{ $carnet->client_id }}"
                        data-csrf="{{ csrf_token() }}" data-depot-url="{{ route('admin.carnets.depot') }}"
                        data-idempotency-key="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <i class="bi bi-plus-lg me-2"></i>Nouveau Dépôt
                    </button>
                @elseif($carnet->type === 'tontine')
                    @if (!$cycleEnCours)
                        {{-- Si aucun cycle en cours : Déclenche le modal d'ouverture de cycle --}}
                        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal"
                            data-bs-target="#modalOuvrirCycle">
                            <i class="bi bi-play-circle me-2"></i>Ouvrir un Cycle
                        </button>
                    @else
                        {{-- Si un cycle est en cours : Déclenche le modal de collecte au bureau --}}
                        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal"
                            data-bs-target="#modalCollecteBureau">
                            <i class="bi bi-cash-stack me-2"></i>Gérer Collecte
                        </button>
                    @endif
                @endif
            </div>
            @endif
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
                    @if ($carnet->type === 'tontine')
                        <li class="nav-item">
                            <button class="nav-link active px-4 py-3 fw-bold" data-bs-toggle="tab"
                                data-bs-target="#tab-cycles">
                                <i class="bi bi-arrow-repeat me-2"></i>Historique des Cycles
                            </button>
                        </li>
                    @else
                        <li class="nav-item">
                            <button class="nav-link active px-4 py-3 fw-bold" data-bs-toggle="tab"
                                data-bs-target="#tab-depots">
                                <i class="bi bi-journal-plus me-2"></i>Historique des Dépôts
                            </button>
                        </li>
                    @endif
                    <li class="nav-item">
                        <button class="nav-link px-4 py-3 fw-bold text-danger" data-bs-toggle="tab"
                            data-bs-target="#tab-retraits">
                            <i class="bi bi-box-arrow-up me-2"></i>Historique des Retraits
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link px-4 py-3 fw-bold text-warning" data-bs-toggle="tab"
                            data-bs-target="#tab-credits">
                            <i class="bi bi-bank me-2"></i>Crédits & Prêts
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-4">

                @if ($carnet->type === 'tontine')
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
                                @foreach ($carnet->cycles->sortByDesc('created_at') as $cycle)
                                    <tr>
                                        <td class="fw-bold">#{{ $loop->iteration }}</td>
                                        <td>{{ number_format($cycle->montant_journalier, 0) }} F</td>
                                        <td class="text-primary fw-bold">
                                            {{ number_format($cycle->collectes->sum('montant'), 0) }} F</td>
                                        <td>{{ $cycle->retire_at ? $cycle->retire_at->format('d/m/Y') : 'En attente' }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $cycle->statut === 'termine' ? 'bg-success' : ($cycle->statut === 'en_cours' ? 'bg-warning' : 'bg-secondary') }}">
                                                {{ ucfirst($cycle->statut) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-voir-collectes"
                                                data-cycle="{{ $loop->iteration }}"
                                                data-collectes="{{ json_encode(
                                                    $cycle->collectes->map(function ($c) {
                                                        return [
                                                            'date' => $c->created_at->format('d/m/Y H:i'),
                                                            'montant' => number_format($c->montant, 0, ',', ' ') . ' F',
                                                            'pointage' => $c->pointage ?? 1,
                                                            'agent' => $c->agent ? $c->agent->nom . ' ' . $c->agent->prenom : 'Inconnu',
                                                            'payeur' => $c->payeur_nom ?: 'Titulaire du carnet',
                                                            'receipt_url' => route('admin.collectes.receipt', $c),
                                                        ];
                                                    }),
                                                ) }}">
                                                <i class="bi bi-list-check"></i> Collectes
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($carnet->type === 'compte')
                    <div class="tab-pane fade show active" id="tab-depots">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Heure</th>
                                    <th>Montant</th>
                                    <th>Payeur</th>
                                    <th>Mode / Référence</th>
                                    <th>Enregistré par</th>
                                    <th>Reçu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($carnet->depots->whereNull('cycle_id')->sortByDesc('date_depot') as $depot)
                                    <tr>
                                        <td>{{ $depot->date_depot->format('d/m/Y H:i') }}</td>
                                        <td class="fw-bold text-success">{{ number_format($depot->montant, 0) }} F</td>
                                        <td class="small">
                                            {{ $depot->payeur_nom ?: $carnet->client->nom . ' ' . $carnet->client->prenom }}
                                            @if ($depot->payeur_type === 'tiers')
                                                <span class="badge bg-info-subtle text-info">Tiers</span>
                                            @endif
                                            @if ($depot->commentaire)
                                                <span class="d-block text-muted">{{ $depot->commentaire }}</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            {{ ['especes' => 'Espèces', 'mobile_money' => 'Mobile Money', 'virement' => 'Virement'][$depot->mode_paiement] ?? 'Espèces' }}
                                            @if ($depot->reference_paiement)
                                                <span class="d-block text-muted">{{ $depot->reference_paiement }}</span>
                                            @endif
                                        </td>
                                        <td class="text-uppercase small fw-bold">{{ $depot->user->name ?? '—' }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('admin.depots.receipt', $depot) }}" target="_blank" rel="noopener">
                                                <i class="bi bi-receipt"></i> Reçu
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Aucun dépôt enregistré.
                                        </td>
                                    </tr>
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
                                <th>Référence</th>
                                <th>Type</th>
                                <th class="text-end">Total débité</th>
                                <th class="text-end">Commission</th>
                                <th class="text-end">Net perçu</th>
                                <th>Mode</th>
                                <th>Agent caisse</th>
                                <th>Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($carnet->retraits->sortByDesc('date_retrait') as $retrait)
                                <tr>
                                    <td>{{ $retrait->date_retrait->format('d/m/Y H:i') }}</td>
                                    <td class="small">{{ $retrait->reference_operation }}</td>
                                    <td>
                                        <span class="badge {{ $retrait->cycle_id ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $retrait->cycle_id ? 'Tontine' : 'Épargne' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-muted">{{ number_format($retrait->montant_total, 0) }} F</td>
                                    <td class="text-end text-danger">-{{ number_format($retrait->commission, 0) }} F</td>
                                    <td class="text-end fw-bold text-success">
                                        {{ number_format($retrait->montant_net, 0) }} F</td>
                                    <td>{{ ['especes' => 'Espèces', 'mobile_money' => 'Mobile Money', 'virement' => 'Virement'][$retrait->mode_paiement] ?? '—' }}</td>
                                    <td class="small fw-bold text-uppercase">{{ $retrait->admin->name ?? 'Admin' }}</td>
                                    <td>
                                        <a href="{{ route('admin.retraits.receipt', $retrait) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                            <i class="bi bi-printer"></i><span class="visually-hidden">Imprimer le reçu</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Aucun retrait enregistré.</td>
                                </tr>
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
                                        <a href="{{ route('admin.credits.show', $credit->id) }}"
                                            class="text-decoration-none">
                                            {{ number_format($credit->montant_accorde, 0) }} F
                                        </a>
                                    </td>
                                    <td class="text-danger fw-bold">
                                        {{ number_format($credit->montant_accorde + $credit->interet_total + ($credit->penalty_amount ?? 0) - ($credit->montant_rembourse ?? 0), 0) }}
                                        F
                                    </td>
                                    <td>{{ $credit->date_fin_prevue ? $credit->date_fin_prevue->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ in_array($credit->statut, ['closed']) ? 'bg-success' : (in_array($credit->statut, ['active', 'in_arrears']) ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ $credit->statut === 'active' ? 'Actif' : ($credit->statut === 'in_arrears' ? 'En retard' : ($credit->statut === 'closed' ? 'Clôturé' : ($credit->statut === 'approved' ? 'Approuvé' : ucfirst($credit->statut)))) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Aucun crédit enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- Inclusion des Modals Partials --}}
        @if ($carnet->type === 'tontine')
            @include('admin.carnets.partials.modal-cycle')
            @if ($cycleEnCours)
                @include('admin.carnets.partials.modal-collecte')
            @endif
        @endif

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Si Laravel renvoie un message de succès en session, on l'affiche avec SweetAlert
            @if (session('swal_success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    text: "{!! session('swal_success') !!}",
                    timer: 2000,
                    showConfirmButton: false
                })
            @endif

            @if (session('error') || session('swal_error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Oups !',
                    text: "{!! session('error') ?? session('swal_error') !!}",
                    confirmButtonText: 'OK'
                });
            @endif
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const CYCLES_DATA = @json($cyclesJs);

        document.addEventListener('DOMContentLoaded', function() {

            const fmt = n => new Intl.NumberFormat('fr-FR').format(Math.round(n));
            const escapeHtml = value => {
                const element = document.createElement('span');
                element.textContent = value ?? '';
                return element.innerHTML;
            };

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
                    const res = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json();

                    if (json.success) {
                        const receiptPrompt = await Swal.fire({
                            icon: 'success',
                            title: cfg.titreSucces,
                            text: `${json.message}${json.reference ? `\nRéférence : ${json.reference}` : ''}`,
                            confirmButtonColor: cfg.couleur,
                            confirmButtonText: json.receipt_url ? 'Ouvrir le reçu' : 'Fermer',
                            showCancelButton: Boolean(json.receipt_url),
                            cancelButtonText: 'Fermer',
                            allowOutsideClick: false,
                        });
                        if (json.receipt_url && receiptPrompt.isConfirmed) {
                            const receiptWindow = window.open(json.receipt_url, '_blank');
                            if (!receiptWindow) {
                                window.location.href = json.receipt_url;
                                return;
                            }
                        }
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: Object.values(json.errors || {}).flat().join(' ') || json.message || 'Une erreur est survenue.',
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
                btnRetrait.addEventListener('click', async function() {
                    const cfg = {
                        carnetType: this.dataset.type,
                        solde: parseFloat(this.dataset.solde) || 0,
                        encours: parseFloat(this.dataset.encours) || 0,
                        carnetId: this.dataset.carnetId,
                        clientId: this.dataset.clientId,
                        csrf: this.dataset.csrf,
                        retraitUrl: this.dataset.retraitUrl,
                        idempotencyKey: this.dataset.idempotencyKey,
                        soldeGlobal: parseFloat(this.dataset.soldeGlobal) || 0,
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
                const currentDate = new Date();
                currentDate.setMinutes(currentDate.getMinutes() - currentDate.getTimezoneOffset());
                const now = currentDate.toISOString().slice(0, 16);

                let cycleHtml = '';
                if (isTontine) {
                    const options = CYCLES_DATA.map(c =>
                        `<option value="${c.id}" data-solde="${c.solde_brut}" data-commission="${c.commission}" data-commission-deja="${c.commission_deja_prelevee}" data-net="${c.solde_net}">${c.label}</option>`
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

                const {
                    value: vals,
                    isConfirmed
                } = await Swal.fire({
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
                            <div class="small text-muted mb-2">Commission appliquée : <strong id="swal_commission">0</strong> F</div>
                            <div class="small text-muted mb-2">Commission déjà prélevée : <strong id="swal_commission_deja">0</strong> F</div>
                            <div class="small text-muted mb-2">Solde après retrait : <strong id="swal_restant">—</strong> F</div>
                            <label class="form-label fw-bold small text-muted text-uppercase">Montant à verser (F)</label>
                            <input type="number" id="swal_montant" class="form-control form-control-lg fw-bold"
                                placeholder="0" min="1" ${!isTontine ? `value="${Math.round(cfg.soldeGlobal)}"` : ''}>
                            <div id="swal_err" class="text-danger small mt-1 d-none">Montant supérieur au solde disponible.</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Mode de paiement</label>
                            <select id="swal_mode_paiement" class="form-select">
                                <option value="especes">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="virement">Virement bancaire</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Motif du retrait</label>
                            <select id="swal_motif" class="form-select">
                                <option value="retrait_normal">Retrait normal</option>
                                <option value="retrait_exceptionnel">Retrait exceptionnel</option>
                                ${isTontine ? '<option value="cloture_cycle">Clôture du cycle</option>' : ''}
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-12 d-none" id="swal_reference_paiement_group">
                            <label class="form-label fw-bold small text-muted text-uppercase">Référence de transaction</label>
                            <input type="text" id="swal_reference_paiement" class="form-control" maxlength="100"
                                placeholder="Référence mobile money ou bancaire">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date d'opération</label>
                            <input type="datetime-local" id="swal_date" class="form-control" value="${now}">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-bold small text-muted text-uppercase">Note complémentaire</label>
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
                            document.getElementById('swal_net').innerText = fmt(cfg.soldeGlobal);
                        }

                        const cycleSelect = document.getElementById('swal_cycle');
                        const montantInput = document.getElementById('swal_montant');
                        const brutEl = document.getElementById('swal_brut');
                        const netEl = document.getElementById('swal_net');
                        const restantEl = document.getElementById('swal_restant');
                        const radioTotal = document.getElementById('swal_total');
                        const radioPartiel = document.getElementById('swal_partiel');
                        const modePaiement = document.getElementById('swal_mode_paiement');
                        const commissionEl = document.getElementById('swal_commission');
                        const referenceGroup = document.getElementById('swal_reference_paiement_group');
                        const referencePaiement = document.getElementById('swal_reference_paiement');

                        if (modePaiement) {
                            modePaiement.addEventListener('change', () => {
                                const needsReference = modePaiement.value !== 'especes';
                                referenceGroup.classList.toggle('d-none', !needsReference);
                                referencePaiement.required = needsReference;
                            });
                        }

                        function recalcul() {
                            let soldeNet = cfg.soldeGlobal;
                            if (isTontine) {
                                if (!cycleSelect || cycleSelect.selectedIndex <= 0) return;
                                const opt = cycleSelect.options[cycleSelect.selectedIndex];
                                const soldeBrut = parseFloat(opt.dataset.solde) || 0;
                                const commission = parseFloat(opt.dataset.commission) || 0;
                                const commissionDejaPrelevee = parseFloat(opt.dataset.commissionDeja) || 0;
                                soldeNet = soldeBrut - commission - commissionDejaPrelevee;
                                brutEl.innerText = fmt(soldeBrut);
                                netEl.innerText = fmt(soldeNet);
                                commissionEl.innerText = fmt(commission);
                                document.getElementById('swal_commission_deja').innerText = fmt(commissionDejaPrelevee);
                            }
                            if (radioTotal && radioTotal.checked) {
                                montantInput.value = Math.round(soldeNet);
                                montantInput.readOnly = true;
                                montantInput.classList.add('bg-light');
                            } else {
                                montantInput.readOnly = false;
                                montantInput.classList.remove('bg-light');
                            }
                            restantEl.innerText = fmt(Math.max(0, soldeNet - (parseFloat(montantInput.value) || 0)));
                        }

                        if (cycleSelect) cycleSelect.addEventListener('change', recalcul);
                        if (radioTotal) radioTotal.addEventListener('change', recalcul);
                        if (radioPartiel) radioPartiel.addEventListener('change', recalcul);
                        montantInput.addEventListener('input', recalcul);
                        if (!isTontine) recalcul();
                    },
                    preConfirm: () => {
                        const montant = parseFloat(document.getElementById('swal_montant')
                            ?.value) || 0;
                        const date = document.getElementById('swal_date')?.value;
                        const note = document.getElementById('swal_note')?.value || '';
                        const modePaiement = document.getElementById('swal_mode_paiement')?.value;
                        const referencePaiement = document.getElementById('swal_reference_paiement')?.value.trim() || '';
                        const motif = document.getElementById('swal_motif')?.value;

                        let cycleId = null,
                            typeRetrait = 'partiel',
                            limiteNet = cfg.soldeGlobal;

                        if (isTontine) {
                            const cycleSelect = document.getElementById('swal_cycle');
                            if (!cycleSelect || cycleSelect.selectedIndex <= 0) {
                                Swal.showValidationMessage('Veuillez sélectionner un cycle.');
                                return false;
                            }
                            const opt = cycleSelect.options[cycleSelect.selectedIndex];
                            cycleId = opt.value;
                            limiteNet = parseFloat(opt.dataset.net) || 0;
                            typeRetrait = document.getElementById('swal_total')?.checked ? 'total' :
                                'partiel';
                        }

                        if (montant <= 0) {
                            Swal.showValidationMessage('Le montant doit être supérieur à 0.');
                            return false;
                        }
                        if (montant > limiteNet + 0.5) {
                            document.getElementById('swal_err')?.classList.remove('d-none');
                            Swal.showValidationMessage('Montant trop élevé. Maximum : ' + fmt(
                                limiteNet) + ' F');
                            return false;
                        }
                        if (!date) {
                            Swal.showValidationMessage('La date est obligatoire.');
                            return false;
                        }
                        if (modePaiement !== 'especes' && !referencePaiement) {
                            Swal.showValidationMessage('La référence du paiement mobile ou bancaire est obligatoire.');
                            return false;
                        }

                        return {
                            montant,
                            date,
                            note,
                            modePaiement,
                            referencePaiement,
                            motif,
                            cycleId,
                            typeRetrait
                        };
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

                if (!confirm.isConfirmed) {
                    await afficherFormulaireRetrait(cfg);
                    return;
                }

                await envoyerFormulaire(cfg.retraitUrl, cfg.csrf, {
                    carnet_id: cfg.carnetId,
                    client_id: cfg.clientId,
                    cycle_id: vals.cycleId,
                    montant_total: vals.montant,
                    date_retrait: vals.date,
                    type_retrait: vals.typeRetrait,
                    mode_paiement: vals.modePaiement,
                    reference_paiement: vals.referencePaiement,
                    motif: vals.motif,
                    idempotency_key: cfg.idempotencyKey,
                    note: vals.note,
                }, {
                    titreSucces: 'Retrait effectué !',
                    couleur: '#4e73df'
                });
            }

            // ══════════════════════════════════════════════════════
            // 2. DÉPÔT
            // ══════════════════════════════════════════════════════
            const btnDepot = document.getElementById('btnDepot');

            if (btnDepot) {
                btnDepot.addEventListener('click', async function() {
                    const carnetId = this.dataset.carnetId;
                    const csrf = this.dataset.csrf;
                    const depotUrl = this.dataset.depotUrl;
                    const idempotencyKey = this.dataset.idempotencyKey;
                    const currentDate = new Date();
                    currentDate.setMinutes(currentDate.getMinutes() - currentDate.getTimezoneOffset());
                    const now = currentDate.toISOString().slice(0, 16);

                    const {
                        value: vals,
                        isConfirmed
                    } = await Swal.fire({
                        title: '<i class="bi bi-piggy-bank me-2"></i>Nouveau Dépôt Épargne',
                        width: 560,
                        html: `
                    <div class="text-start">
                        <div class="alert alert-light border small mb-3">
                            Le titulaire du carnet reste le bénéficiaire. Les informations du payeur servent à la traçabilité du versement.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Montant versé (FCFA)</label>
                            <input type="number" id="swal_depot_montant" class="form-control form-control-lg fw-bold text-success" placeholder="0" min="1" max="9999999999999" step="1" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Mode de paiement</label>
                                <select id="swal_depot_mode" class="form-select" required>
                                    <option value="especes">Espèces</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="virement">Virement bancaire</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Payeur</label>
                                <select id="swal_depot_payeur_type" class="form-select" required>
                                    <option value="client">Le titulaire du carnet</option>
                                    <option value="tiers">Une autre personne</option>
                                </select>
                            </div>
                            <div class="col-12 d-none" id="swal_depot_reference_group">
                                <label class="form-label fw-bold small text-muted text-uppercase">Référence de transaction</label>
                                <input type="text" id="swal_depot_reference" class="form-control" maxlength="100" placeholder="Référence mobile money ou bancaire">
                            </div>
                        </div>
                        <div id="swal_depot_tiers_group" class="row g-2 mb-3 d-none">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nom du payeur</label>
                                <input type="text" id="swal_depot_payeur_nom" class="form-control" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted text-uppercase">Téléphone (facultatif)</label>
                                <input type="tel" id="swal_depot_payeur_telephone" class="form-control" maxlength="30">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-muted text-uppercase">Lien avec le titulaire</label>
                                <input type="text" id="swal_depot_payeur_lien" class="form-control" maxlength="80" placeholder="Ex. conjoint, parent, représentant">
                            </div>
                            <div class="col-12 small text-muted">Identité déclarée lors de l’opération ; ne vaut pas vérification d’identité.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date de l'opération</label>
                            <input type="datetime-local" id="swal_depot_date" class="form-control" value="${now}" max="${now}" required>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-bold small text-muted text-uppercase">Note / Commentaire (Optionnel)</label>
                            <textarea id="swal_depot_note" class="form-control" rows="2" maxlength="1000" placeholder="Information utile à la traçabilité du dépôt"></textarea>
                        </div>
                    </div>`,
                        showCancelButton: true,
                        confirmButtonText: 'Confirmer le dépôt',
                        cancelButtonText: 'Annuler',
                        confirmButtonColor: '#1cc88a',
                        cancelButtonColor: '#858796',
                        focusConfirm: false,
                        didOpen: () => {
                            const payeurType = document.getElementById('swal_depot_payeur_type');
                            const tiersGroup = document.getElementById('swal_depot_tiers_group');
                            const payeurNom = document.getElementById('swal_depot_payeur_nom');
                            const payeurLien = document.getElementById('swal_depot_payeur_lien');
                            const mode = document.getElementById('swal_depot_mode');
                            const referenceGroup = document.getElementById('swal_depot_reference_group');
                            const reference = document.getElementById('swal_depot_reference');
                            const syncConditionalFields = () => {
                                const isThirdParty = payeurType.value === 'tiers';
                                const needsReference = mode.value !== 'especes';
                                tiersGroup.classList.toggle('d-none', !isThirdParty);
                                payeurNom.required = isThirdParty;
                                payeurLien.required = isThirdParty;
                                referenceGroup.classList.toggle('d-none', !needsReference);
                                reference.required = needsReference;
                            };
                            payeurType.addEventListener('change', syncConditionalFields);
                            mode.addEventListener('change', syncConditionalFields);
                            syncConditionalFields();
                            setTimeout(() => document.getElementById('swal_depot_montant')?.focus(), 100);
                        },
                        preConfirm: () => {
                            const montantRaw = document.getElementById('swal_depot_montant')?.value;
                            const montant = Number(montantRaw);
                            const date = document.getElementById('swal_depot_date')?.value;
                            const mode = document.getElementById('swal_depot_mode')?.value;
                            const payeurType = document.getElementById('swal_depot_payeur_type')?.value;
                            const reference = document.getElementById('swal_depot_reference')?.value.trim() || '';
                            const payeurNom = document.getElementById('swal_depot_payeur_nom')?.value.trim() || '';
                            const payeurTelephone = document.getElementById('swal_depot_payeur_telephone')?.value.trim() || '';
                            const payeurLien = document.getElementById('swal_depot_payeur_lien')?.value.trim() || '';
                            const note = document.getElementById('swal_depot_note')?.value.trim() || '';
                            if (!Number.isSafeInteger(montant) || montant <= 0) {
                                Swal.showValidationMessage('Saisissez un montant entier positif en FCFA.');
                                return false;
                            }
                            if (!date) {
                                Swal.showValidationMessage('La date est obligatoire.');
                                return false;
                            }
                            if (mode !== 'especes' && !reference) {
                                Swal.showValidationMessage('La référence de paiement est obligatoire pour ce mode de règlement.');
                                return false;
                            }
                            if (payeurType === 'tiers' && (!payeurNom || !payeurLien)) {
                                Swal.showValidationMessage('Le nom du payeur et son lien avec le titulaire sont obligatoires.');
                                return false;
                            }
                            return {
                                montant,
                                date,
                                mode,
                                reference,
                                payeurType,
                                payeurNom,
                                payeurTelephone,
                                payeurLien,
                                note
                            };
                        }
                    });

                    if (!isConfirmed || !vals) return;

                    await envoyerFormulaire(depotUrl, csrf, {
                        carnet_id: carnetId,
                        montant: vals.montant,
                        date_depot: vals.date,
                        mode_paiement: vals.mode,
                        reference_paiement: vals.reference,
                        payeur_type: vals.payeurType,
                        payeur_nom: vals.payeurNom,
                        payeur_telephone: vals.payeurTelephone,
                        payeur_lien: vals.payeurLien,
                        idempotency_key: idempotencyKey,
                        commentaire: vals.note,
                    }, {
                        titreSucces: 'Dépôt enregistré !',
                        couleur: '#1cc88a'
                    });
                });
            }

            // ══════════════════════════════════════════════════════
            // 3. COLLECTES
            // ══════════════════════════════════════════════════════
            document.querySelectorAll('.btn-voir-collectes').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cycleNum = this.dataset.cycle;
                    let collectes = [];
                    try {
                        collectes = JSON.parse(this.dataset.collectes);
                        collectes.sort((a, b) => new Date(b.date.split('/').reverse().join('-')) -
                            new Date(a.date.split('/').reverse().join('-')));
                    } catch (e) {
                        return;
                    }

                    const rows = collectes.length === 0 ?
                        `<tr><td colspan="6" class="text-center py-4 text-muted">Aucune collecte.</td></tr>` :
                        collectes.map(c => `
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
                                <small class="fw-semibold text-truncate" style="max-width:120px;">${escapeHtml(c.agent)}</small>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-info-subtle text-info border border-info px-2">${c.pointage}</span>
                        </td>
                        <td><small>${escapeHtml(c.payeur)}</small></td>
                        <td class="text-end pe-2 fw-bold">${c.montant}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="${c.receipt_url}" target="_blank" rel="noopener">Reçu</a></td>
                    </tr>`).join('');

                    Swal.fire({
                        title: `<i class="bi bi-journal-check me-2"></i>Cycle #${cycleNum} — Collectes`,
                        width: 900,
                        html: `
                    <div style="max-height:420px;overflow-y:auto;">
                        <table class="table table-hover align-middle mb-0 text-start">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-2">Date / Heure</th>
                                    <th>Agent</th>
                                    <th class="text-center">Pointage</th>
                                    <th>Payeur</th>
                                    <th class="text-end pe-2">Montant</th>
                                    <th>Reçu</th>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('modalCollecteBureau');
            const pointageInput = document.getElementById('col_pointage');
            const montantInput = document.getElementById('col_montant');
            const payeurTypeInput = document.getElementById('col_payeur_type');
            const tiersFields = document.getElementById('col_payeur_tiers');
            const payeurNomInput = document.getElementById('col_payeur_nom');
            const payeurLienInput = document.getElementById('col_payeur_lien');
            const collecteForm = document.getElementById('formCollecteBureau');

            if (modalEl && pointageInput && montantInput) {
                const miseJournaliere = parseFloat(modalEl.getAttribute('data-mise')) || 0;
                const pointagesRestants = parseInt(modalEl.getAttribute('data-restants'), 10);

                // Définir la limite maximale autorisée sur l'input HTML
                pointageInput.setAttribute('max', pointagesRestants);
                pointageInput.setAttribute('min', '1');
                pointageInput.setAttribute('step', '1');

                pointageInput.addEventListener('input', function() {
                    let pointages = parseInt(this.value) || 0;

                    // Empêcher de dépasser les pointages restants
                    if (pointages > pointagesRestants) {
                        pointages = pointagesRestants;
                        this.value = pointages; // Force l'input à redescendre au max autorisé
                    }

                    // Calcul automatique du montant
                    montantInput.value = pointages * miseJournaliere;
                });
            }

            if (payeurTypeInput && tiersFields && payeurNomInput && payeurLienInput) {
                const toggleThirdParty = () => {
                    const isThirdParty = payeurTypeInput.value === 'tiers';
                    tiersFields.classList.toggle('d-none', !isThirdParty);
                    payeurNomInput.required = isThirdParty;
                    payeurLienInput.required = isThirdParty;
                };
                payeurTypeInput.addEventListener('change', toggleThirdParty);
                toggleThirdParty();
            }

            if (collecteForm) {
                collecteForm.addEventListener('submit', function() {
                    const submitButton = collecteForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerText = 'Enregistrement...';
                    }
                });
            }
        });
    </script>

@endsection
