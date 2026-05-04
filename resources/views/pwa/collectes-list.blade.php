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
        <!-- Filtres de statut -->
        <div class="container-fluid px-3 mb-2 mt-3">
            <div class="d-flex gap-2" id="filter-group">
                <button onclick="setFiltre('non_synchro')" id="btn-filter-non-synchro" class="btn btn-sm rounded-pill btn-primary shadow-sm flex-fill">
                    <i class="bi bi-cloud-arrow-up"></i> À envoyer
                </button>
                <button onclick="setFiltre('synchro')" id="btn-filter-synchro" class="btn btn-sm rounded-pill btn-outline-secondary flex-fill">
                    <i class="bi bi-cloud-check"></i> Synchro
                </button>
                <button onclick="setFiltre('tous')" id="btn-filter-tous" class="btn btn-sm rounded-pill btn-outline-secondary flex-fill">
                    Toutes
                </button>
            </div>
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
                <div id="edit-error-msg" class="text-danger small fw-bold mb-3 d-none" 
                    style="background-color: #fff5f5; padding: 10px; border-radius: 10px; border: 1px solid #feb2b2;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <span>Limite atteinte</span>
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

    // --- 1. ÉTAT GLOBAL ---
    let filtreActuel = 'tous'; 

    // --- 2. EXPOSITION SYSTÉMATIQUE ---
    // Indispensable pour que tes boutons HTML (onclick) fonctionnent
    window.initialiserRecherche = initialiserRecherche;
    window.chargerDonneesClient = chargerDonneesClient;
    window.viderRecherche = viderRecherche;
    window.gererAffichageCroix = gererAffichageCroix;
    window.supprimerCollecte = supprimerCollecte;
    window.confirmerSuppression = confirmerSuppression;
    window.setFiltre = setFiltre;
    window.ouvrirModifCollecte = ouvrirModifCollecte;
    window.changePointageModif = changePointageModif;
    window.enregistrerModifCollecte = enregistrerModifCollecte;

    // --- 3. GESTION DES FILTRES ---
    async function setFiltre(nouveauFiltre) {
        
        filtreActuel = nouveauFiltre;

        // 1. Liste des IDs de tes boutons
        const boutons = {
            'non_synchro': 'btn-filter-non-synchro',
            'synchro': 'btn-filter-synchro',
            'tous': 'btn-filter-tous'
        };

        // 2. Mise à jour visuelle des boutons
        Object.keys(boutons).forEach(cle => {
            const btn = document.getElementById(boutons[cle]);
            if (btn) {
                if (cle === nouveauFiltre) {
                    // Style bouton actif (Bleu)
                    btn.classList.replace('btn-outline-secondary', 'btn-primary');
                    btn.classList.add('shadow-sm');
                } else {
                    // Style bouton inactif (Gris)
                    btn.classList.replace('btn-primary', 'btn-outline-secondary');
                    btn.classList.remove('shadow-sm');
                }
            }
        });

        // 3. Recharger les données avec le nouveau filtre
        await chargerDonneesClient();
    }

    // --- 4. CŒUR DU SYSTÈME : CHARGEMENT & GROUPEMENT ---
    async function chargerDonneesClient() {
        const input = document.getElementById('inputSearchClient');
        const container = document.getElementById('collectes-master-container');
        const val = input?.value.trim();

        try {
            const toutesLesCols = await db.collectes.toArray();
            const allClients = await db.clients.toArray();
            const allCycles = await db.cycles.toArray();
            const allCarnets = await db.carnets.toArray();

            let collectesAffichees = [];
            let titreMode = "";

            if (val) {
                const client = allClients.find(c => `${c.nom} ${c.prenom}` === val || c.nom === val);
                if (!client) {
                    container.innerHTML = `<div class="text-center py-5 text-muted small">Client non trouvé.</div>`;
                    return;
                }
                collectesAffichees = toutesLesCols.filter(col => col.client_id == client.id);
                titreMode = `${client.nom} ${client.prenom}`;
            } else {
                collectesAffichees = toutesLesCols;
                titreMode = "VUE GLOBALE";
            }

            if (filtreActuel === 'non_synchro') collectesAffichees = collectesAffichees.filter(c => c.synced == 0);
            else if (filtreActuel === 'synchro') collectesAffichees = collectesAffichees.filter(c => c.synced == 1);

            // En-tête simple sans le compteur global (on le met par carnet maintenant)
            let html = `<div class="px-2 mb-2 text-muted small fw-bold text-uppercase" style="font-size:0.6rem;">${titreMode}</div>`;

            if (collectesAffichees.length === 0) {
                container.innerHTML = html + `<div class="text-center py-5 text-muted small">Aucun résultat.</div>`;
                return;
            }

            const groupement = {};
            collectesAffichees.forEach(col => {
                if (!groupement[col.client_id]) groupement[col.client_id] = [];
                groupement[col.client_id].push(col);
            });

            for (const clientId in groupement) {
                const clientInfo = allClients.find(c => c.id == clientId);
                const nomClient = clientInfo ? `${clientInfo.nom} ${clientInfo.prenom}` : `Client ID: ${clientId}`;
                
                html += `
                    <div class="client-header px-2 mt-3 mb-1">
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem; text-transform: uppercase;">${nomClient}</span>
                    </div>`;

                const cycles = {};
                groupement[clientId].forEach(col => {
                    const cyId = col.cycle_id || 'sans-cycle';
                    if (!cycles[cyId]) cycles[cyId] = [];
                    cycles[cyId].push(col);
                });

                for (const cyId in cycles) {
                    const listeCols = cycles[cyId];
                    listeCols.sort((a, b) => new Date(b.date_collecte || b.created_at) - new Date(a.date_collecte || a.created_at));
                    
                    let numCarnet = "N/A";
                    const cycleData = allCycles.find(cy => cy.id == cyId);
                    if (cycleData) {
                        const carnetData = allCarnets.find(car => car.id == cycleData.carnet_id);
                        if (carnetData) numCarnet = carnetData.numero;
                    }

                    const totalMnt = listeCols.reduce((s, c) => s + parseFloat(c.montant || 0), 0);

                    html += `
                        <div class="card mx-2 mb-2 shadow-sm border-0" style="border-radius:12px;">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center border-0 py-2">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-bold text-dark">Carnet: ${numCarnet}</span>
                                        <span class="badge rounded-pill bg-dark" style="font-size:0.55rem;">${listeCols.length}</span>
                                    </div>
                                    <span class="text-muted" style="font-size:0.6rem;">Cycle ID: ${cyId}</span>
                                </div>
                                <span class="text-primary fw-bold" style="font-size:0.85rem;">${totalMnt.toLocaleString()} F</span>
                            </div>
                            <div class="list-group list-group-flush">`;

                    listeCols.forEach(col => {
                       // --- FORMATAGE DATE SÉCURISÉ ---
                        let dateAffiche = "Date inc."; 
                        const rawDate = col.date_saisie;
                        // console.log("Raw date:", rawDate); // Debug : voir la valeur brute de la date
                        if (rawDate) {
                            const d = new Date(rawDate);
                            
                            // On vérifie si la date est valide
                            if (!isNaN(d.getTime())) {
                                const auj = new Date();
                                
                                // Si c'est aujourd'hui
                                if (d.toDateString() === auj.toDateString()) {
                                    const h = d.getHours().toString().padStart(2, '0');
                                    const m = d.getMinutes().toString().padStart(2, '0');
                                    dateAffiche = `Auj. à ${h}:${m}`;
                                } else {
                                    // Format jour/mois (ex: 02/mai)
                                    const jour = d.getDate().toString().padStart(2, '0');
                                    const moisNom = d.toLocaleString('fr-FR', { month: 'short' }).replace('.', '');
                                    dateAffiche = `${jour}/${moisNom}`;
                                }
                            }
                        }

                        html += `
                            <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-1" style="border-bottom: 1px solid #f8f9fa !important;">
                                <div style="font-size:0.75rem;">
                                    <div class="fw-bold text-dark" style="font-size:0.8rem;">${parseFloat(col.montant).toLocaleString()} F</div>
                                    <div class="text-muted" style="font-size:0.65rem;">
                                        ${dateAffiche} • <span class="text-primary">${col.pointage || 1} jrs</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    ${col.synced == 0 ? `
                                        <i onclick="ouvrirModifCollecte(${col.id})" class="bi bi-pencil-square text-muted"></i>
                                        <i onclick="supprimerCollecte(${col.id})" class="bi bi-trash text-danger"></i>
                                    ` : '<i class="bi bi-cloud-check-fill text-success fs-6"></i>'}
                                </div>
                            </div>`;
                    });
                    html += `</div></div>`;
                }
            }
            container.innerHTML = html;
        } catch (e) { console.error(e); }
    }

    // --- 5. FONCTIONS DE MAINTENANCE (Modif / Suppr) ---
    function supprimerCollecte(id) {
        document.getElementById('idCollecteASupprimer').value = id;
        new bootstrap.Modal(document.getElementById('modalSupprCollecte')).show();
    }

    async function confirmerSuppression() {
        const id = parseInt(document.getElementById('idCollecteASupprimer').value);
        await db.collectes.delete(id);
        bootstrap.Modal.getInstance(document.getElementById('modalSupprCollecte')).hide();
        await chargerDonneesClient();
    }

async function ouvrirModifCollecte(id) {
    const col = await db.collectes.get(id);
    const cycle = await db.cycles.get(col.cycle_id);

    // 1. Calculer le cumul du cycle (sauf la collecte actuelle qu'on modifie)
    const toutesLesColsDuCycle = await db.collectes.where('cycle_id').equals(col.cycle_id).toArray();
    const joursDejaPointes = toutesLesColsDuCycle
        .filter(c => c.id !== id) // On exclut celle qu'on est en train de modifier
        .reduce((sum, c) => sum + (parseInt(c.pointage) || 0), 0);

    // 2. Déterminer le maximum autorisé pour cette modification
    const maxAutorise = 31 - joursDejaPointes;

    // 3. Remplir le modal
    document.getElementById('edit-collecte-id').value = id;
    document.getElementById('edit-mnt-journalier').value = cycle.montant_journalier;
    document.getElementById('edit-max-jours').value = maxAutorise; // On stocke la limite dynamique

    document.getElementById('edit-nb-val').innerText = col.pointage;
    
    const totalInitial = col.pointage * cycle.montant_journalier;
    document.getElementById('edit-total-txt').innerText = totalInitial.toLocaleString();

    new bootstrap.Modal(document.getElementById('modalModifCollecte')).show();
}

// Variable pour stocker le timer
let errorTimer;

function changePointageModif(delta) {
    const elNb = document.getElementById('edit-nb-val');
    const elTotal = document.getElementById('edit-total-txt');
    const elError = document.getElementById('edit-error-msg');
    const mntJournalier = parseFloat(document.getElementById('edit-mnt-journalier').value);
    const maxAutorise = parseInt(document.getElementById('edit-max-jours').value);

    let nouveauPointage = parseInt(elNb.innerText) + delta;

    if (nouveauPointage >= 1 && nouveauPointage <= maxAutorise) {
        // --- ACTION VALIDE ---
        elNb.innerText = nouveauPointage;
        elTotal.innerText = (nouveauPointage * mntJournalier).toLocaleString();
        
        // On cache l'erreur immédiatement si l'utilisateur revient dans les clous
        elError.classList.add('d-none');
        clearTimeout(errorTimer); 
    } else {
        // --- ACTION BLOQUÉE ---
        elError.classList.remove('d-none');
    const msg = nouveauPointage > maxAutorise 
            ? `Limite de 31j atteinte (${31 - maxAutorise}j déjà enregistrés)` 
            : "Le pointage minimum est de 1j";
        elError.querySelector('span').innerText = msg;

        // On fait disparaître le message automatiquement après 3 secondes
        clearTimeout(errorTimer);
        errorTimer = setTimeout(() => {
            elError.classList.add('d-none');
        }, 3000);

        if (window.navigator.vibrate) window.navigator.vibrate(50);
    }
}   

    async function enregistrerModifCollecte() {
        const id = parseInt(document.getElementById('edit-collecte-id').value);
        const ptsModifies = parseInt(document.getElementById('edit-nb-val').innerText);
        const mntJournalier = parseFloat(document.getElementById('edit-mnt-journalier').value);
        const mntTotal = ptsModifies * mntJournalier;

        try {
            // 1. Récupérer la collecte pour avoir le cycle_id
            const col = await db.collectes.get(id);
            
            // 2. Calculer le nouveau cumul de jours pour ce cycle
            const toutesCols = await db.collectes.where('cycle_id').equals(col.cycle_id).toArray();
            
            // Somme des autres collectes + le nouveau pointage qu'on vient de saisir
            const cumulJours = toutesCols
                .filter(c => c.id !== id)
                .reduce((sum, c) => sum + (parseInt(c.pointage) || 0), 0) + ptsModifies;

            // 3. Déterminer le statut du cycle
            const nouveauStatut = (cumulJours >= 31) ? 'termine' : 'en_cours';

            // 4. Mise à jour de la COLLECTE
            await db.collectes.update(id, { 
                pointage: ptsModifies, 
                montant: mntTotal,
                synced: 0 // On force la resynchronisation
            });

            // 5. Mise à jour du CYCLE (le statut)
            await db.cycles.update(col.cycle_id, { 
                statut: nouveauStatut 
            });

            // 6. Fermer le modal et rafraîchir l'affichage
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalModifCollecte'));
            if (modalInstance) modalInstance.hide();
            
            await chargerDonneesClient();

        } catch (error) {
            console.error("Erreur update statut :", error);
        }
    }
    async function viderRecherche() {
        const input = document.getElementById('inputSearchClient');
            if (input) {
                input.value = ''; // On vide le champ
                await gererAffichageCroix(); // On cache la croix
                await chargerDonneesClient(); // RELANCE LE CHARGEMENT (affichera tout le monde par défaut)
        }
    }

    async function gererAffichageCroix() {
        const input = document.getElementById('inputSearchClient');
        const btnX = document.getElementById('btnViderRecherche');
        const val = input.value.trim();

        if (val.length > 0) {
            btnX.classList.remove('d-none');
        } else {
            btnX.classList.add('d-none');
            // IMPORTANT : Si l'utilisateur efface tout manuellement, on recharge la vue globale
            chargerDonneesClient(); 
        }
    }

    // Dans initialiserRecherche, on prépare juste le datalist
    async function initialiserRecherche() {
        const clients = await db.clients.toArray();
        const dl = document.getElementById('datalistClients');
        if (dl) dl.innerHTML = clients.map(c => `<option value="${c.nom} ${c.prenom}">`).join('');
        
        // Optionnel : Forcer le style du premier bouton au démarrage
        await setFiltre('non_synchro');
    }

    initialiserRecherche();
</script>
@endsection