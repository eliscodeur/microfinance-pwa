@extends('pwa.layouts.app')

@section('header')
<div class="d-flex align-items-center w-100 bg-white py-1">
    
    <div id="header-default-mode" class="d-flex align-items-center justify-content-between w-100 px-2">
        <div class="d-flex align-items-center ">
            <button onclick="toggleSidebar()" class="btn btn-link text-dark p-0 me-3 border-0">
                <i class="bi bi-list fs-3 me-3"></i>
            </button>
            <span class="fw-bold text-dark fs-5 text-primary">Listes des cycles</span>
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
                   list="datalistClientsCycles" 
                   id="inputSearchClient" 
                   placeholder="Chercher client..."
                   style="border-radius: 8px; font-size: 1rem; height: 45px;"
                   oninput="gererAffichageCroix(); afficherCyclesRegroupes();">
            
            <button id="btnViderRecherche"
                    onclick="viderRecherche()" 
                    class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted d-none" 
                    style="z-index: 10; padding-right: 12px;">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
    </div>
    
    <datalist id="datalistClientsCycles"></datalist>

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
    .custom-search-input:focus, .custom-input-clean:focus {
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
<div class="container-fluid px-3 py-3" style="padding-bottom: 80px !important;">
    <div id="cycles-master-container">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search" style="font-size: 2rem; opacity: 0.3;"></i>
            <p class="mt-2 small">Sélectionnez un client pour voir ses cycles</p>
        </div>
    </div>
</div>

<style>
    .client-group { border-left: 2px solid transparent; }
    .card { transition: all 0.2s ease; }
</style>

<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js'; 

    // --- 1. EXPOSITION SYSTÉMATIQUE ---
    window.initialiserRecherche = initialiserRecherche;
    window.afficherCyclesRegroupes = afficherCyclesRegroupes;
    window.viderRecherche = viderRecherche;
    window.gererAffichageCroix = gererAffichageCroix;
    
    // Fonctions liées aux actions SweetAlert2
    window.ouvrirModif = ouvrirModif;
    window.supprimerCycle = supprimerCycle;

    // --- 2. RENDU DYNAMIQUE ET FILTRAGE COMPLET ---
    async function afficherCyclesRegroupes() {
        const activeDB = getAgentDB();
        if (!activeDB) return;
        
        const input = document.getElementById('inputSearchClient');
        const container = document.getElementById('cycles-master-container');
        const val = input ? input.value.trim() : '';
        
        if (!container) return;

        try {
            const [clients, carnets, cycles, collectes] = await Promise.all([
                activeDB.clients.toArray(),
                activeDB.carnets.toArray(),
                activeDB.cycles.toArray(),
                activeDB.collectes.toArray()
            ]);

            const clientsTries = clients.sort((a, b) => {
                const nomA = `${a.nom} ${a.prenom}`.toLowerCase();
                const nomB = `${b.nom} ${b.prenom}`.toLowerCase();
                return nomA.localeCompare(nomB);
            });

            const cyclesChronologiques = cycles.sort((a, b) => new Date(b.date_debut) - new Date(a.date_debut));

            let html = `<div class="px-2 mb-2 text-muted small fw-bold text-uppercase" style="font-size:0.6rem;">${val ? val : 'TOUS LES CYCLES'}</div>`;

            let auMoinsUnMatch = false;

            clientsTries.forEach(client => {
                const matchClient = val ? `${client.nom} ${client.prenom}`.toLowerCase().includes(val.toLowerCase()) : true;
                const clientCarnets = carnets.filter(car => car.client_id === client.id);
                
                if (cycles.some(cy => cy.client_id === client.id)) {
                    let clientHtml = '';
                    let clientAffiche = false;

                    clientCarnets.forEach(carnet => {
                        const numCarnet = carnet.numero;
                        const carnetCycles = cyclesChronologiques.filter(cy => cy.carnet_id === carnet.id);
                        
                        if (carnetCycles.length > 0 && matchClient) {
                            clientAffiche = true;
                            auMoinsUnMatch = true;
                            clientHtml += `
                                <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px; overflow: hidden;">
                                    <div class="card-header border-0 py-2 px-3 d-flex justify-content-between align-items-center" style="background: #f1f3f5;">
                                        <span class="fw-bold" style="font-size: 0.7rem; color: #495057;">
                                            <i class="bi bi-journal-bookmark-fill me-1"></i> CARNET: ${numCarnet}
                                        </span>
                                    </div>
                                    <div class="card-body p-0">
                            `;

                            carnetCycles.forEach((cycle, index) => {
                                const collectesCycle = collectes.filter(col => col.cycle_id === cycle.id);
                                
                                const nbrPointages = collectesCycle.reduce((sum, col) => sum + parseInt(col.pointage || 0), 0);
                                const progression = Math.min(Math.round((nbrPointages / 31) * 100), 100);
                                const brut = collectesCycle.reduce((sum, col) => sum + parseFloat(col.montant || 0), 0);
                                const mise = parseFloat(cycle.montant_journalier || 0);

                                const estTermine = cycle.statut === 'termine' || nbrPointages >= 31;
                                const estSynchro = cycle.synced === 1;
                                const afficherSomme = estTermine && (!cycle.retire_at || cycle.retire_at === "null");
                                const peutAgir = (nbrPointages === 0);

                                clientHtml += `
                                    <div class="px-3 py-3 ${index !== carnetCycles.length - 1 ? 'border-bottom' : ''}">
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
                                                    <i class="bi ${estSynchro ? 'bi-cloud-check-fill text-info' : 'bi-cloud-arrow-up text-warning'}" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                                    <i class="bi bi-cash-stack me-1"></i>Cumul: <strong>${brut.toLocaleString()} F</strong>
                                                </div>
                                            </div>

                                            <div class="text-end">
                                                ${afficherSomme ? `
                                                    <div class="bg-primary text-white px-2 py-1 rounded-3 mb-1" style="font-size: 0.8rem; font-weight: 700;">Net: ${(cycle.solde_restant_net || 0).toLocaleString()} F</div>
                                                    <div style="font-size: 0.55rem; color: #dc3545;">Com: -${mise.toLocaleString()} F</div>
                                                ` : `
                                                    <div class="d-flex gap-3 justify-content-end align-items-center mt-1">
                                                        ${peutAgir ? `
                                                            <i onclick="ouvrirModif(${cycle.id})" class="bi bi-pencil-square text-muted fs-5" style="cursor:pointer;"></i>
                                                            <i onclick="supprimerCycle(${cycle.id})" class="bi bi-trash text-danger fs-5" style="cursor:pointer;"></i>
                                                        ` : '<span class="text-muted small" style="font-size:0.6rem;"><i class="bi bi-lock-fill"></i> Actif</span>'}
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
                                                <i class="bi bi-calendar3 me-1"></i>Lancé le ${new Date(cycle.date_debut).toLocaleDateString('fr-FR')}
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
                        html += `
                            <div class="client-group mb-4 px-1">
                                <div class="d-flex align-items-center mb-2 mt-3">
                                    <div class="bg-dark rounded-circle d-flex align-items-center justify-content-center text-white me-2 shadow-sm" style="width: 35px; height: 35px; font-weight: 800; font-size: 0.9rem;">
                                        ${client.nom.charAt(0).toUpperCase()}
                                    </div>
                                    <h6 class="mb-0 fw-black text-dark" style="font-size: 1.1rem; letter-spacing: -0.5px;">${client.nom.toUpperCase()} ${client.prenom.toUpperCase()}</h6>
                                </div>
                                ${clientHtml}
                            </div>
                        `;
                    }
                }
            });

            if (!auMoinsUnMatch) {
                container.innerHTML = html + `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search display-1 opacity-25 d-block mb-3"></i>
                        <div class="small">Aucun cycle trouvé.</div>
                    </div>`;
                return;
            }

            container.innerHTML = html;
        } catch (error) {
            console.error("Erreur d'affichage des cycles:", error);
        }
    }

    // --- 3. ACTIONS DE MODIFICATION SWEETALERT2 ---
    async function ouvrirModif(id) {
        const activeDB = getAgentDB();
        try {
            const cycle = await activeDB.cycles.get(id);
            if (!cycle) return;

            const { value: nouveauMontant } = await Swal.fire({
                title: 'Modifier le cycle',
                html: `
                    <div class="p-2 text-start">
                        <label class="form-label small fw-bold text-muted mb-1">Montant journalier (F)</label>
                        <input type="number" id="swal-edit-montant" class="form-control border-0 bg-light p-3 text-center fw-bold custom-input-clean" 
                               value="${cycle.montant_journalier}" style="border-radius: 12px; font-size: 1.2rem;">
                        <div id="swal-error-montant" class="text-danger mt-1 d-none" style="font-size: 0.75rem;">
                            <i class="bi bi-exclamation-circle me-1"></i> Minimum 100 F requis.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Enregistrer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#3085d6',
                reverseButtons: true,
                didOpen: () => {
                    const input = document.getElementById('swal-edit-montant');
                    if (input) input.focus();
                },
                preConfirm: () => {
                    const montant = parseFloat(document.getElementById('swal-edit-montant').value) || 0;
                    if (montant < 100) {
                        document.getElementById('swal-error-montant').classList.remove('d-none');
                        return false; 
                    }
                    return montant;
                }
            });

            if (nouveauMontant) {
                await activeDB.cycles.update(id, {
                    montant_journalier: nouveauMontant,
                    synced: 0 
                });

                Swal.fire({ icon: 'success', title: 'Mis à jour !', timer: 1000, showConfirmButton: false });
                await afficherCyclesRegroupes();
            }
        } catch (error) {
            console.error(error);
        }
    }

    // --- 4. ACTION DE SUPPRESSION SWEETALERT2 ---
    async function supprimerCycle(id) {
        const activeDB = getAgentDB();
        
        const result = await Swal.fire({
            title: 'Supprimer ce cycle ?',
            text: "Cette action effacera définitivement le cycle et ses paramètres en local.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            try {
                await activeDB.cycles.delete(id);
                Swal.fire({ title: 'Supprimé !', icon: 'success', timer: 1000, showConfirmButton: false });
                await afficherCyclesRegroupes();
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de supprimer ce cycle.' });
            }
        }
    }

    // --- 5. ENGINES DE RECHERCHE & DATALIST ---
    async function viderRecherche() {
        document.getElementById('inputSearchClient').value = '';
        gererAffichageCroix();
        await afficherCyclesRegroupes();
    }

    function gererAffichageCroix() {
        const val = document.getElementById('inputSearchClient').value.trim();
        document.getElementById('btnViderRecherche').classList.toggle('d-none', val.length === 0);
        if(val.length === 0) afficherCyclesRegroupes();
    }

    async function initialiserRecherche() {
        const activeDB = getAgentDB();
        if (!activeDB) return;
        
        const clients = await activeDB.clients.toArray();
        const dl = document.getElementById('datalistClientsCycles');
        if (dl) dl.innerHTML = clients.map(c => `<option value="${c.nom} ${c.prenom}">`).join('');
        
        await afficherCyclesRegroupes();
    }

    // Chargement initial au boot de la PWA
    initialiserRecherche();
</script>
@endsection