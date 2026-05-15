@extends('pwa.layouts.app')

@section('content')
<div class="container py-3" style="padding-bottom: 80px;"> {{-- Marge pour la nav du bas --}}
    
    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-wallet2 me-2"></i>Mes Gains</h5>
    </div>

    {{-- Résumé des gains (Cards Soft) --}}
    <div class="row g-2 mb-4">
        <div class="col-6">
            <div class="card bg-white border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <i class="bi bi-hourglass-split text-primary fs-4 d-block mb-1"></i>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">En attente</small>
                    <span class="fw-bold text-primary" id="total-attente" style="font-size: 1.1rem;">0 F</span>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-white border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">Dernier Payé</small>
                    <span class="fw-bold text-success" id="dernier-paiement" style="font-size: 1.1rem;">0 F</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation par onglets (Pills arrondis) --}}
    <div class="nav nav-pills mb-3 bg-light p-1 rounded-4 shadow-sm" id="pills-tab" role="tablist">
        <li class="nav-item flex-grow-1" role="presentation">
            <button class="nav-link active rounded-4 border-0 w-100 py-2" id="tab-attente" data-bs-toggle="pill" data-bs-target="#view-attente" type="button">
                À venir
            </button>
        </li>
        <li class="nav-item flex-grow-1" role="presentation">
            <button class="nav-link rounded-4 border-0 w-100 py-2" id="tab-historique" data-bs-toggle="pill" data-bs-target="#view-historique" type="button">
                Historique
            </button>
        </li>
    </div>

    {{-- Zone de contenu --}}
    <div class="tab-content mt-3" id="pills-tabContent">
        {{-- Liste "À venir" --}}
        <div class="tab-pane fade show active" id="view-attente" role="tabpanel">
            <div id="list-bonus-attente" class="row g-2">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm"></div>
                    <p class="text-muted small mt-2">Chargement des gains...</p>
                </div>
            </div>
        </div>

        {{-- Liste "Historique" --}}
        <div class="tab-pane fade" id="view-historique" role="tabpanel">
            <div id="list-paiements-valides" class="row g-2">
                {{-- Injecté par JS --}}
            </div>
        </div>
    </div>
</div>

<script type="module">
    import { getAgentDB, populateDatabase, DBManager, db } from '/js/db-manager.js';
    document.addEventListener('DOMContentLoaded', async () => {
        
        await refreshFinanceDashboard();
    });

    async function refreshFinanceDashboard() {
        const db = getAgentDB();
        if (!db) return;

        try {
            
            // 1. Récupération des bonus en attente (Dexie)
            const bonus = await db.bonus_en_attente.orderBy('date_attribution').reverse().toArray();
            const total = bonus.reduce((sum, b) => sum + Number(b.montant), 0);
            
            const totalAttenteEl = document.getElementById('total-attente');
            if (totalAttenteEl) {
                totalAttenteEl.innerText = `${total.toLocaleString()} F`;
            }

            const listAttente = document.getElementById('list-bonus-attente');
            if (listAttente) {
                listAttente.innerHTML = bonus.map(b => `
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 mb-2">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi ${b.cycle_id ? 'bi-arrow-repeat' : 'bi-award'} text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold small text-capitalize">${b.motif || 'Commission / Gratification'}</h6>
                                    <small class="text-muted" style="font-size: 0.7rem;">${new Date(b.date_attribution).toLocaleDateString()}</small>
                                </div>
                                <div class="fw-bold text-primary">${Number(b.montant).toLocaleString()} F</div>
                            </div>
                        </div>
                    </div>
                `).join('') || '<p class="text-center text-muted small py-4">Aucun gain en attente.</p>';
            }

            // 2. Récupération des paiements validés
            const paiements = await db.paiements_valides.orderBy('created_at').reverse().toArray();
            
            const dernierPaiementEl = document.getElementById('dernier-paiement');
            if (paiements.length > 0 && dernierPaiementEl) {
                dernierPaiementEl.innerText = `${Number(paiements[0].montant_total).toLocaleString()} F`;
            }

            const listHistorique = document.getElementById('list-paiements-valides');
            if (listHistorique) {
                listHistorique.innerHTML = paiements.map(p => `
                    <div class="col-12 mb-2" onclick="voirDetailPaiement('${p.reference}')" style="cursor: pointer;">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                   ${p.type === 'deboursement' ? '<span class="badge bg-light text-success border-0 small text-capitalize">Déboursé</span>' : '<span class="badge bg-light text-danger border-0 small text-capitalize">Rejeté</span>'}
                                    <small class="text-muted" style="font-size: 0.7rem;">${new Date(p.created_at).toLocaleDateString()}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold small text-secondary">${p.reference}</span>
                                    <span class="fw-bold text-success">${Number(p.montant_total).toLocaleString()} F</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('') || '<p class="text-center text-muted small py-4">Aucun historique disponible.</p>';
            }

        } catch (error) {
            console.error("Erreur lors du chargement des gains :", error);
        }
    }

    /**
     * Affiche le détail d'un paiement et de ses bonus associés (imbriqués)
     */
    window.voirDetailPaiement = voirDetailPaiement;
    async function voirDetailPaiement(ref) {
        try {
            const db = getAgentDB();
            if (!db) return;

            // Recherche du paiement par sa référence unique
            const paiement = await db.paiements_valides.where('reference').equals(ref).first();
            
            // Sécurité si le paiement ou les bonus imbriqués n'existent pas
            if (!paiement || !paiement.bonuses || paiement.bonuses.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Détails indisponibles',
                    text: 'Aucune ligne de bonus détaillée n\'est rattachée à ce paiement.',
                    confirmButtonText: 'Fermer',
                    confirmButtonColor: '#6c757d'
                });
                return;
            }

            let detailHtml = '<div class="list-group list-group-flush text-start">';
            
            // Parcours des bonus imbriqués (parfaitement visibles sur ton screen)
            paiement.bonuses.forEach(b => {
               
                detailHtml += `
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 small border-bottom">
                        <div>
                            <span class="d-block fw-semibold text-dark">${b.motif || 'Commission Automatique'}</span>
                            <small class="text-muted" style="font-size: 0.65rem;">Attribué le : ${new Date(b.date_attribution).toLocaleDateString()}</small>
                        </div>
                        <span class="fw-bold text-end align-self-center text-primary ${b.statut === 'refuse' ? 'text-danger' : ''}">${b.statut === 'refuse' ? '-' : '+'} ${Number(b.montant).toLocaleString()} F</span>
                    </div>`;
            });
            
            detailHtml += '</div>';

            // Affichage propre via SweetAlert2
            Swal.fire({
                title: `<div class="fs-5 fw-bold text-secondary">Référence ${ref}</div>`,
                html: detailHtml,
                confirmButtonText: 'Fermer la vue',
                confirmButtonColor: '#0d6efd',
                customClass: { popup: 'rounded-4' }
            });

        } catch (e) {
            console.error("Erreur lors de l'ouverture du détail paiement :", e);
        }
    }
</script>
@endsection


