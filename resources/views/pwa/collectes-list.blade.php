@extends('pwa.layouts.app')

@section('content')
<div class="sticky-top bg-white pt-3 pb-2 shadow-sm" style="z-index: 1020;">
    <div class="container-fluid px-3">
        <label class="form-label small fw-bold text-muted mb-1">Collectes par client</label>
        <div class="position-relative">
            <input class="form-control form-control-lg border-0 bg-light pe-5" 
                   list="datalistClients" 
                   id="inputSearchClient" 
                   placeholder="Nom du client..."
                   style="border-radius: 12px; font-size: 0.95rem;"
                   oninput="gererAffichageCroix(); chargerDonneesClient();">
            
            <button id="btnViderRecherche"
                    onclick="viderRecherche()" 
                    class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted d-none" 
                    style="z-index: 10; padding-right: 15px;">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
        <datalist id="datalistClients"></datalist>
    </div>
</div>

    <div id="collectes-master-container">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search" style="font-size: 2rem; opacity: 0.3;"></i>
            <p class="mt-2 small">Sélectionnez un client pour voir ses collectes</p>
        </div>
    </div>
</div>
<div class="modal fade" id="modalSupprCollecte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <i class="bi bi-exclamation-octagon-fill" style="font-size: 3rem;"></i>
                </div>
                <h5 class="fw-bold">Supprimer ?</h5>
                <p class="text-muted small">Voulez-vous vraiment annuler ce pointage ? Cette action est irréversible.</p>
                
                <input type="hidden" id="idCollecteASupprimer">

                <div class="d-grid gap-2">
                    <button type="button" onclick="confirmerSuppression()" class="btn btn-danger py-2 fw-bold" style="border-radius: 10px;">Supprimer</button>
                    <button type="button" class="btn btn-light py-2 text-muted" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalModifCollecte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <div class="modal-body p-4 text-center">
                <h5 class="fw-bold mb-4">Modifier le Pointage</h5>
                
                <input type="hidden" id="edit-collecte-id">
                <input type="hidden" id="edit-mnt-journalier">
                <input type="hidden" id="edit-max-jours"> <div class="bg-light rounded-4 p-4 mb-4 border">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Nombre de jours à pointer</small>
                    <div class="d-flex justify-content-center align-items-center my-3">
                        <button type="button" class="btn btn-white shadow-sm border-0" onclick="changePointageModif(-1)" style="width:55px; height:55px; border-radius:15px; font-size:1.5rem;">-</button>
                        <h1 class="mx-4 mb-0 fw-black" id="edit-nb-val">1</h1>
                        <button type="button" class="btn btn-white shadow-sm border-0" onclick="changePointageModif(1)" style="width:55px; height:55px; border-radius:15px; font-size:1.5rem;">+</button>
                    </div>
                    <h3 class="text-primary fw-bold mb-0"><span id="edit-total-txt">0</span> FCFA</h3>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" onclick="enregistrerModifCollecte()" class="btn btn-primary py-3 fw-bold shadow-sm" style="border-radius: 15px;">Mettre à jour</button>
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="module">
    import { db } from '/js/db-manager.js';

    // Exposition des fonctions
    window.initialiserRecherche = initialiserRecherche;
    window.chargerDonneesClient = chargerDonneesClient;
    window.viderRecherche = viderRecherche;
    window.gererAffichageCroix = gererAffichageCroix;
    window.supprimerCollecte = supprimerCollecte;
    window.confirmerSuppression = confirmerSuppression;

    /**
     * Ouvre le modal et prépare l'ID
     */
    function supprimerCollecte(id) {
        // On stocke l'ID dans l'input caché du modal
        document.getElementById('idCollecteASupprimer').value = id;
        
        // On affiche le modal
        const myModal = new bootstrap.Modal(document.getElementById('modalSupprCollecte'));
        myModal.show();
    }

    /**
     * Action finale après clic sur le bouton rouge du modal
     */
    async function confirmerSuppression() {
        const id = parseInt(document.getElementById('idCollecteASupprimer').value);
        
        try {
            // 1. Suppression dans Dexie
            await db.collectes.delete(id);
            
            // 2. Fermer le modal
            const modalEl = document.getElementById('modalSupprCollecte');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance.hide();
            
            // 3. Notification rapide (optionnel)
            console.log(`Collecte ${id} supprimée`);
            
            // 4. Rafraîchir la liste immédiatement
            chargerDonneesClient();
            
        } catch (error) {
            alert("Erreur lors de la suppression");
            console.error(error);
        }
    }

    function gererAffichageCroix() {
        const input = document.getElementById('inputSearchClient');
        const btnX = document.getElementById('btnViderRecherche');
        input.value.length > 0 ? btnX.classList.remove('d-none') : btnX.classList.add('d-none');
    }

    function viderRecherche() {
        document.getElementById('inputSearchClient').value = '';
        gererAffichageCroix();
        document.getElementById('collectes-master-container').innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search" style="font-size: 2rem; opacity: 0.2;"></i>
                <p class="mt-2 small">Sélectionnez un client</p>
            </div>`;
    }

    async function initialiserRecherche() {
        const clients = await db.clients.toArray();
        const datalist = document.getElementById('datalistClients');
        if (datalist) {
            datalist.innerHTML = clients.map(c => `<option value="${c.nom}">`).join('');
        }
    }

    async function chargerDonneesClient() {
        const input = document.getElementById('inputSearchClient');
        const container = document.getElementById('collectes-master-container');
        const val = input?.value;

        const client = await db.clients.where('nom').equals(val).first();
        if (!client) {
            container.innerHTML = `<div class="text-center py-5 text-muted">Client non trouvé.</div>`;
            return;
        }

        window.scrollTo(0, 0);

        try {
            const toutesLesCollectes = await db.collectes.toArray();
            let collectesClient = toutesLesCollectes.filter(col => col.client_id == client.id);

            if (collectesClient.length === 0) {
                container.innerHTML = `<div class="text-center py-5 text-muted small">Aucun pointage trouvé.</div>`;
                return;
            }

            const collectesParCycle = {};
            collectesClient.forEach(col => {
                const cyId = col.cycle_id || 'sans-cycle';
                if (!collectesParCycle[cyId]) collectesParCycle[cyId] = [];
                collectesParCycle[cyId].push(col);
            });

            // --- ZONE CLIENT COMPACTE ---
            let html = `
                <div class="px-2 mb-3">
                    <div class="d-flex align-items-center bg-white p-2 border shadow-sm" style="border-radius: 12px;">
                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">${client.nom}</div>
                        <div class="ms-auto">
                            <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.65rem;">ID: ${client.id}</span>
                        </div>
                    </div>
                </div>
            `;

            const cycleIds = Object.keys(collectesParCycle);
            
            for (const cyId of cycleIds) {
                const listeCols = collectesParCycle[cyId];
                
                listeCols.sort((a, b) => {
                    const timeA = a.date_collecte ? new Date(a.date_collecte).getTime() : (a.id || 0);
                    const timeB = b.date_collecte ? new Date(b.date_collecte).getTime() : (b.id || 0);
                    return timeB - timeA;
                });

                let numeroAffiche = "N° Inconnu";
                const firstCol = listeCols[0];
                if (firstCol) {
                    const cycleData = await db.cycles.get(parseInt(firstCol.cycle_id));
                    if (cycleData) {
                        const carnetData = await db.carnets.get(parseInt(cycleData.carnet_id));
                        if (carnetData) numeroAffiche = carnetData.numero;
                    }
                }

                const totalCycle = listeCols.reduce((sum, col) => sum + parseFloat(col.montant || 0), 0);
                const ptsCycle = listeCols.reduce((sum, col) => sum + parseInt(col.pointage || 1), 0);
                const percent = Math.min((ptsCycle / 31) * 100, 100);

                // --- BLOC CYCLE OPTIMISÉ ---
                html += `
                    <div class="cycle-block mb-3 mx-2 shadow-sm border bg-white" style="border-radius: 15px; overflow: hidden;">
                        <div class="p-2 px-3 bg-light-subtle" style="border-bottom: 1px dashed #dee2e6;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted" style="font-size: 0.65rem; text-transform: uppercase;">Carnet</span>
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">${numeroAffiche}</div>
                                </div>
                                <div class="text-center">
                                    <div class="fw-bold text-success" style="font-size: 0.95rem;">${totalCycle.toLocaleString()} F</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold ${ptsCycle > 31 ? 'text-danger' : 'text-primary'}" style="font-size: 0.8rem;">
                                        ${ptsCycle}/31 <i class="bi bi-calendar2-check"></i>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px; width: 50px; background-color: #e9ecef; margin-left: auto;">
                                        <div class="progress-bar ${ptsCycle >= 31 ? 'bg-success' : ''}" style="width: ${percent}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="list-group list-group-flush">
                `;

                listeCols.forEach(col => {
                    // 1. On essaie de récupérer une date valide
                    const bruteDate = col.date_collecte || col.created_at;
                    const d = new Date(bruteDate);
                    
                    // 2. Si la date est invalide (N/A), on affiche un tiret ou "---"
                    let dateStr = "Auj"; 
                    if (!isNaN(d.getTime())) {
                        dateStr = d.toLocaleDateString('fr-FR', {day:'2-digit', month:'short'});
                    }
                    
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 border-0" style="border-bottom: 1px solid #f8f9fa !important;">
                            <div class="d-flex align-items-center">
                                <div class="rounded bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 0.7rem;">
                                    ${col.pointage || 1}
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 0.8rem;">${parseFloat(col.montant).toLocaleString()} F</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">${dateStr}</div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                ${col.synced == 0 ? `
                                    <i onclick="ouvrirModifCollecte(${col.id})" class="bi bi-pencil-square text-muted fs-6"></i>
                                    <i onclick="supprimerCollecte(${col.id})" class="bi bi-trash3 text-danger fs-6"></i>
                                ` : '<i class="bi bi-cloud-check-fill text-success fs-5"></i>'}
                            </div>
                        </div>`;
                });

                html += `</div></div>`;
            }

            container.innerHTML = html;

        } catch (e) {
            console.error(e);
            container.innerHTML = `<div class="alert alert-danger mx-2 py-2 small">Erreur de lecture.</div>`;
        }
    }
    // --- FONCTIONS POUR LE MODAL DE MODIFICATION ---

    window.ouvrirModifCollecte = async function(id) {
        try {
            const collecte = await db.collectes.get(id);
            const cycle = await db.cycles.get(collecte.cycle_id);
            
            if (!collecte || !cycle) return;

            // 1. Calcul du maximum autorisé
            // On récupère toutes les collectes du cycle pour savoir combien de jours ont été déjà pointés
            const toutesCollectesCycle = await db.collectes.where('cycle_id').equals(cycle.id).toArray();
            const totalJoursPointes = toutesCollectesCycle.reduce((sum, c) => sum + (parseInt(c.pointage) || 0), 0);
            
            // Le max autorisé est : (31 - jours pointés par les AUTRES collectes)
            // Donc : 31 - (total - pointage_actuel)
            const maxAutorise = 31 - (totalJoursPointes - (parseInt(collecte.pointage) || 0));

            // 2. Remplir le modal
            document.getElementById('edit-collecte-id').value = id;
            document.getElementById('edit-mnt-journalier').value = cycle.montant_journalier;
            document.getElementById('edit-max-jours').value = maxAutorise;
            
            document.getElementById('edit-nb-val').innerText = collecte.pointage || 1;
            document.getElementById('edit-total-txt').innerText = (collecte.pointage * cycle.montant_journalier).toLocaleString();

            const modal = new bootstrap.Modal(document.getElementById('modalModifCollecte'));
            modal.show();
        } catch (e) { console.error(e); }
    };

    window.changePointageModif = function(delta) {
        const el = document.getElementById('edit-nb-val');
        const txtTotal = document.getElementById('edit-total-txt');
        const mntJournalier = parseFloat(document.getElementById('edit-mnt-journalier').value);
        const maxJours = parseInt(document.getElementById('edit-max-jours').value);
        
        let current = parseInt(el.innerText);
        let nouveau = current + delta;

        // Sécurité : Min 1 jour, Max restant dans le cycle (selon limite 31)
        if (nouveau >= 1 && nouveau <= maxJours) {
            el.innerText = nouveau;
            txtTotal.innerText = (nouveau * mntJournalier).toLocaleString();
        } else if (nouveau > maxJours) {
            // Optionnel : petite alerte ou effet visuel si on dépasse
            console.warn("Limite du cycle atteinte : " + maxJours + " jours");
        }
    };

    window.enregistrerModifCollecte = async function() {
        const id = parseInt(document.getElementById('edit-collecte-id').value);
        const nouveauPointage = parseInt(document.getElementById('edit-nb-val').innerText);
        const mntJournalier = parseFloat(document.getElementById('edit-mnt-journalier').value);
        
        try {
            // 1. Récupérer la collecte avant modif pour avoir le cycle_id
            const colAvant = await db.collectes.get(id);
            const cycleId = colAvant.cycle_id;

            // 2. Mettre à jour la collecte
            await db.collectes.update(id, {
                pointage: nouveauPointage,
                montant: nouveauPointage * mntJournalier
            });

            // 3. SURVEILLANCE DU CYCLE : Recompter tout le cycle
            const toutesLesCollectesDuCycle = await db.collectes.where('cycle_id').equals(cycleId).toArray();
            const cumulPointage = toutesLesCollectesDuCycle.reduce((sum, c) => sum + (parseInt(c.pointage) || 0), 0);

            // 4. Mise à jour automatique du statut du cycle
            if (cumulPointage < 31) {
                await db.cycles.update(cycleId, { statut: 'en_cours' });
            } else {
                await db.cycles.update(cycleId, { statut: 'termine' });
            }

            // 5. Fermer le modal et rafraîchir la vue
            bootstrap.Modal.getInstance(document.getElementById('modalModifCollecte')).hide();
            chargerDonneesClient(); 

        } catch (e) {
            console.error("Erreur lors de la modif/surveillance :", e);
        }
    };

    initialiserRecherche();
</script>
@endsection