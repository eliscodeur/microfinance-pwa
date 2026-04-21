@extends('pwa.layouts.app')

@section('content')
<div class="container mt-3">
    <h4 class="fw-bold mb-3 text-dark">Cycles par Client</h4>
    
    <div class="input-group mb-4 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <span class="input-group-text border-0 bg-white"><i class="bi bi-search"></i></span>
        <input type="text" id="inputSearch" class="form-control border-0 p-3" 
           placeholder="Rechercher client ou n° carnet..." onkeyup="afficherCyclesRegroupes()">
    </div>

    <div id="cycles-master-container" class="pb-5">
    </div>
</div>
<div class="modal fade" id="modalModifCycle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>Modifier le cycle</h6>
                <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-cycle-id">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Montant journalier (F)</label>
                    <input type="number" id="edit-montant" class="form-control border-0 bg-light" style="border-radius: 12px;" oninput="validerChampsModif()">
                    <div id="error-montant" class="text-danger mt-1 d-none" style="font-size: 0.7rem;">
                        <i class="bi bi-exclamation-circle me-1"></i> Minimum 100 F requis.
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted">Date de début (Fixe)</label>
                    <input type="date" id="edit-date" class="form-control form-control-lg border-0 bg-light-disabled" 
                        style="border-radius: 12px; font-size: 1rem; cursor: not-allowed;" 
                        disabled>
                    <small class="text-muted" style="font-size: 0.6rem;">
                        <i class="bi bi-info-circle me-1"></i> La date ne peut pas être modifiée après création.
                    </small>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 flex-column">
                <button id="btn-save-cycle" type="button" onclick="enregistrerModifCycle()" class="btn btn-primary w-100 py-2 mb-2" 
                        style="border-radius: 12px; font-weight: 600; background: var(--nana-blue); border: none;">
                    Enregistrer
                </button>
                <button type="button" class="btn btn-light w-100 py-2 text-muted" data-bs-dismiss="modal" 
                        style="border-radius: 12px; font-weight: 600;">Annuler</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalSupprCycle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-body text-center pt-4 pb-3">
                <div class="mb-3">
                    <i class="bi bi-exclamation-octagon text-danger" style="font-size: 3rem; opacity: 0.8;"></i>
                </div>
                <h6 class="fw-bold text-dark">Supprimer ce cycle ?</h6>
                <p class="text-muted px-2" style="font-size: 0.75rem;">
                    Cette action effacera définitivement le cycle et ses paramètres. Cette opération est irréversible.
                </p>
                <input type="hidden" id="suppr-cycle-id">
            </div>
            <div class="modal-footer border-0 pt-0 flex-column">
                <button type="button" onclick="executerSuppression()" class="btn btn-danger w-100 py-2 mb-2" 
                        style="border-radius: 12px; font-weight: 600; background-color: #dc3545; border: none;">
                    Oui, supprimer
                </button>
                <button type="button" class="btn btn-light w-100 py-2 text-muted" data-bs-dismiss="modal" 
                        style="border-radius: 12px; font-weight: 600;">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>
<style>
    /* Style pour les inputs du modal */
    #modalModifCycle .form-control:focus {
        background-color: #fff !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        border: 1px solid var(--nana-blue) !important;
    }

    /* Modal mobile friendly */
    @media (max-width: 576px) {
        .modal-dialog-centered {
            margin: 1rem;
        }
        .modal-content {
            bottom: 0;
            border-radius: 25px 25px 25px 25px !important;
        }
    }
    .client-group-header {
        background: #e9ecef;
        padding: 10px 15px;
        border-radius: 10px;
        font-weight: bold;
        color: var(--nana-blue);
        margin-top: 20px;
    }
    .cycle-card {
        border-left: 5px solid var(--nana-blue);
        margin-left: 10px;
        margin-top: 10px;
    }
    .btn-action {
        width: 35px;
        height: 35px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
    .bg-soft-green { background-color: #e6f4ea; color: #1e7e34; }
    .bg-soft-primary { background-color: #e7f0ff; color: #0d6efd; }
    .client-group { border-left: 2px solid transparent; }
    .client-group:focus-within { border-left-color: var(--nana-blue); }
    .card { transition: all 0.2s ease; }
</style>
<script type="module">
     import { db } from '/js/db-manager.js'; 
</script>
<script>
    async function afficherCyclesRegroupes() {
        const container = document.getElementById('cycles-master-container');
        const search = document.getElementById('inputSearch').value.toLowerCase();
        container.innerHTML = '';

        // 1. Récupération des données Dexie
        const [clients, carnets, cycles, collectes] = await Promise.all([
            db.clients.toArray(),
            db.carnets.toArray(),
            db.cycles.toArray(),
            db.collectes.toArray()
        ]);

        // 2. Tri par date (les plus récents en haut)
        const cyclesTries = cycles.sort((a, b) => new Date(b.date_debut) - new Date(a.date_debut));

        clients.forEach(client => {
            const matchClient = client.nom.toLowerCase().includes(search);
            const clientCarnets = carnets.filter(car => car.client_id === client.id);
            
            if (cycles.some(cy => cy.client_id === client.id)) {
                let clientHtml = '';
                let clientAffiche = false;

                clientCarnets.forEach(carnet => {
                    const numCarnet = carnet.numero;
                    const matchCarnet = numCarnet.toLowerCase().includes(search);
                    const carnetCycles = cyclesTries.filter(cy => cy.carnet_id === carnet.id);

                    if (carnetCycles.length > 0 && (matchClient || matchCarnet)) {
                        clientAffiche = true;
                        clientHtml += `
                            <div class="card border-0 shadow-sm mb-2" style="border-radius: 12px; background: #fff;">
                                <div class="card-header border-0 py-1 px-3 d-flex justify-content-between align-items-center" style="background: #f8f9fa; border-radius: 12px 12px 0 0;">
                                    <span class="fw-bold" style="font-size: 0.65rem; color: #888; letter-spacing: 0.3px;">
                                        <i class="bi bi-journal-text me-1"></i>${numCarnet}
                                    </span>
                                </div>
                                <div class="card-body p-0">
                        `;

                        carnetCycles.forEach(cycle => {
                            // --- CALCUL FINANCIER ---
                            const collectesCycle = collectes.filter(col => col.cycle_id === cycle.id);
                            const brut = collectesCycle.reduce((sum, col) => sum + parseFloat(col.montant || 0), 0);
                            const mise = parseFloat(cycle.montant_journalier || 0);
                            const net = brut > mise ? brut - mise : brut; 
                            
                            const estTermine = cycle.statut === 'termine' || cycle.statut === 'vrai';
                            const afficherSomme = estTermine && (!cycle.retire_at || cycle.retire_at === "null" || cycle.retire_at === null);
                            const nbrCol = collectesCycle.length;
                            const peutAgir = (nbrCol === 0 && cycle.synced === 0);

                            clientHtml += `
                                <div class="px-3 py-2 ${carnetCycles.indexOf(cycle) !== carnetCycles.length - 1 ? 'border-bottom' : ''}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        
                                        <a href="/pwa/pointage-shell?carnet_id=${carnet.id}" class="text-decoration-none d-flex flex-column" style="flex: 1;">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="fw-bold text-dark" style="font-size: 0.95rem;">${cycle.montant_journalier} F</span>
                                                ${estTermine 
                                                    ? '<span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.55rem;">Terminé <i class="bi bi-eye ms-1"></i></span>' 
                                                    : '<span class="badge rounded-pill bg-soft-green text-success" style="font-size: 0.55rem; border: 1px solid #c3e6cb;">En cours <i class="bi bi-chevron-right ms-1"></i></span>'
                                                }
                                            </div>
                                            <div class="text-muted" style="font-size: 0.65rem;">
                                                <i class="bi bi-calendar3 me-1"></i>${new Date(cycle.date_debut).toLocaleDateString()}
                                            </div>
                                        </a>

                                        <div class="text-end" style="min-width: 80px;">
                                            ${peutAgir && !estTermine ? `
                                                <div class="d-flex gap-1 mb-1 justify-content-end">
                                                    <button onclick="event.preventDefault(); ouvrirModif(${cycle.id})" class="btn btn-sm p-1 text-primary bg-light border-0" style="border-radius: 6px;"><i class="bi bi-pencil-square"></i></button>
                                                    <button onclick="event.preventDefault(); supprimerCycle(${cycle.id})" class="btn btn-sm p-1 text-danger bg-light border-0" style="border-radius: 6px;"><i class="bi bi-trash3"></i></button>
                                                </div>
                                            ` : ''}
                                            
                                            ${afficherSomme ? `
                                                <div class="d-flex flex-column align-items-end">
                                                    <div class="badge bg-soft-primary text-primary" style="font-size: 0.75rem; font-weight: 800; border: 1px solid #b3d1ff;">
                                                        Net: ${net} F
                                                    </div>
                                                    <small class="text-danger" style="font-size: 0.5rem; font-style: italic;">(-${mise} F comm.)</small>
                                                </div>
                                            ` : (estTermine ? '<i class="bi bi-check2-all text-success" title="Retrait effectué"></i>' : '')}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        clientHtml += `</div></div>`;
                    }
                });

                if (clientAffiche) {
                    container.innerHTML += `
                        <div class="client-group mb-3 px-1">
                            <div class="ps-1 mb-1">
                                <small class="fw-bold text-muted text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                                    <i class="bi bi-person me-1"></i>${client.nom}
                                </small>
                            </div>
                            ${clientHtml}
                        </div>
                    `;
                }
            }
        });

        if (container.innerHTML === '') {
            container.innerHTML = `<div class="text-center py-5 text-muted small">Aucun cycle trouvé pour "${search}"</div>`;
        }
    }

    // Appeler la fonction au chargement
    document.addEventListener('DOMContentLoaded', afficherCyclesRegroupes);
    // Variable globale pour le modal Bootstrap
    let instanceModalModif;

    // 1. Ouvrir le modal et charger les infos
    async function ouvrirModif(id) {
        try {
            const cycle = await db.cycles.get(id);
            if (!cycle) return;

            document.getElementById('edit-cycle-id').value = cycle.id;
            document.getElementById('edit-montant').value = cycle.montant_journalier;
            
            // --- DYNAMISME DE LA DATE ---
            // On propose la date du jour par défaut
            const aujourdhui = new Date().toISOString().split('T')[0];
            const elDate = document.getElementById('edit-date');
            
            if (elDate) {
                // On met la date du jour par défaut pour gagner du temps
                elDate.value = aujourdhui; 
            }

            // Cache les messages d'erreur au départ
            document.getElementById('error-montant')?.classList.add('d-none');
            document.getElementById('error-date-past')?.classList.add('d-none');
            document.getElementById('error-date-conflict')?.classList.add('d-none');

            const modalEl = document.getElementById('modalModifCycle');
            if (modalEl) {
                const myModal = new bootstrap.Modal(modalEl);
                myModal.show();
                // On valide immédiatement pour voir si "Aujourd'hui" est une date valide
                setTimeout(() => validerChampsModif(), 150);
            }
        } catch (error) {
            console.error(error);
        }
    }

    async function enregistrerModifCycle() {
        const id = parseInt(document.getElementById('edit-cycle-id').value);
        const montant = parseFloat(document.getElementById('edit-montant').value);
        const dateStr = document.getElementById('edit-date').value;

        try {
            await db.cycles.update(id, {
                montant_journalier: montant,
                date_debut: dateStr
            });

            // Fermer le modal
            const modalEl = document.getElementById('modalModifCycle');
            bootstrap.Modal.getInstance(modalEl).hide();
            
            // Rafraîchir la liste
            await afficherCyclesRegroupes();
        } catch (e) {
            console.error("Erreur update cycle:", e);
        }
    }

    async function validerChampsModif() {
        const id = parseInt(document.getElementById('edit-cycle-id')?.value);
        const montant = parseFloat(document.getElementById('edit-montant')?.value) || 0;
        const dateStr = document.getElementById('edit-date')?.value;
        const btnSave = document.getElementById('btn-save-cycle');

        let isMontantOk = montant >= 100;
        let isDateOk = true;

        // Gestion de l'affichage de l'erreur montant
        document.getElementById('error-montant')?.classList.toggle('d-none', isMontantOk);

        if (dateStr && id) {
            const cycleActuel = await db.cycles.get(id);
            const nouvelleDate = new Date(dateStr);
            nouvelleDate.setHours(0,0,0,0);

            const ancienneDateInitiale = new Date(cycleActuel.date_debut);
            ancienneDateInitiale.setHours(0,0,0,0);

            // 1. Erreur si on tente de mettre une date plus vieille que celle déjà enregistrée
            const isPast = nouvelleDate < ancienneDateInitiale;
            document.getElementById('error-date-past')?.classList.toggle('d-none', !isPast);
            if (isPast) isDateOk = false;

            // 2. Erreur si conflit avec un autre cycle du carnet
            const cyclesDuCarnet = await db.cycles.where('carnet_id').equals(cycleActuel.carnet_id).toArray();
            const conflit = cyclesDuCarnet.find(c => {
                if (c.id === id) return false;
                const dC = new Date(c.date_debut);
                dC.setHours(0,0,0,0);
                return dC > nouvelleDate;
            });

            const hasConflict = !!conflit;
            document.getElementById('error-date-conflict')?.classList.toggle('d-none', !hasConflict);
            if (hasConflict) isDateOk = false;
        }

        // Bouton actif seulement si TOUT est bon
        if (btnSave) {
            btnSave.disabled = !(isMontantOk && isDateOk);
            btnSave.style.opacity = btnSave.disabled ? "0.5" : "1";
        }
    }

    let instanceModalSuppr;

    /**
     * 1. Ouvre le modal de confirmation
     */
    function supprimerCycle(id) {
        // On stocke l'ID dans le champ caché du modal
        const elId = document.getElementById('suppr-cycle-id');
        if (elId) elId.value = id;

        // Affichage du modal
        const modalEl = document.getElementById('modalSupprCycle');
        if (modalEl) {
            instanceModalSuppr = new bootstrap.Modal(modalEl);
            instanceModalSuppr.show();
        }
    }

    /**
     * 2. Exécute la suppression réelle dans Dexie
     */
    async function executerSuppression() {
        const id = parseInt(document.getElementById('suppr-cycle-id').value);
        
        if (!id) return;

        try {
            // Suppression dans la base de données
            await db.cycles.delete(id);

            // Fermeture du modal
            if (instanceModalSuppr) {
                instanceModalSuppr.hide();
            }

            // Rafraîchissement de la liste
            await afficherCyclesRegroupes();

            // Petit feedback visuel console ou toast si tu en as un
            console.log(`Cycle ${id} supprimé.`);
            
        } catch (error) {
            console.error("Erreur lors de la suppression:", error);
            alert("Impossible de supprimer ce cycle.");
        }
    }
    
</script>
@endsection