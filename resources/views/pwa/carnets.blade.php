@extends('pwa.layouts.app')

@section('header')
<style>
    body { background-color: #f8f9fa; }

    /* Alignement et typographie épurée pour l'en-tête client */
    .header-client-name {
        font-size: 1.15rem;
        font-weight: 600;
        color: #212529;
    }
</style>

<div class="d-flex align-items-center w-100 bg-white py-1">
    <!-- <a href="/pwa/clients" class="btn btn-link text-dark p-0 me-3 border-0">
        <i class="bi bi-arrow-left fs-3"></i>
    </a> -->
    
    <div class="d-flex align-items-center justify-content-between flex-grow-1">
        <span id="headerClientName" class="header-client-name text-truncate me-2">Chargement...</span>
        <span id="headerClientBadge" class="badge bg-warning text-dark border small" style="display: none;">0 carnet</span>
    </div>
</div>
@endsection

@section('content')
<div class="container py-4" style="padding-bottom: 80px;">
    
    {{-- Conteneur principal alimenté par le script --}}
    <div id="collecte-content">
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted small mt-2">Chargement des livrets...</p>
        </div>
    </div>
</div>

<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js'; 
    
    const urlParams = new URLSearchParams(window.location.search);
    const clientId = parseInt(urlParams.get('client_id'));

    // async function initCollectePage() {
    //     if (!clientId) {
    //         document.getElementById('collecte-content').innerHTML = `<div class="alert alert-danger">Client introuvable.</div>`;
    //         return;
    //     }

    //     try {
    //         const database = getAgentDB();

    //         if (!database.isOpen()) {
    //             await database.open();
    //         }
            
    //         const [client, carnets] = await Promise.all([
    //             db.clients.get(Number(clientId)),
    //             db.carnets.where('client_id').equals(Number(clientId)).toArray()
    //         ]);

    //         const container = document.getElementById('collecte-content');
            
    //         // --- INJECTION DYNAMIQUE DANS LE HEADER (NOM + BADGE) ---
    //         if (client) {
    //             document.getElementById('headerClientName').innerText = `${client.nom} ${client.prenom}`;
    //         }
            
    //         const totalCarnets = carnets.length;
    //         const badgeElement = document.getElementById('headerClientBadge');
    //         if (badgeElement) {
    //             badgeElement.innerText = `${totalCarnets} carnet${totalCarnets > 1 ? 's' : ''}`;
    //             badgeElement.style.display = 'inline-block'; // Rendre le badge visible après calcul
    //         }

    //         if (totalCarnets === 0) {
    //             container.innerHTML = `<div class="text-center py-5 text-muted">Aucun carnet trouvé pour ce bénéficiaire.</div>`;
    //             return;
    //         }

    //         let html = `<p class="text-muted small mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;">Livrets d'Épargne</p>`;

    //         for (const carnet of carnets) {
    //             const cyclesDuCarnet = await db.cycles.where('carnet_id').equals(carnet.id).toArray() || [];
    //             const soldeNet = cyclesDuCarnet[0]?.solde_restant_net || 0;
    //             const cycleActif = cyclesDuCarnet.find(cy => cy.statut === 'en_cours');
    //             const cyclesAencaisser = cyclesDuCarnet.filter(cy => cy.statut === 'termine' && !cy.retire_at);
                
    //             // --- CALCUL DE L'ÉPARGNE DU CYCLE EN COURS ---
    //             let epargneEnCours = 0;
    //             let pourcentage = 0;
                
    //             if (cycleActif) {
    //                 const collectesActives = await db.collectes.where('cycle_uid').equals(String(cycleActif.cycle_uid)).toArray();
    //                 epargneEnCours = collectesActives.reduce((sum, item) => sum + (Number(item.montant) || 0), 0);
                    
    //                 const nbrPointages = collectesActives.reduce((sum, item) => sum + (Number(item.pointage) || 0), 0);
    //                 pourcentage = Math.min(100, (nbrPointages / 31) * 100);
    //             }
                
    //             // --- CALCUL DES STATISTIQUES DES CYCLES ---
    //             const totalHistorique = Number(carnet.total_cycles_termines || 0);
    //             const terminauxEnAttente = cyclesAencaisser.length;
    //             const terminauxPayes = Math.max(0, totalHistorique - terminauxEnAttente);

    //             html += `
    //             <div class="card mb-4 shadow-sm border-0" 
    //                 style="border-radius: 25px; background: #fff; overflow: hidden; cursor: pointer;"
    //                 onclick="window.goToPointage(${carnet.id})">
                    
    //                 <div class="p-3 d-flex justify-content-between align-items-center" style="background: rgba(13, 110, 253, 0.05);">
    //                     <span class="badge rounded-pill bg-primary px-3 shadow-sm">LIVRET N° ${carnet.numero || '---'}</span>
    //                     <div class="text-end">
    //                         <small class="text-muted d-block" style="font-size: 0.65rem; font-weight: 700;">SOLDE DISPONIBLE</small>
    //                         <span class="fw-bold text-success fs-5">${soldeNet} F</span>
    //                     </div>
    //                 </div>

    //                 <div class="card-body">
    //                     ${cycleActif ? `
    //                         <div class="row align-items-center">
    //                             <div class="col-7">
    //                                 <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Cycle en cours</small>
    //                                 <h3 class="fw-black mb-0" style="color: #2d3436; font-size: 1.8rem;">
    //                                     ${epargneEnCours.toLocaleString()} <small style="font-size: 0.9rem;">FCFA</small>
    //                                 </h3>
    //                             </div>
    //                             <div class="col-5 text-end">
    //                                 <div class="d-inline-block p-2 rounded-3 bg-light border shadow-xs">
    //                                     <small class="d-block text-muted" style="font-size: 0.6rem;">Mise</small>
    //                                     <span class="fw-bold text-primary">${cycleActif.montant_journalier} F</span>
    //                                 </div>
    //                             </div>
    //                         </div>
    //                         <div class="mt-3">
    //                             <div class="progress" style="height: 10px; border-radius: 10px; background: #f0f2f5;">
    //                                 <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: ${pourcentage}%"></div>
    //                             </div>
    //                             <div class="d-flex justify-content-between mt-2">
    //                                 <small class="text-muted" style="font-size: 0.7rem;">Objectif : 31 jours</small>
    //                                 <small class="fw-bold text-primary" style="font-size: 0.7rem;">${Math.round(pourcentage)}%</small>
    //                             </div>
    //                         </div>
    //                     ` : `
    //                         <div class="py-4 text-center text-muted small">Aucun cycle actif. Prêt pour une nouvelle épargne ?</div>
    //                     `}
    //                 </div>

    //                 <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center pb-3 pt-0">
    //                     <small class="text-muted" style="font-size: 0.75rem;">
    //                         <i class="bi bi-check2-all text-success"></i> ${terminauxPayes} payé(s) | 
    //                         <i class="bi bi-clock text-warning"></i> ${terminauxEnAttente} en attente
    //                     </small>
    //                     <span class="text-primary fw-bold small" style="font-size: 0.8rem;">Gérer <i class="bi bi-chevron-right ms-1"></i></span>
    //                 </div>
    //             </div>`;
    //         }

    //         container.innerHTML = html;

    //     } catch (error) {
    //         console.error("Erreur Init Livret:", error);
    //         document.getElementById('collecte-content').innerHTML = `<div class="alert alert-danger">Erreur lors du traitement local des données.</div>`;
    //     }
    // }

async function initCollectePage() {
    if (!clientId) {
        document.getElementById('collecte-content').innerHTML = `<div class="alert alert-danger">Client introuvable.</div>`;
        return;
    }

    try {
        const database = getAgentDB();

        if (!database.isOpen()) {
            await database.open();
        }
        
        // 1. MODIFICATION ICI : db.cycles.toArray() au lieu du .where() pour contourner le manque d'index
        const [client, carnets, tousLesCycles, toutesLesCollectes] = await Promise.all([
            db.clients.get(Number(clientId)),
            db.carnets.where('client_id').equals(Number(clientId)).toArray(),
            db.cycles.toArray(), 
            db.collectes.toArray()
        ]);

        const container = document.getElementById('collecte-content');
        
        if (client) {
            document.getElementById('headerClientName').innerText = `${client.nom} ${client.prenom}`;
        }
        
        const totalCarnets = carnets.length;
        const badgeElement = document.getElementById('headerClientBadge');
        if (badgeElement) {
            badgeElement.innerText = `${totalCarnets} carnet${totalCarnets > 1 ? 's' : ''}`;
            badgeElement.style.display = 'inline-block';
        }

        if (totalCarnets === 0) {
            container.innerHTML = `<div class="text-center py-5 text-muted">Aucun carnet trouvé pour ce bénéficiaire.</div>`;
            return;
        }

        // FILTRAGE EN MÉMOIRE : On isole les cycles du client actuel sans utiliser Dexie
        const cyclesDuClient = tousLesCycles.filter(cy => Number(cy.client_id) === Number(clientId));

        let html = `<p class="text-muted small mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;">Livrets d'Épargne</p>`;

        // 2. BOUCLE SUR CHAQUE CARNET
        for (const carnet of carnets) {
            // On utilise maintenant "cyclesDuClient" qu'on a filtré juste au-dessus
            const cyclesDuCarnet = cyclesDuClient.filter(cy => cy.carnet_id === carnet.id) || [];
            
            // Uniquement les cycles terminés et NON retirés pour le solde disponible
            const cyclesTerminesNonRetires = cyclesDuCarnet.filter(cy => 
                cy.statut === 'termine' && (!cy.retire_at || cy.retire_at === "null" || cy.retire_at === null)
            );

            // Récupérer les collectes rattachées à ces cycles terminés
            const collectesDesCyclesTermines = toutesLesCollectes.filter(col => {
                return cyclesTerminesNonRetires.some(cy => cy.id === col.cycle_id);
            });

            // Calcul du solde disponible (Cycles terminés non payés)
            const soldeReelDisponible = collectesDesCyclesTermines.reduce((sum, item) => sum + (Number(item.montant) || 0), 0);

            const cycleActif = cyclesDuCarnet.find(cy => cy.statut === 'en_cours');
            const cyclesAencaisser = cyclesDuCarnet.filter(cy => cy.statut === 'termine' && !cy.retire_at);
            
            // --- CALCUL DE L'ÉPARGNE DU CYCLE EN COURS ---
            let epargneEnCours = 0;
            let pourcentage = 0;
            
            if (cycleActif) {
                const collectesActives = toutesLesCollectes.filter(col => String(col.cycle_id) === String(cycleActif.id));
                epargneEnCours = collectesActives.reduce((sum, item) => sum + (Number(item.montant) || 0), 0);
                const nbrPointages = collectesActives.reduce((sum, item) => sum + (Number(item.pointage) || 0), 0);
                pourcentage = Math.min(100, (nbrPointages / 31) * 100);
            }
            
            // --- STATISTIQUES FOOTER ---
            const totalHistorique = Number(carnet.total_cycles_termines || 0);
            const terminauxEnAttente = cyclesAencaisser.length;
            const terminauxPayes = Math.max(0, totalHistorique - terminauxEnAttente);

            html += `
            <div class="card mb-4 shadow-sm border-0" 
                style="border-radius: 25px; background: #fff; overflow: hidden; cursor: pointer;"
                onclick="window.goToPointage(${carnet.id})">
                
                <div class="p-3 d-flex justify-content-between align-items-center" style="background: rgba(13, 110, 253, 0.05);">
                    <span class="badge rounded-pill bg-primary px-3 shadow-sm">LIVRET N° ${carnet.numero || '---'}</span>
                    <div class="text-end">
                        <small class="text-muted d-block" style="font-size: 0.65rem; font-weight: 700;">SOLDE DISPONIBLE</small>
                        <span class="fw-bold text-success fs-5">${soldeReelDisponible.toLocaleString()} F</span>
                    </div>
                </div>

                <div class="card-body">
                    ${cycleActif ? `
                        <div class="row align-items-center">
                            <div class="col-7">
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Cycle en cours</small>
                                <h3 class="fw-black mb-0" style="color: #2d3436; font-size: 1.8rem;">
                                    ${epargneEnCours.toLocaleString()} <small style="font-size: 0.9rem;">FCFA</small>
                                </h3>
                            </div>
                            <div class="col-5 text-end">
                                <div class="d-inline-block p-2 rounded-3 bg-light border shadow-xs">
                                    <small class="d-block text-muted" style="font-size: 0.6rem;">Mise</small>
                                    <span class="fw-bold text-primary">${cycleActif.montant_journalier} F</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 10px; border-radius: 10px; background: #f0f2f5;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: ${pourcentage}%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted" style="font-size: 0.7rem;">Objectif : 31 jours</small>
                                <small class="fw-bold text-primary" style="font-size: 0.7rem;">${Math.round(pourcentage)}%</small>
                            </div>
                        </div>
                    ` : `
                        <div class="py-4 text-center text-muted small">Aucun cycle actif. Prêt pour une nouvelle épargne ?</div>
                    `}
                </div>

                <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center pb-3 pt-0">
                    <small class="text-muted" style="font-size: 0.75rem;">
                        <i class="bi bi-check2-all text-success"></i> ${terminauxPayes} payé(s) | 
                        <i class="bi bi-clock text-warning"></i> ${terminauxEnAttente} en attente
                    </small>
                    <span class="text-primary fw-bold small" style="font-size: 0.8rem;">Gérer <i class="bi bi-chevron-right ms-1"></i></span>
                </div>
            </div>`;
        }

        container.innerHTML = html;

    } catch (error) {
        console.error("Erreur Init Livret:", error);
        document.getElementById('collecte-content').innerHTML = `<div class="alert alert-danger">Erreur lors du traitement local des données.</div>`;
    }
}

    window.goToPointage = (carnetId) => {
        window.location.href = `/pwa/pointage-shell?carnet_id=${carnetId}`;
    };

    document.addEventListener('DOMContentLoaded', initCollectePage);
</script>
@endsection