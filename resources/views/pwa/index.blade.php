@extends('pwa.layouts.app')

@section('content')
<div id="offline-alert" class="alert alert-warning py-2 shadow-sm rounded-0 border-0 mb-0 d-none text-center">
    <i class="bi bi-wifi-off me-2"></i> Mode hors-ligne : Données locales utilisées.
</div>

<div class="container mt-n3">
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 cursor-pointer" onclick="afficherCollectesDuJour()" style="cursor: pointer;">
        <div class="row text-center">
            <div class="col-6 border-end">
                <small class="text-muted d-block">Collectes Jour</small>
                <span class="fw-bold fs-5 text-primary" id="total-montant">0 FCFA</span>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Clients vus</small>
                <span class="fw-bold fs-5 text-dark" id="total-clients-vus">0</span>
            </div>
        </div>
        <div class="text-center mt-2">
            <small class="text-muted"><i class="bi bi-pencil me-1"></i> Cliquez pour éditer</small>
        </div>
    </div>
</div>

<div class="container mb-4">
    <div class="input-group shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <span class="input-group-text bg-white border-0"><i class="bi bi-search text-primary"></i></span>
        <input type="text" id="omni-search" class="form-control border-0 ps-0 py-3" 
               placeholder="Nom, Tel ou N° Livret..." oninput="rechercheRapide()">
    </div>
    
    <div id="results-list" class="mt-3">
        <div class="text-center mt-5 opacity-50">
            <i class="bi bi-search fs-1 d-block mb-2"></i>
            <p class="small">Recherchez un client pour commencer</p>
        </div>
    </div>
</div>

<div id="sync-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.95); z-index:9999; flex-direction:column; align-items:center; justify-content:center;">
    <div class="text-center p-4">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
        <h4 class="fw-bold">Initialisation terrain</h4>
        <p class="text-muted" id="sync-status">Chargement...</p>
        <div class="progress mt-3" style="height: 10px; width: 250px; border-radius: 10px;">
            <div id="sync-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
        </div>
    </div>
</div>

<!-- MODAL COLLECTES DU JOUR -->
<div class="modal fade" id="modalCollectesDuJour" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Collectes du Jour</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-collectes-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT COLLECTE -->
<div class="modal fade" id="modalEditCollecte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier Collecte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <div class="mb-3">
                    <label class="form-label fw-bold">Client</label>
                    <input type="text" id="edit-client-nom" class="form-control" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Carnet</label>
                    <input type="text" id="edit-carnet-numero" class="form-control" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" id="edit-collecte-date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pointage (jours)</label>
                    <input type="number" id="edit-collecte-pointage" class="form-control" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Montant Unitaire (FCFA)</label>
                    <input type="number" id="edit-collecte-montant-unit" class="form-control" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Montant Total (FCFA)</label>
                    <div class="input-group">
                        <input type="number" id="edit-collecte-montant-total" class="form-control" disabled>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-danger" id="btn-supprimer-collecte">
                    <i class="bi bi-trash me-1"></i>Supprimer
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btn-sauvegarder-collecte">
                    <i class="bi bi-check me-1"></i>Sauvegarder
                </button>
                
            </div>
        </div>
    </div>
</div>

<style>
    .client-card-animate { animation: fadeInUp 0.3s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .badge-carnet { background: #f0f7ff; color: #0061f2; border: 1px solid #d0e3ff; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; font-weight: 600; }
    .btn-next { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; color: #0d6efd; transition: 0.2s; }
</style>

<script type="module">
    import { db } from '/js/db-manager.js'; 

    window.addEventListener('load', async () => {
        if (!db.isOpen()) await db.open();
        // 1. On vérifie si on a des clients en base (preuve de données réelles)
        const clientCount = await db.clients.count();
        const hasLastSync = localStorage.getItem('last_sync');

        // 2. Si RIEN en base OU pas de flag de synchro
        if (clientCount === 0 || !hasLastSync) {
            // On ne redirige QUE si on a internet, sinon on affiche un message d'erreur sur l'accueil
           
            window.location.replace("{{ route('pwa.sync') }}?init=1");
            return;     
        }
        // 3. Si tout est OK, on rafraîchit l'interface
        await rafraichirStatsInterface();
    });
    // 1. STATS DU JOUR (Filtrage intelligent)
    async function rafraichirStatsInterface() {
        try {
            // 1. Obtenir la date locale YYYY-MM-DD (évite le décalage UTC de toISOString)
            const maintenant = new Date();
            const offset = maintenant.getTimezoneOffset() * 60000; 
            const aujourdhui = new Date(maintenant - offset).toISOString().split('T')[0];

            // 2. Récupérer les données
            const toutesCollectes = await db.collectes.toArray();
            
            // 3. DEBUG: Afficher les dates des collectes
            // console.log('[DEBUG] Toutes les collectes:', toutesCollectes.map(c => ({ id: c.id, date: c.date, sync: c.synced, montant: c.montant })));
            
            // 4. Filtrer seulement les collectes du jour qui ne sont PAS encore synchronisées
            const duJour = toutesCollectes.filter(c => {
                // Vérifier que la date existe et correspond
                const dateValide = c.date && c.date === aujourdhui;
                // Vérifier que ce n'est pas synchronisé (sync = 0)
                const pasSynced = c.synced === 0 || c.synced === false;
                
                // console.log(`[DEBUG] Collecte ${c.id}: date=${c.date}, aujourdhui=${aujourdhui}, valide=${dateValide}, synced=${c.synced}, pasSynced=${pasSynced}`);
                
                return dateValide && pasSynced;
            });

            // 5. Calculer le montant total
            const total = duJour.reduce((acc, curr) => acc + (parseInt(curr.montant) || 0), 0);
            
            // 6. Calculer les clients uniques (un client = une fois même avec plusieurs collectes/carnets)
            // Utilise Set pour éliminer les doublons : si un client a 2 collectes sur 2 carnets différents, il compte pour 1
            const idsUniques = new Set(duJour.map(c => Number(c.client_id)));
            const clientsVus = idsUniques.size;
            
            // Debug: Afficher la répartition par client
            // console.log(`[DEBUG] Clients uniques: ${clientsVus} | IDs: [${Array.from(idsUniques).join(', ')}]`);

            // 7. Mise à jour du DOM
            const elMontant = document.getElementById('total-montant');
            const elClients = document.getElementById('total-clients-vus');

            if (elMontant) elMontant.innerText = total.toLocaleString('fr-FR') + " FCFA";
            if (elClients) elClients.innerText = clientsVus;

            // Petit log pour débugger dans ta console (F12)
            // console.log(`[Stats] Date: ${aujourdhui} | Collectes du jour non sync: ${duJour.length} | Clients: ${clientsVus} | Total: ${total} FCFA`);

        } catch (e) { 
            console.error("Erreur rafraîchissement stats:", e); 
        }
    }
   let searchTimeout;

    window.rechercheRapide = async () => {
        clearTimeout(searchTimeout);
        
        const rawQuery = document.getElementById('omni-search').value.trim();
        const area = document.getElementById('results-list');

        if (rawQuery.length < 2) {
            area.innerHTML = '<p class="text-center text-muted mt-5 small">Entrez au moins 2 caractères...</p>';
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const query = rawQuery.toLowerCase();
                const digitsQuery = query.replace(/\D/g, '');

                // 1. Lancer toutes les recherches en PARALLÈLE pour gagner du temps
                const [byName, byCarnet, allClients] = await Promise.all([
                    db.clients.where('nom').startsWithIgnoreCase(query).toArray(),
                    db.carnets.where('numero').startsWithIgnoreCase(query).toArray(),
                    db.clients.toArray() // Nécessaire pour le téléphone car non indexable facilement avec formatage
                ]);

                // 2. Filtrer les clients par téléphone (uniquement si on a des chiffres)
                const byPhone = digitsQuery.length > 2 
                    ? allClients.filter(c => c.telephone && c.telephone.replace(/\D/g, '').includes(digitsQuery))
                    : [];

                // 3. Récupérer les IDs des clients liés aux carnets trouvés
                let clientsViaCarnets = [];
                if (byCarnet.length > 0) {
                    const clientIds = [...new Set(byCarnet.map(car => car.client_id))];
                    clientsViaCarnets = await db.clients.where('id').anyOf(clientIds).toArray();
                }

                // 4. Fusionner et dédoublonner via une Map (Clé = ID Client)
                const mapResultats = new Map();
                [...byName, ...byPhone, ...clientsViaCarnets].forEach(c => {
                    if (c && c.id) mapResultats.set(c.id, c);
                });

                const resultats = Array.from(mapResultats.values());

                if (resultats.length === 0) {
                    area.innerHTML = `<div class="text-center p-5 text-muted small">Aucun résultat pour "${rawQuery}"</div>`;
                    return;
                }

                // 5. Précharger les carnets et cycles pour l'affichage (évite les requêtes dans la boucle)
                const [tousLesCarnets, tousCycles] = await Promise.all([
                    db.carnets.toArray(),
                    db.cycles.toArray()
                ]);

                // 6. Construction propre du HTML
                let html = '';
                resultats.forEach(c => {
                    const sesCarnets = tousLesCarnets.filter(car => car.client_id === c.id);
                    
                    const carnetsHtml = sesCarnets.map(carnet => {
                        const cyclesEnCours = tousCycles.filter(cy => cy.carnet_id === carnet.id && cy.statut === 'en_cours').length;
                        const total = cyclesEnCours + (carnet.nombre_cycles_termines || 0);
                        return `<span class="badge-carnet mt-1"><i class="bi bi-journal-text"></i> ${carnet.numero} (${total})</span>`;
                    }).join(' ');

                    html += `
                        <div class="card mb-3 border-0 shadow-sm client-card-animate" style="border-radius: 18px;" onclick="window.gererRedirectionSmart(${c.id})">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1 nana-text-blue">${c.nom}</h6>
                                        <small class="text-muted"><i class="bi bi-phone"></i> ${c.telephone || '---'}</small><br>
                                        <div class="d-flex flex-wrap gap-1">${carnetsHtml}</div>
                                    </div>
                                    <div class="btn-next shadow-sm">
                                        <i class="bi bi-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });

                area.innerHTML = html;

            } catch (error) {
                console.error("Erreur recherche:", error);
                area.innerHTML = '<div class="alert alert-danger">Erreur lors de la recherche.</div>';
            }
        }, 300);
    };
            // 3. REDIRECTION INTELLIGENTE
    window.gererRedirectionSmart = async (clientId) => {
        const sesCarnets = await db.carnets.where('client_id').equals(Number(clientId)).toArray();
        
        if (sesCarnets.length === 1) {
            // Un seul carnet -> Pointage direct
            window.location.href = `/pwa/pointage-shell?carnet_id=${sesCarnets[0].id}`;
        } else {
            // Plusieurs carnets -> Page de sélection
            window.location.href = `/pwa/carnet?client_id=${clientId}`;
        }
    };



    // ===== MODIFICATION DES COLLECTES =====
    window.afficherCollectesDuJour = async () => {
        const modalBody = document.getElementById('modal-collectes-body');
        
        try {
            // Récupérer la date de aujourd'hui
            const maintenant = new Date();
            const offset = maintenant.getTimezoneOffset() * 60000;
            const aujourdhui = new Date(maintenant - offset).toISOString().split('T')[0];

            // Récupérer les collectes du jour non synchronisées
            const toutesCollectes = await db.collectes.toArray();
            const collectesDuJour = toutesCollectes.filter(c => 
                c.date === aujourdhui && (c.synced === 0 || c.synced === false)
            );

            if (collectesDuJour.length === 0) {
                modalBody.innerHTML = '<div class="alert alert-info text-center py-4"><i class="bi bi-info-circle me-2"></i>Aucune collecte à éditer aujourd\'hui.</div>';
                new bootstrap.Modal(document.getElementById('modalCollectesDuJour')).show();
                return;
            }

            // Construire le HTML des collectes
            let html = '';
            for (const collecte of collectesDuJour) {
                // console.log(`[DEBUG] Collecte ${collecte.id}:`, { cycle_id: collecte.cycle_id, client_id: collecte.client_id });
                
                const client = await db.clients.get(Number(collecte.client_id));
                
                // Récupérer le cycle pour trouver le carnet
                let carnet = null;
                
                if (collecte.cycle_id && collecte.cycle_id !== null && collecte.cycle_id !== undefined) {
                    try {
                        // Essayer de chercher le cycle par ID numérique d'abord
                        const cycleId = Number(collecte.cycle_id);
                        // console.log(`[DEBUG] Cherche cycle avec ID: ${cycleId} (isNaN: ${isNaN(cycleId)})`);
                        
                        if (!isNaN(cycleId) && cycleId > 0) {
                            const cycle = await db.cycles.where('id').equals(cycleId).first();
                            // console.log(`[DEBUG] Cycle trouvé par ID:`, cycle);
                            if (cycle && cycle.carnet_id) {
                                carnet = await db.carnets.get(Number(cycle.carnet_id));
                            }
                        }
                        
                        // Sinon chercher par cycle_uid si c'est une string
                        if (!carnet && typeof collecte.cycle_id === 'string') {
                            // console.log(`[DEBUG] Cherche cycle avec cycle_uid: ${collecte.cycle_id}`);
                            const cycle = await db.cycles.where('cycle_uid').equals(String(collecte.cycle_id)).first();
                            // console.log(`[DEBUG] Cycle trouvé par uid:`, cycle);
                            if (cycle && cycle.carnet_id) {
                                carnet = await db.carnets.get(Number(cycle.carnet_id));
                            }
                        }
                    } catch (e) {
                        console.warn('Erreur recherche cycle:', e);
                    }
                } else {
                    // console.log(`[DEBUG] cycle_id invalide ou vide:`, collecte.cycle_id);
                }
                
                // Fallback : chercher tous les carnets du client
                if (!carnet && collecte.client_id) {
                    try {
                        const carnetsDuClient = await db.carnets.where('client_id').equals(Number(collecte.client_id)).toArray();
                        // console.log(`[DEBUG] Carnets du client ${collecte.client_id}:`, carnetsDuClient);
                        if (carnetsDuClient && carnetsDuClient.length > 0) {
                            carnet = carnetsDuClient[0];
                        }
                    } catch (e) {
                        console.warn('Erreur recherche carnet client:', e);
                    }
                }
                
                html += `
                    <div class="card mb-3 border-0 shadow-sm" style="border-radius: 15px;" onclick="window.editerCollecte(${collecte.id})">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="flex: 1;">
                                    <h6 class="fw-bold mb-1">${client?.nom || 'Client #' + collecte.client_id}</h6>
                                    <small class="text-muted d-block"><i class="bi bi-journal-text"></i> ${carnet?.numero || 'Carnet inconnu'}</small>
                                    <div class="mt-2">
                                        <span class="badge bg-primary">${collecte.pointage}j</span>
                                        <span class="badge bg-success">${collecte.montant.toLocaleString('fr-FR')} FCFA</span>
                                    </div>
                                </div>
                                <i class="bi bi-pencil-square text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>`;
            }

            modalBody.innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalCollectesDuJour')).show();
        } catch (error) {
            console.error('Erreur:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des collectes.</div>';
        }
    };

    window.editerCollecte = async (collecteId) => {
        try {
            const collecte = await db.collectes.get(Number(collecteId));
            if (!collecte) return;

            const client = await db.clients.get(Number(collecte.client_id));
            
            // Récupérer le carnet via le cycle
            let carnet = null;
            let montantUnit = 0;
            
            if (collecte.cycle_id && collecte.cycle_id !== null && collecte.cycle_id !== undefined) {
                try {
                    // Essayer de chercher le cycle par ID numérique d'abord
                    const cycleId = Number(collecte.cycle_id);
                    if (!isNaN(cycleId) && cycleId > 0) {
                        const cycle = await db.cycles.where('id').equals(cycleId).first();
                        if (cycle) {
                            montantUnit = cycle.montant_journalier || 0;
                            if (cycle.carnet_id) {
                                carnet = await db.carnets.get(Number(cycle.carnet_id));
                            }
                        }
                    }
                    
                    // Sinon chercher par cycle_uid si c'est une string
                    if (!carnet && typeof collecte.cycle_id === 'string') {
                        const cycle = await db.cycles.where('cycle_uid').equals(String(collecte.cycle_id)).first();
                        if (cycle) {
                            montantUnit = cycle.montant_journalier || 0;
                            if (cycle.carnet_id) {
                                carnet = await db.carnets.get(Number(cycle.carnet_id));
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Erreur recherche cycle édition:', e);
                }
            }
            
            // Fallback : chercher le carnet du client
            if (!carnet && collecte.client_id) {
                try {
                    const carnetsDuClient = await db.carnets.where('client_id').equals(Number(collecte.client_id)).toArray();
                    if (carnetsDuClient && carnetsDuClient.length > 0) {
                        carnet = carnetsDuClient[0];
                    }
                } catch (e) {
                    console.warn('Erreur recherche carnet client édition:', e);
                }
            }

            // Remplir le formulaire
            document.getElementById('edit-client-nom').value = client?.nom || 'Client #' + collecte.client_id;
            document.getElementById('edit-carnet-numero').value = carnet?.numero || 'Carnet inconnu';
            document.getElementById('edit-collecte-date').value = collecte.date;
            document.getElementById('edit-collecte-pointage').value = collecte.pointage;
            document.getElementById('edit-collecte-montant-unit').value = montantUnit;
            document.getElementById('edit-collecte-montant-total').value = collecte.montant;

            // Mettre à jour le calcul automatique du montant total
            const pointageField = document.getElementById('edit-collecte-pointage');
            pointageField.onchange = () => {
                const montantTotal = pointageField.value * montantUnit;
                document.getElementById('edit-collecte-montant-total').value = montantTotal;
            };

            // Bouton Supprimer
            document.getElementById('btn-supprimer-collecte').onclick = async () => {
                if (confirm('Êtes-vous sûr de vouloir supprimer cette collecte ?')) {
                    // Récupérer la collecte avant de la supprimer
                    const collecteAvantSuppression = await db.collectes.get(Number(collecteId));

                    // Supprimer la collecte
                    await db.collectes.delete(Number(collecteId));

                    // Logique de réouverture du cycle si nécessaire
                    if (collecteAvantSuppression.cycle_id && collecteAvantSuppression.cycle_id !== null) {
                        try {
                            const cycleId = Number(collecteAvantSuppression.cycle_id);
                            if (!isNaN(cycleId) && cycleId > 0) {
                                // Récupérer le cycle
                                const cycle = await db.cycles.get(cycleId);
                                if (cycle && cycle.statut === 'termine') {
                                    // Récupérer toutes les collectes restantes du cycle
                                    const collectesDuCycle = await db.collectes.where('cycle_id').equals(cycleId).toArray();
                                    
                                    // Calculer le total des pointages
                                    const totalPointages = collectesDuCycle.reduce((sum, c) => sum + Number(c.pointage || 0), 0);
                                    
                                    // console.log('[DEBUG] Suppression - Cycle ID:', cycleId, 'Total pointages:', totalPointages, 'Statut actuel:', cycle.statut);
                                    
                                    // Si le total < 31 et le cycle est fermé, le réouvrir
                                    if (totalPointages < 31) {
                                        // console.log('[DEBUG] Réouverture du cycle après suppression - total:', totalPointages, '< 31');
                                        await db.cycles.update(cycleId, {
                                            statut: 'en_cours',
                                            synced: 0
                                        });
                                    }
                                }
                            }
                        } catch (e) {
                            console.warn('Erreur lors de la réouverture du cycle après suppression:', e);
                        }
                    }

                    bootstrap.Modal.getInstance(document.getElementById('modalEditCollecte')).hide();
                    bootstrap.Modal.getInstance(document.getElementById('modalCollectesDuJour')).hide();
                    await rafraichirStatsInterface();
                    await window.afficherCollectesDuJour();
                }
            };

            // Bouton Sauvegarder
            document.getElementById('btn-sauvegarder-collecte').onclick = async () => {
                const pointage = Number(document.getElementById('edit-collecte-pointage').value);
                const montantTotal = Number(document.getElementById('edit-collecte-montant-total').value);
                const date = document.getElementById('edit-collecte-date').value;

                // Récupérer la collecte originale pour accéder au cycle_id
                const collecteActuelle = await db.collectes.get(Number(collecteId));

                // Mettre à jour la collecte
                await db.collectes.update(Number(collecteId), {
                    pointage: pointage,
                    montant: montantTotal,
                    date: date,
                    synced: 0 // Reinitialize à 0 pour marquer comme modifié
                });

                // Logique de réouverture du cycle si nécessaire
                if (collecteActuelle.cycle_id && collecteActuelle.cycle_id !== null) {
                    try {
                        const cycleId = Number(collecteActuelle.cycle_id);
                        if (!isNaN(cycleId) && cycleId > 0) {
                            // Récupérer le cycle
                            const cycle = await db.cycles.get(cycleId);
                            if (cycle && cycle.statut === 'termine') {
                                // Récupérer toutes les collectes du cycle
                                const collectesDuCycle = await db.collectes.where('cycle_id').equals(cycleId).toArray();
                                
                                // Calculer le total des pointages
                                const totalPointages = collectesDuCycle.reduce((sum, c) => sum + Number(c.pointage || 0), 0);
                                
                                // console.log('[DEBUG] Cycle ID:', cycleId, 'Total pointages:', totalPointages, 'Statut actuel:', cycle.statut);
                                
                                // Si le total < 31 et le cycle est fermé, le réouvrir
                                if (totalPointages < 31) {
                                    // console.log('[DEBUG] Réouverture du cycle - total:', totalPointages, '< 31');
                                    await db.cycles.update(cycleId, {
                                        statut: 'en_cours',
                                        synced: 0
                                    });
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('Erreur lors de la réouverture du cycle:', e);
                    }
                }

                alert('Collecte modifiée avec succès !');
                bootstrap.Modal.getInstance(document.getElementById('modalEditCollecte')).hide();
                bootstrap.Modal.getInstance(document.getElementById('modalCollectesDuJour')).hide();
                await rafraichirStatsInterface();
                await window.afficherCollectesDuJour();
            };

            // Fermer la modal des collectes et afficher celle d'édition
            bootstrap.Modal.getInstance(document.getElementById('modalCollectesDuJour')).hide();
            new bootstrap.Modal(document.getElementById('modalEditCollecte')).show();
        } catch (error) {
            console.error('Erreur édition collecte:', error);
            alert('Erreur lors de l\'édition de la collecte.');
        }
    };

</script>

@endsection