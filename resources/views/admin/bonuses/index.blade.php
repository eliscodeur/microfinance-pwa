@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-cash-stack text-success me-2"></i>
            Gestion des Bonus et Commissions
        </h2>
        <button type="button" class="btn btn-success shadow-sm" onclick="ouvrirFormulaireBonus()">
            <i class="bi bi-plus-circle me-2"></i>Attribuer un Bonus
        </button>
    </div>

    {{-- Statistiques dynamiques basées sur l'objet $stats du contrôleur --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small fw-bold text-uppercase">Total Global en Attente</p>
                    <h3 class="text-success fw-black mb-0">{{ number_format($stats->montant_total_attente, 0, ',', ' ') }} F</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 border-start border-primary border-4 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small fw-bold text-uppercase">Nombre de Lignes</p>
                    <h3 class="text-primary fw-black mb-0">{{ $stats->nombre_lignes_total }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 border-start border-info border-4 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small fw-bold text-uppercase">Agents à Régler</p>
                    <h3 class="text-info fw-black mb-0">{{ $stats->nombre_agents_concernes }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Table des bonus --}}
    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom-0">
            <h5 class="mb-0 fw-bold text-dark">Récapitulatif par Agent</h5>
            <div class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-filter-left me-1"></i> {{ $bonusesByAgent->count() }} Agent(s) actif(s)
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 border-0 text-muted small text-uppercase">Agent</th>
                            <th class="text-center border-0 text-muted small text-uppercase">Commissions (Cycles)</th>
                            <th class="text-center border-0 text-muted small text-uppercase">Bonus (Manuels)</th>
                            <th class="text-center border-0 text-muted small text-uppercase">Cumul Attendu</th>
                            <th class="text-end pe-4 border-0 text-muted small text-uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bonusesByAgent as $data)
                        <tr>
                            {{-- Identité de l'Agent --}}
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-soft-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 40px; height: 40px; background: #eef2ff;">
                                        {{ strtoupper(substr($data->agent->nom, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ strtoupper($data->agent->nom) }} {{ $data->agent->prenom }}</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            ID: <span class="badge bg-light text-dark border-0 p-0">{{ $data->agent->code_agent ?? 'N/A' }}</span>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            {{-- Commissions Automatiques --}}
                            <td class="text-center">
                                <span class="badge rounded-pill {{ $data->total_commissions > 0 ? 'bg-info-subtle text-info border border-info' : 'bg-light text-muted border' }} px-3">
                                    {{ number_format($data->total_commissions, 0, ',', ' ') }} F
                                </span>
                            </td>

                            {{-- Bonus Manuels --}}
                            <td class="text-center">
                                <span class="badge rounded-pill {{ $data->total_manuels > 0 ? 'bg-warning-subtle text-warning border border-warning' : 'bg-light text-muted border' }} px-3">
                                    {{ number_format($data->total_manuels, 0, ',', ' ') }} F
                                </span>
                            </td>

                            {{-- Total Global --}}
                            <td class="text-center">
                                <div class="fw-bolder text-primary" style="font-size: 1.05rem;">{{ number_format($data->total_global, 0, ',', ' ') }} F</div>
                                <div class="text-muted" style="font-size: 0.65rem;">{{ $data->nb_items }} opération(s)</div>
                            </td>

                            {{-- Actions groupées --}}
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    {{-- Détails --}}
                                    <button type="button" 
                                        class="btn btn-sm btn-outline-primary show-bonus-details" 
                                        data-agent-id="{{ $data->agent_id }}"
                                        data-agent-name="{{ $data->agent->nom }}"
                                        data-total="{{ number_format($data->total_global, 0, ',', ' ') }}"
                                        data-items='@json($data->items)'>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    {{-- Validation groupée --}}
                                    <form action="{{ route('admin.bonuses.bulk-approve') }}" method="POST" class="d-inline bulk-approve-form">
                                        @csrf
                                        <input type="hidden" name="agent_id" value="{{ $data->agent_id }}">
                                        <button type="button" class="btn btn-success btn-sm px-3 ms-1 bulk-approve-btn" 
                                                data-montant="{{ number_format($data->total_global, 0, ',', ' ') }}">
                                            <i class="bi bi-check2-circle me-1"></i> Valider
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="opacity-50">
                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                    <p class="mb-0">Aucun paiement ou commission en attente.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /**
         * 1. INITIALISATION DES ÉCOUTEURS (Boutons directs dans le tableau)
         */
        
        // Validation simple (Approve)
        document.querySelectorAll('.confirm-approve').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.approve-form');
                const montant = this.dataset.montant;
                confirmAndSubmit('Valider le paiement ?', '#198754', form.action, null, `Souhaitez-vous confirmer le paiement de ${montant} F ? Cette action créera un reçu officiel.`);
            });
        });

        // Rejet simple (Reject)
        document.querySelectorAll('.confirm-reject').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.reject-form');
                confirmAndSubmit('Refuser ce bonus ?', '#d33', form.action, null, "Le bonus sera marqué comme refusé et archivé avec un montant de 0 F. Cette action est irréversible.", 'warning');
            });
        });

        // Validation globale par ligne (Bulk Approve direct)
        document.querySelectorAll('.bulk-approve-btn').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.bulk-approve-form');
                const montant = this.dataset.montant;
                confirmAndSubmit('Confirmer le paiement global', '#198754', form.action, { agent_id: form.querySelector('[name="agent_id"]').value }, `Voulez-vous valider le paiement total de ${montant} F pour cet agent ?`, 'info');
            });
        });

        /**
         * 2. GESTION DU MODAL DE DÉTAILS (SWEETALERT DYNAMIQUE)
         */
        document.querySelectorAll('.show-bonus-details').forEach(button => {
            button.addEventListener('click', function() {
                const d = this.dataset;
                const items = JSON.parse(d.items);

                let tableRows = items.map(item => {
                    const date = new Date(item.date_attribution).toLocaleDateString('fr-FR');
                    const badge = item.cycle_id 
                        ? '<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill small"><i class="bi bi-arrow-repeat me-1"></i> Auto</span>'
                        : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small"><i class="bi bi-star-fill me-1"></i> Manuel</span>';
                    
                    const montant = parseInt(item.montant).toLocaleString('fr-FR');

                    return `
                        <tr class="align-middle">
                            <td class="ps-3 small text-secondary">${date}</td>
                            <td>${badge}</td>
                            <td class="text-start">
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">${item.motif}</div>
                                ${item.cycle_id ? `<small class="text-muted">ID: #${item.cycle_id}</small>` : ''}
                            </td>
                            <td class="text-center fw-bold text-primary">${montant} F</td>
                            <td class="pe-3">
                                <div class="d-flex gap-1 justify-content-end">
                                    <button onclick="executeAction('approve', ${item.id})" class="btn btn-sm btn-outline-success border-2 p-1"><i class="bi bi-check-lg"></i></button>
                                    <button onclick="executeAction('reject', ${item.id})" class="btn btn-sm btn-outline-danger border-2 p-1"><i class="bi bi-x-circle"></i></button>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                Swal.fire({
                    title: `<i class="bi bi-person-circle me-2"></i> Détails : ${d.agentName}`,
                    width: '850px',
                    html: `
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light border rounded mb-3 text-start">
                            <div>
                                <h3 class="fw-bold text-success mb-0">${d.total} F</h3>
                                <p class="text-muted mb-0 small">Total cumulé en attente</p>
                            </div>
                            <button onclick="executeBulkApprove(${d.agentId})" class="btn btn-success shadow-sm">
                                <i class="bi bi-check2-all me-1"></i> Tout Valider & Payer
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-hover border-top">
                                <thead class="table-light text-uppercase" style="font-size: 0.75rem;">
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th>Type</th>
                                        <th class="text-start">Motif</th>
                                        <th>Montant</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>${tableRows}</tbody>
                            </table>
                        </div>`,
                    showConfirmButton: false,
                    showCloseButton: true,
                    borderRadius: '15px',
                    customClass: { title: 'fs-5 fw-bold' }
                });
            });
        });
    });

    /**
     * 3. LOGIQUE MÉTIER & SOUMISSION (FONCTIONS GLOBALES)
     */

    // Déclencheur pour les actions individuelles dans le modal
    function executeAction(type, id) {
        const config = {
            approve: { title: 'Valider ce bonus ?', color: '#198754', url:`/admin/bonuses/${id}/approve-single`, icon: 'question' },
            reject: { title: 'Refuser ce bonus ?', color: '#d33', url: `/admin/bonuses/reject/${id}`, icon: 'warning' }
        };
        const c = config[type];
        confirmAndSubmit(c.title, c.color, c.url, null, '', c.icon);
    }

    // Déclencheur pour la validation groupée dans le modal
    function executeBulkApprove(agentId) {
        confirmAndSubmit('Tout valider et payer ?', '#198754', '/admin/bonuses/bulk-approve', { agent_id: agentId }, 'Cette action validera tous les bonus en attente pour cet agent.', 'question');
    }

    /**
     * MOTEUR DE SOUMISSION UNIQUE
     * Gère la création du formulaire, le CSRF, et les données additionnelles.
     */
    function confirmAndSubmit(title, color, url, data = null, text = '', icon = 'question') {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: color,
            confirmButtonText: 'Confirmer',
            cancelButtonText: 'Annuler',
            borderRadius: '15px',
            backdrop: icon === 'info' || icon === 'question' ? `rgba(25, 135, 84, 0.1)` : `rgba(211, 33, 33, 0.1)`
        }).then((result) => {
            if (result.isConfirmed) {
                // Affichage du loader
                Swal.fire({
                    title: 'Traitement en cours...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                // Construction du formulaire dynamique
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                
                // CSRF Token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrf);

                // Données supplémentaires (ex: agent_id)
                if(data) {
                    Object.keys(data).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden'; 
                        input.name = key; 
                        input.value = data[key];
                        form.appendChild(input);
                    });
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    function ouvrirFormulaireBonus() {
        Swal.fire({
            title: '<div class="fs-4 fw-bold text-success"><i class="bi bi-award me-2"></i>Attribuer un Bonus</div>',
            html: `
                <div class="text-start p-2">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Agent Collecteur <span class="text-danger">*</span></label>
                        <select id="swal-agent-id" class="form-select rounded-3">
                            <option value="">-- Sélectionner l'agent --</option>
                            @foreach($agents ?? [] as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->nom }} ({{ $agent->matricule ?? $agent->code_agent }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Montant (F CFA) <span class="text-danger">*</span></label>
                        <input type="number" id="swal-montant" class="form-control rounded-3" placeholder="Ex: 5000" min="1">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold small text-secondary">Motif ou Justification <span class="text-danger">*</span></label>
                        <textarea id="swal-motif" class="form-control rounded-3" rows="3" placeholder="Ex: Prime de performance mensuelle..."></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Valider l\'attribution',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#198754', 
            cancelButtonColor: '#6c757d',
            customClass: {
                popup: 'rounded-4 shadow-lg',
                confirmButton: 'rounded-3 px-3',
                cancelButton: 'rounded-3 px-3'
            },
            preConfirm: () => {
                const agentId = document.getElementById('swal-agent-id').value;
                const montant = document.getElementById('swal-montant').value;
                const motif = document.getElementById('swal-motif').value;

                if (!agentId) {
                    Swal.showValidationMessage("Veuillez sélectionner un agent collecteur.");
                    return false;
                }
                if (!montant || Number(montant) <= 0) {
                    Swal.showValidationMessage("Veuillez saisir un montant valide.");
                    return false;
                }
                if (!motif.trim()) {
                    Swal.showValidationMessage("Un motif est obligatoire pour attribuer ce bonus.");
                    return false;
                }

                return { 
                    agent_id: agentId, 
                    montant: montant, 
                    motif: motif,
                    statut: 'en_attente', // Forcé par défaut à la création
                    date_attribution: new Date().toISOString().split('T')[0] // Optionnel : date du jour AAAA-MM-JJ
                };
            }
        }).then(async (result) => {
            if (result.isConfirmed && result.value) {
                
                Swal.fire({
                    title: 'Enregistrement...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                try {
                    // Utilisation de la route générée par ton Route::resource
                    const response = await fetch("{{ route('admin.bonuses.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(result.value)
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Enregistré !',
                            text: data.message || 'Le bonus a été attribué avec succès.',
                            confirmButtonColor: '#198754',
                            customClass: { popup: 'rounded-4' }
                        }).then(() => {
                            window.location.reload(); 
                        });
                    } else {
                        throw new Error(data.message || 'Une erreur est survenue lors de la sauvegarde.');
                    }

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: error.message,
                        confirmButtonColor: '#0d6efd',
                        customClass: { popup: 'rounded-4' }
                    });
                }
            }
        });
    }
</script>

@endsection