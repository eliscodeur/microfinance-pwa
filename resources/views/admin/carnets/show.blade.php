@extends('admin.layouts.sidebar')

@section('content')
<div class="container-fluid">
    {{-- Entête avec Actions Rapides --}}
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
            {{-- Le retrait est désormais accessible aux deux types --}}
            <button class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#modalRetrait">
                <i class="bi bi-box-arrow-up me-2"></i>Effectuer un Retrait
            </button>

            @if($carnet->type === 'compte')
                <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalDepot">
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
                    {{ number_format($carnet->type === 'tontine' ? $carnet->solde_tontine_non_retire : $carnet->solde_disponible, 0, ',', ' ') }} F
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-danger border-4 h-100">
                <small class="text-muted fw-bold text-uppercase">Encours Crédit</small>
                <div class="h4 mb-0 text-danger mt-1">
                    {{ number_format($carnet->credits->where('statut', 'en_cours')->sum('montant_restant'), 0, ',', ' ') }} F
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

    {{-- Onglets de gestion --}}
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
            {{-- TONTINE : CYCLES --}}
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
                                        class="btn btn-sm btn-outline-primary view-collectes" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalCollectes"
                                        data-cycle="{{ $loop->iteration }}"
                                        data-collectes="{{ json_encode($cycle->collectes->map(function($c) {
                                            return [
                                                'date' => $c->created_at->format('d/m/Y H:i'),
                                                'montant' => number_format($c->montant, 0, ',', ' ') . ' F',
                                                'pointage' => $c->pointage ?? 1, // On récupère le nombre de pointages
                                                'agent' => $c->agent ? ($c->agent->nom . ' ' . $c->agent->prenom) : 'Inconnu'
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

            {{-- ÉPARGNE : DÉPÔTS --}}
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

            {{-- COMMUN : RETRAITS --}}
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

            {{-- COMMUN : CRÉDITS --}}
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
                            <td class="fw-bold">{{ number_format($credit->montant, 0) }} F</td>
                            <td class="text-danger fw-bold">{{ number_format($credit->montant_restant, 0) }} F</td>
                            <td>{{ $credit->date_echeance ? $credit->date_echeance->format('d/m/Y') : '-' }}</td>
                            <td>
                                <span class="badge {{ $credit->statut === 'solde' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($credit->statut) }}
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

{{-- MODALS --}}
@include('admin.carnets.partials.modal_depot')
@include('admin.carnets.partials.modal_retrait')
@include('admin.carnets.partials.modal_collecte')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalCollectes = document.getElementById('modalCollectes');
        const tableBody = document.getElementById('collectesTableBody');
        const cycleSpan = document.getElementById('modalCycleNumber');

        if (modalCollectes) {
            modalCollectes.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                let collectes = [];
                
                try {
                    collectes = JSON.parse(button.getAttribute('data-collectes'));
                    
                    // TRI PAR DATE DÉCROISSANTE
                    collectes.sort((a, b) => {
                        const dateA = new Date(a.date.split('/').reverse().join('-'));
                        const dateB = new Date(b.date.split('/').reverse().join('-'));
                        return dateB - dateA;
                    });

                } catch (e) {
                    console.error("Erreur JSON :", e);
                    return;
                }

                cycleSpan.textContent = '#' + button.getAttribute('data-cycle');
                tableBody.innerHTML = '';

                if (collectes.length > 0) {
                    collectes.forEach(collecte => {
                        const row = `
                            <tr>
                                <td class="ps-3">
                                    <div class="small fw-bold">${collecte.date.split(' ')[0]}</div>
                                    <div class="small text-muted">${collecte.date.split(' ')[1]}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2 bg-light rounded-circle text-center" style="width: 25px; height: 25px; line-height: 25px;">
                                            <i class="bi bi-person text-primary" style="font-size: 0.8rem;"></i>
                                        </div>
                                        <small class="fw-semibold text-truncate" style="max-width: 120px;">${collecte.agent}</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-info-subtle text-info border border-info px-2">
                                        ${collecte.pointage}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold text-dark">${collecte.montant}</span>
                                </td>
                            </tr>`;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted">Aucune donnée disponible.</td></tr>';
                }
            });
        }

        // --- LOGIQUE RETRAIT : AUTOMATISATION ---
        const cycleSelect = document.getElementById('cycle_select');
        const montantInput = document.getElementById('montant_total');
        const commissionInput = document.getElementById('commission_input');
        const radioTotal = document.getElementById('retrait_total');
        const radioPartiel = document.getElementById('retrait_partiel');
        const brutView = document.getElementById('brut_view');
        const netView = document.getElementById('net_view');
        const submitBtn = document.querySelector('#modalRetrait button[type="submit"]');

        function updateCalculations() {
            if (!cycleSelect) return;
            const selected = cycleSelect.options[cycleSelect.selectedIndex];
            
            if (!selected || selected.value === "") {
                brutView.innerText = "0";
                netView.innerText = "0";
                if (submitBtn) submitBtn.disabled = true;
                return;
            }

            const soldeBrut = parseFloat(selected.getAttribute('data-solde')) || 0;
            const commission = parseFloat(selected.getAttribute('data-commission')) || 0;
            const soldeNet = soldeBrut - commission;

            // Affichage permanent de la commission
            commissionInput.value = Math.round(commission);
            brutView.innerText = new Intl.NumberFormat('fr-FR').format(soldeBrut);
            
            // Reset couleur par défaut
            netView.classList.remove('text-warning');
            netView.classList.add('text-success');
            netView.innerText = new Intl.NumberFormat('fr-FR').format(soldeNet);

            if (radioTotal.checked) {
                montantInput.value = Math.round(soldeNet);
                montantInput.readOnly = true; 
                montantInput.classList.add('bg-light');
                montantInput.classList.remove('is-invalid');
                if (submitBtn) submitBtn.disabled = false;
            } else {
                montantInput.readOnly = false; 
                montantInput.classList.remove('bg-light');
                montantInput.placeholder = "Saisir montant (Max: " + soldeNet + ")";
                
                if (montantInput.value == Math.round(soldeNet)) {
                    montantInput.value = "";
                }
            }
            
            montantInput.max = soldeNet;
            validateAmount(soldeNet);
        }

        function validateAmount(maxAllowed) {
            const montantSaisi = parseFloat(montantInput.value) || 0;

            if (montantSaisi > maxAllowed) {
                montantInput.classList.add('is-invalid');
                netView.innerText = "Dépassement du solde !";
                netView.classList.replace('text-success', 'text-danger');
                if(submitBtn) submitBtn.disabled = true;
            } else {
                montantInput.classList.remove('is-invalid');
                netView.classList.replace('text-danger', 'text-success');
                if(submitBtn) submitBtn.disabled = false;

                // Alerte si on vide tout en mode partiel
                if (radioPartiel.checked && montantSaisi > 0 && montantSaisi >= maxAllowed) {
                    netView.innerText = "Total saisi : le cycle sera clôturé.";
                    netView.classList.replace('text-success', 'text-warning');
                } else {
                    netView.innerText = new Intl.NumberFormat('fr-FR').format(maxAllowed);
                }
            }
        }

        // Écouteurs pour les calculs
        if (cycleSelect) {
            cycleSelect.addEventListener('change', updateCalculations);
            radioTotal.addEventListener('change', updateCalculations);
            radioPartiel.addEventListener('change', updateCalculations);
            
            // Écouteur pour la validation en temps réel de la saisie
            montantInput.addEventListener('input', function() {
                const selected = cycleSelect.options[cycleSelect.selectedIndex];
                if (selected && selected.value !== "") {
                    const soldeNet = parseFloat(selected.getAttribute('data-solde')) - parseFloat(selected.getAttribute('data-commission'));
                    validateAmount(soldeNet);
                }
            });
        }

        // Refresh auto à l'ouverture
        const myModal = document.getElementById('modalRetrait');
        if (myModal) {
            myModal.addEventListener('shown.bs.modal', updateCalculations);
        }
    });
</script>

@endsection