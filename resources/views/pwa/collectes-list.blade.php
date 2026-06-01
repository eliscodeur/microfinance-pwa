@extends('pwa.layouts.app')

@section('header')
<div class="d-flex align-items-center w-100 bg-white py-1">
    
    <div id="header-default-mode" class="d-flex align-items-center justify-content-between w-100 px-2">
        <div class="d-flex align-items-center">
            <button onclick="toggleSidebar()" class="btn btn-link text-dark p-0 me-3 border-0">
                <i class="bi bi-list fs-3 me-3"></i>
            </button>
            <span class="fw-bold fs-5 text-primary">Liste des collectes</span>
        </div>
        
        <button onclick="activerModeRecherche()" class="btn btn-link text-dark p-0 border-0">
            <i class="bi bi-search fs-4"></i>
        </button>
    </div>

    <div id="header-search-mode" class="d-flex align-items-center w-100 d-none px-2">
        <button onclick="desactiverModeRecherche()" class="btn btn-link text-dark p-0 me-3 border-0">
            <i class="bi bi-arrow-left fs-3"></i>
        </button>
        
        <div class="position-relative flex-grow-1">
            <input class="form-control border-0 bg-light pe-5 custom-search-input" 
                   list="datalistClients" 
                   id="inputSearchClient" 
                   placeholder="Chercher client..."
                   style="border-radius: 8px; font-size: 1rem; height: 45px;"
                   oninput="gererAffichageCroix(); chargerDonneesClient();">
            
            <button id="btnViderRecherche"
                    onclick="viderRecherche()" 
                    class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted d-none" 
                    style="z-index: 10; padding-right: 12px;">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
    </div>
    
    <datalist id="datalistClients"></datalist>

</div>

<script>
    /**
     * Bascule l'en-tête vers le champ de saisie actif en pleine largeur
     */
    function activerModeRecherche() {
        document.getElementById('header-default-mode').classList.add('d-none');
        const searchMode = document.getElementById('header-search-mode');
        searchMode.classList.remove('d-none');
        
        const input = document.getElementById('inputSearchClient');
        if (input) input.focus();
    }

    /**
     * Quitte le mode recherche, nettoie les filtres et réaffiche le titre
     */
    function desactiverModeRecherche() {
        document.getElementById('header-search-mode').classList.add('d-none');
        document.getElementById('header-default-mode').classList.remove('d-none');
        
        if (typeof window.viderRecherche === 'function') {
            window.viderRecherche();
        } else {
            const input = document.getElementById('inputSearchClient');
            if (input) input.value = '';
            const btnCroix = document.getElementById('btnViderRecherche');
            if (btnCroix) btnCroix.classList.add('d-none');
        }
    }
</script>

<style>
    /* Suppression radicale du contour bleu Bootstrap au clic */
    .custom-search-input:focus {
        background-color: #f1f3f4 !important;
        border-color: transparent !important;
        box-shadow: none !important;
        outline: none !important;
    }
    
    /* Style du texte indicatif */
    .custom-search-input::placeholder {
        color: #757575 !important;
        font-weight: 600 !important;
        opacity: 1;
    }
</style>
@endsection

@section('content')
<div class="sticky-top bg-white pt-3 pb-2 shadow-sm" style="z-index: 1020; top: 0;">
    <div class="container-fluid px-3">
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
</div>

<div class="container-fluid px-3 py-3" style="padding-bottom: 80px !important;">
    <div id="collectes-master-container">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search" style="font-size: 2rem; opacity: 0.3;"></i>
            <p class="mt-2 small">Sélectionnez un client pour voir ses collectes</p>
        </div>
    </div>
</div>

<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js'; 

    // --- 1. ÉTAT GLOBAL ---
    let filtreActuel = 'tous'; 

    // --- 2. EXPOSITION SYSTÉMATIQUE ---
    window.initialiserRecherche = initialiserRecherche;
    window.chargerDonneesClient = chargerDonneesClient;
    window.viderRecherche = viderRecherche;
    window.gererAffichageCroix = gererAffichageCroix;
    window.setFiltre = setFiltre;
    
    // Fonctions liées à SweetAlert
    window.confirmerSuppression = confirmerSuppression;
    window.ouvrirModifCollecte = ouvrirModifCollecte;

    // --- 3. GESTION DES FILTRES ---
    async function setFiltre(nouveauFiltre) {
        filtreActuel = nouveauFiltre;
        const boutons = {
            'non_synchro': 'btn-filter-non-synchro',
            'synchro': 'btn-filter-synchro',
            'tous': 'btn-filter-tous'
        };

        Object.keys(boutons).forEach(cle => {
            const btn = document.getElementById(boutons[cle]);
            if (btn) {
                if (cle === nouveauFiltre) {
                    btn.classList.replace('btn-outline-secondary', 'btn-primary');
                    btn.classList.add('shadow-sm');
                } else {
                    btn.classList.replace('btn-primary', 'btn-outline-secondary');
                    btn.classList.remove('shadow-sm');
                }
            }
        });
        await chargerDonneesClient();
    }

    // --- 4. CHARGEMENT & AFFICHAGE ---
    async function chargerDonneesClient() {
        const activeDB = getAgentDB();
        if (!activeDB) return;

        const input = document.getElementById('inputSearchClient');
        const container = document.getElementById('collectes-master-container');
        const val = input?.value.trim();
        container.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary spinner-border-sm mb-2" role="status" style="width: 1.5rem; height: 1.5rem; border-width: 0.15em;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <div class="text-muted small" style="font-size: 0.75rem; font-weight: 500; letter-spacing: 0.5px;">
                        Chargement des livrets...
                    </div>
                </div>
            `;
        try {
            const toutesLesCols = await activeDB.collectes.toArray();
            const allClients = await activeDB.clients.toArray();
            const allCycles = await activeDB.cycles.toArray();
            const allCarnets = await activeDB.carnets.toArray();

            let collectesAffichees = val 
                ? toutesLesCols.filter(col => {
                    const c = allClients.find(cl => cl.id == col.client_id);
                    return c && (`${c.nom} ${c.prenom}`.toLowerCase().includes(val.toLowerCase()));
                  })
                : toutesLesCols;

            if (filtreActuel === 'non_synchro') collectesAffichees = collectesAffichees.filter(c => c.synced == 0);
            else if (filtreActuel === 'synchro') collectesAffichees = collectesAffichees.filter(c => c.synced == 1);

            let html = `<div class="px-2 mb-2 text-muted small fw-bold text-uppercase" style="font-size:0.6rem;">${val ? val : 'VUE GLOBALE'}</div>`;

            if (collectesAffichees.length === 0) {
                container.innerHTML = html + `
                    <div class="text-center py-5 px-3">
                        <div class="position-relative d-inline-block mb-3">
                            <i class="bi bi-folder-x text-secondary opacity-25" style="font-size: 4.5rem;"></i>
                        </div>
                        <h6 class="fw-bold text-secondary mb-1" style="font-size: 0.95rem;">Aucun pointage trouvé</h6>
                        <p class="text-muted small mx-auto mb-0" style="max-width: 250px; font-size: 0.8rem;">
                            Aucune mise ou collecte n'a encore été enregistrée sur ce cycle.
                        </p>
                    </div>`;
                return;
            }

            // Groupement par Client puis par Cycle
            const groupement = {};
            collectesAffichees.forEach(col => {
                if (!groupement[col.client_id]) groupement[col.client_id] = [];
                groupement[col.client_id].push(col);
            });

            for (const clientId in groupement) {
                const clientInfo = allClients.find(c => c.id == clientId);
                html += `<div class="client-header px-2 mt-3 mb-1">
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem;">${clientInfo ? clientInfo.nom + ' ' + clientInfo.prenom : 'Client Inconnu'}</span>
                         </div>`;

                const cycles = {};
                groupement[clientId].forEach(col => {
                    const cyId = col.cycle_id || 'sans-cycle';
                    if (!cycles[cyId]) cycles[cyId] = [];
                    cycles[cyId].push(col);
                });

                for (const cyId in cycles) {
                    const listeCols = cycles[cyId].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    const cycleData = allCycles.find(cy => cy.id == cyId);
                    const carnetData = cycleData ? allCarnets.find(car => car.id == cycleData.carnet_id) : null;
                    const totalMnt = listeCols.reduce((s, c) => s + parseFloat(c.montant || 0), 0);

                    html += `
                        <div class="card mx-2 mb-2 shadow-sm border-0" style="border-radius:12px;">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center border-0 py-2">
                                <span class="small fw-bold">Carnet: ${carnetData ? carnetData.numero : 'N/A'}</span>
                                <span class="text-primary fw-bold" style="font-size:0.85rem;">${totalMnt.toLocaleString()} F</span>
                            </div>
                            <div class="list-group list-group-flush">`;

                    listeCols.forEach(col => {
                        const d = new Date(col.created_at || col.date_saisie);
                        const dateStr = d.toLocaleDateString('fr-FR', {day:'2-digit', month:'short'});
                        
                        html += `
                            <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-1" style="border-bottom: 1px solid #f8f9fa !important;">
                                <div style="font-size:0.75rem;">
                                    <div class="fw-bold">${parseFloat(col.montant).toLocaleString()} F</div>
                                    <div class="text-muted" style="font-size:0.65rem;">${dateStr} • <span class="text-primary">${col.pointage} jrs</span></div>
                                </div>
                                <div class="d-flex gap-3">
                                    ${col.synced == 0 ? `
                                        <i onclick="ouvrirModifCollecte(${col.id})" class="bi bi-pencil-square text-muted"></i>
                                        <i onclick="confirmerSuppression(${col.id})" class="bi bi-trash text-danger"></i>
                                    ` : '<i class="bi bi-cloud-check-fill text-success"></i>'}
                                </div>
                            </div>`;
                    });
                    html += `</div></div>`;
                }
            }
            container.innerHTML = html;
        } catch (e) { console.error(e); }
    }

    // --- 5. ACTIONS AVEC SWEETALERT2 ---

    // SUPPRESSION
    async function confirmerSuppression(id) {
        const result = await Swal.fire({
            title: 'Supprimer cette collecte ?',
            text: "Cette action est irréversible en local.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            const activeDB = getAgentDB();
            await activeDB.collectes.delete(id);
            Swal.fire({
                title: 'Supprimé !',
                icon: 'success',
                timer: 1000,
                showConfirmButton: false
            });
            await chargerDonneesClient();
        }
    }

    // MODIFICATION (Interface dynamique dans Swal)
    async function ouvrirModifCollecte(id) {
        const activeDB = getAgentDB();
        const col = await activeDB.collectes.get(id);
        const cycle = await activeDB.cycles.get(col.cycle_id);

        const toutesLesColsDuCycle = await activeDB.collectes.where('cycle_id').equals(col.cycle_id).toArray();
        const joursDejaPointes = toutesLesColsDuCycle
            .filter(c => c.id !== id)
            .reduce((sum, c) => sum + (parseInt(c.pointage) || 0), 0);

        const maxAutorise = 31 - joursDejaPointes;
        let ptsTemp = col.pointage;

        const { value: formValues } = await Swal.fire({
            title: 'Modifier le pointage',
            html: `
                <div class="p-3 bg-light rounded-4 border mb-3">
                    <small class="text-muted fw-bold d-block mb-2">NOMBRE DE JOURS (Max: ${maxAutorise})</small>
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <button class="btn btn-white shadow-sm border" onclick="document.getElementById('swal-nb').innerText = Math.max(1, parseInt(document.getElementById('swal-nb').innerText) - 1); updateSwalTotal(${cycle.montant_journalier})" style="width:45px;height:45px;border-radius:12px;">-</button>
                        <h2 class="mx-4 mb-0 fw-bold" id="swal-nb">${ptsTemp}</h2>
                        <button class="btn btn-white shadow-sm border" onclick="let n = parseInt(document.getElementById('swal-nb').innerText); if(n < ${maxAutorise}) { document.getElementById('swal-nb').innerText = n + 1; updateSwalTotal(${cycle.montant_journalier}); } else { window.navigator.vibrate(50); }" style="width:45px;height:45px;border-radius:12px;">+</button>
                    </div>
                    <h3 class="text-primary fw-bold"><span id="swal-total">${(ptsTemp * cycle.montant_journalier).toLocaleString()}</span> FCFA</h3>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Enregistrer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#3085d6',
            preConfirm: () => {
                return parseInt(document.getElementById('swal-nb').innerText);
            }
        });

        if (formValues) {
            await enregistrerModif(id, formValues, cycle.montant_journalier, col.cycle_id);
        }
    }

    // Fonction globale pour mettre à jour le montant dans l'alerte
    window.updateSwalTotal = (mise) => {
        const nb = parseInt(document.getElementById('swal-nb').innerText);
        document.getElementById('swal-total').innerText = (nb * mise).toLocaleString();
    };

    async function enregistrerModif(id, pts, mise, cycleId) {
        const activeDB = getAgentDB();
        
        const toutesCols = await activeDB.collectes.where('cycle_id').equals(cycleId).toArray();
        const cumulJours = toutesCols.filter(c => c.id !== id).reduce((s, c) => s + c.pointage, 0) + pts;
        const nouveauStatut = (cumulJours >= 31) ? 'termine' : 'en_cours';

        await activeDB.collectes.update(id, { 
            pointage: pts, 
            montant: pts * mise,
            synced: 0 
        });

        await activeDB.cycles.update(cycleId, { statut: nouveauStatut });

        Swal.fire({ icon: 'success', title: 'Mis à jour', timer: 1000, showConfirmButton: false });
        await chargerDonneesClient();
    }

    // --- RECHERCHE & INITIALISATION ---
    async function viderRecherche() {
        document.getElementById('inputSearchClient').value = '';
        gererAffichageCroix();
        await chargerDonneesClient();
    }

    function gererAffichageCroix() {
        const val = document.getElementById('inputSearchClient').value.trim();
        document.getElementById('btnViderRecherche').classList.toggle('d-none', val.length === 0);
        if(val.length === 0) chargerDonneesClient();
    }

    async function initialiserRecherche() {
        const activeDB = getAgentDB();
        const clients = await activeDB.clients.toArray();
        const dl = document.getElementById('datalistClients');
        if (dl) dl.innerHTML = clients.map(c => `<option value="${c.nom} ${c.prenom}">`).join('');
        await setFiltre('non_synchro');
    }

    initialiserRecherche();
</script>
@endsection