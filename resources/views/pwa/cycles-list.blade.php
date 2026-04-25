@extends('pwa.layouts.app')

@section('content')
<div class="container-fluid px-0 mt-2">
    <div class="sticky-top bg-white pt-3 pb-2 shadow-sm mb-3" style="z-index: 1020;">
        <div class="container">
            <h4 class="fw-bold mb-3 text-dark small">Cycles par Client</h4>
            
            <div class="position-relative shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="inputSearch" class="form-control border-0 p-3 ps-5 bg-light" 
                       placeholder="Rechercher client ou n° carnet..." 
                       onkeyup="gererCroixCycles(); afficherCyclesRegroupes()"
                       style="border-radius: 15px; font-size: 0.95rem;">
                
                <button id="btnViderCycles" onclick="viderRechercheCycles()" 
                        class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted d-none" 
                        style="z-index: 10; padding-right: 15px;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="cycles-master-container" class="pb-5">
            </div>
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
    window.gererCroixCycles = function() {
        const input = document.getElementById('inputSearch');
        const btnX = document.getElementById('btnViderCycles');
        input.value.length > 0 ? btnX.classList.remove('d-none') : btnX.classList.add('d-none');
    };

    window.viderRechercheCycles = function() {
        const input = document.getElementById('inputSearch');
        input.value = '';
        window.gererCroixCycles();
        window.afficherCyclesRegroupes(); // Recharge la liste complète
        window.scrollTo(0, 0);
    };

    // Ta fonction existante à laquelle on ajoute juste le reset scroll
    
</script>
<script>
async function afficherCyclesRegroupes() {
    window.scrollTo(0, 0); 
    const container = document.getElementById('cycles-master-container');
    const search = document.getElementById('inputSearch').value.toLowerCase();
    container.innerHTML = '';

    const [clients, carnets, cycles, collectes] = await Promise.all([
        db.clients.toArray(),
        db.carnets.toArray(),
        db.cycles.toArray(),
        db.collectes.toArray()
    ]);

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
                        <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px; overflow: hidden;">
                            <div class="card-header border-0 py-2 px-3 d-flex justify-content-between align-items-center" style="background: #f1f3f5;">
                                <span class="fw-bold" style="font-size: 0.7rem; color: #495057;">
                                    <i class="bi bi-journal-bookmark-fill me-1"></i> CARNET: ${numCarnet}
                                </span>
                            </div>
                            <div class="card-body p-0">
                    `;

                    carnetCycles.forEach(cycle => {
                        const collectesCycle = collectes.filter(col => col.cycle_id === cycle.id);
                        
                        // --- NOUVELLE LOGIQUE : SOMME DES POINTAGES ---
                        const nbrPointages = collectesCycle.reduce((sum, col) => sum + parseInt(col.pointage || 0), 0);
                        
                        const progression = Math.min(Math.round((nbrPointages / 31) * 100), 100);
                        const brut = collectesCycle.reduce((sum, col) => sum + parseFloat(col.montant || 0), 0);
                        const mise = parseFloat(cycle.montant_journalier || 0);
                        const net = brut > mise ? brut - mise : brut; 

                        const estTermine = cycle.statut === 'termine' || nbrPointages >= 31;
                        const estSynchro = cycle.synced === 1;
                        const afficherSomme = estTermine && (!cycle.retire_at || cycle.retire_at === "null");
                        
                        const peutAgir = (nbrPointages === 0 && !estSynchro);

                        clientHtml += `
                            <div class="px-3 py-3 ${carnetCycles.indexOf(cycle) !== carnetCycles.length - 1 ? 'border-bottom' : ''}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div style="flex: 1;">
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="/pwa/pointage-shell?carnet_id=${carnet.id}" class="text-decoration-none fw-bolder text-dark" style="font-size: 1.1rem;">
                                                ${cycle.montant_journalier} F
                                            </a>
                                            ${estTermine 
                                                ? '<span class="badge bg-success" style="font-size: 0.55rem;">Terminé (31/31)</span>' 
                                                : `<span class="badge bg-light text-primary border" style="font-size: 0.55rem;">${nbrPointages} / 31 pts</span>`
                                            }
                                            ${estSynchro 
                                                ? '<i class="bi bi-cloud-check-fill text-info" style="font-size: 0.8rem;"></i>' 
                                                : '<i class="bi bi-cloud-arrow-up text-warning" style="font-size: 0.8rem;"></i>'
                                            }
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                            <i class="bi bi-cash-stack me-1"></i>Cumul: <strong>${brut} F</strong>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        ${afficherSomme ? `
                                            <div class="bg-primary text-white px-2 py-1 rounded-3 mb-1" style="font-size: 0.8rem; font-weight: 700;">Net: ${net} F</div>
                                            <div style="font-size: 0.55rem; color: #dc3545;">Com: -${mise} F</div>
                                        ` : `
                                            <div class="d-flex gap-1 justify-content-end">
                                                ${peutAgir ? `
                                                    <button onclick="event.preventDefault(); ouvrirModif(${cycle.id})" class="btn btn-outline-secondary btn-sm border-0 p-1"><i class="bi bi-pencil-square"></i></button>
                                                    <button onclick="event.preventDefault(); supprimerCycle(${cycle.id})" class="btn btn-outline-danger btn-sm border-0 p-1"><i class="bi bi-trash3"></i></button>
                                                ` : '<span class="text-muted small" style="font-size:0.55rem;"><i class="bi bi-lock-fill"></i> Actif</span>'}
                                            </div>
                                        `}
                                    </div>
                                </div>

                                <div class="progress" style="height: 6px; background-color: #eee; border-radius: 10px;">
                                    <div class="progress-bar ${progression >= 100 ? 'bg-success' : 'bg-primary'}" 
                                         role="progressbar" style="width: ${progression}%;"></div>
                                </div>

                                <div class="mt-2 d-flex justify-content-between align-items-center">
                                    <small class="text-muted" style="font-size: 0.6rem;">
                                        <i class="bi bi-calendar3 me-1"></i>Lancé le ${new Date(cycle.date_debut).toLocaleDateString()}
                                    </small>
                                    <a href="/pwa/pointage-shell?carnet_id=${carnet.id}" class="btn btn-link p-0 text-decoration-none shadow-none" style="font-size: 0.65rem;">Ouvrir <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        `;
                    });
                    clientHtml += `</div></div>`;
                }
            });

            if (clientAffiche) {
                container.innerHTML += `
                    <div class="client-group mb-4 px-1">
                        <div class="d-flex align-items-center mb-2 mt-3">
                            <div class="bg-dark rounded-circle d-flex align-items-center justify-content-center text-white me-2 shadow-sm" style="width: 35px; height: 35px; font-weight: 800; font-size: 0.9rem;">
                                ${client.nom.charAt(0).toUpperCase()}
                            </div>
                            <h6 class="mb-0 fw-black text-dark" style="font-size: 1.1rem; letter-spacing: -0.5px;">${client.nom.toUpperCase()}</h6>
                        </div>
                        ${clientHtml}
                    </div>
                `;
            }
        }
    });

    if (container.innerHTML === '') {
        container.innerHTML = `<div class="text-center py-5 text-muted small">Aucun cycle trouvé</div>`;
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