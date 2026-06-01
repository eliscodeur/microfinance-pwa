@extends('pwa.layouts.app')
@section('header')
<div class="d-flex justify-content-between align-items-center w-100">
    <div class="d-flex align-items-center">
        <button onclick="toggleSidebar()" class="btn btn-link text-dark p-0 me-3 border-0">
            <i class="bi bi-list fs-3 me-3"></i>
        </button>
        <div class="logo-container">
            <img src="{{ asset('icons/icon-192x192.png') }}" class="logo-img" alt="Logo">
        </div>
        <span class="brand-text">Nana<span class="brand-subtext">Eco</span></span>
    </div>

    <!-- <div class="d-flex align-items-center">
        <div id="status-icons" class="me-3">
            <i id="online-icon" class="bi bi-wifi" style="color: var(--nana-green); font-size: 1.4rem;"></i>
            <i id="offline-icon" class="bi bi-wifi-off animate-pulse" style="color: #dc3545; font-size: 1.4rem; display: none;"></i>
        </div>
    </div> -->
</div>
@endsection
@section('content')

<div class="container mt-n3">
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 cursor-pointer" style="cursor: pointer;">
        <div class="row text-center align-items-center g-0">
            <div class="col-4 border-end" onclick="goToCollecte()" style="cursor: pointer;">
                <small class="text-muted d-block mb-1" style="font-size: 0.70rem; white-space: nowrap;">
                    <i class="bi bi-cash-coin text-success me-1"></i>Collectes Jour
                </small>
                <span class="fw-bold text-success" id="total-montant" style="font-size: 0.85rem; white-space: nowrap;">0 F</span>
            </div>

            <div class="col-4 border-end" style="padding: 0 2px;">
                <small class="text-muted d-block mb-1" style="font-size: 0.62rem; line-height: 1.1; font-weight: 500;">
                    <i class="bi bi-check2-circle text-primary me-1"></i>Clients Encaissés
                </small>
                <span class="fw-bold text-dark" id="total-clients-vus" style="font-size: 1.1rem;">0</span>
            </div>

            <div class="col-4">
                <small class="text-muted d-block mb-1" style="font-size: 0.70rem; white-space: nowrap;">
                    <i class="bi bi-people text-secondary me-1"></i>Clients actifs
                </small>
                <span class="fw-bold text-primary" id="total-clients-actifs" style="font-size: 1.1rem;">0</span>
            </div>
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
<style>
    .client-card-animate { animation: fadeInUp 0.3s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .badge-carnet { background: #f0f7ff; color: #0061f2; border: 1px solid #d0e3ff; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; font-weight: 600; }
    .btn-next { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; color: #0d6efd; transition: 0.2s; }
</style>

<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js'; 

    try {
        // On récupère l'instance réelle pour vérifier l'ouverture
        const database = getAgentDB(); 
        if (!database) {
            window.location.replace("{{ route('agent.login') }}");
        }

        if (!database.isOpen()) await database.open();

        const clientCount = await database.clients.count();;
        const hasLastSync = localStorage.getItem('last_sync');

        if (clientCount === 0 || !hasLastSync) {
            window.location.replace("{{ route('pwa.sync') }}?init=1");    
        }

        await rafraichirStatsInterface();
    } catch (err) {
        console.error("Erreur au chargement de l'index:", err);
        // Si la base est corrompue, on redirige vers la synchro pour réparer
        window.location.replace("{{ route('pwa.sync') }}?init=1");
    }
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
            const idsUniques = new Set(duJour.map(c => Number(c.client_id)));
            const clientsVus = idsUniques.size;
            
            // 6b. Calculer le nombre de clients actifs distincts (qui ont au moins un cycle en_cours)
            const tousCyclesEnCours = await db.cycles.where('statut').equals('en_cours').toArray();
            const carnetIdsEnCours = tousCyclesEnCours.map(cy => cy.carnet_id);
            
            const carnetsEnCours = await db.carnets.where('id').anyOf(carnetIdsEnCours).toArray();
            const clientIdsActifsUniques = new Set(carnetsEnCours.map(car => Number(car.client_id)));
            const totalClientsActifs = clientIdsActifsUniques.size;

            // 7. Mise à jour du DOM
            const elMontant = document.getElementById('total-montant');
            const elClientsVus = document.getElementById('total-clients-vus');
            const elClientsActifs = document.getElementById('total-clients-actifs');

            if (elMontant) elMontant.innerText = total.toLocaleString('fr-FR') + " F";
            if (elClientsVus) elClientsVus.innerText = clientsVus;
            if (elClientsActifs) elClientsActifs.innerText = totalClientsActifs;

            // Petit log pour débugger dans ta console (F12)
            // console.log(`[Stats] Date: ${aujourdhui} | Collectes du jour non sync: ${duJour.length} | Clients Vus: ${clientsVus} | Clients Actifs: ${totalClientsActifs} | Total: ${total} FCFA`);

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
                const terms = query.split(/\s+/); // Découpe "Jean Dupont" en ["jean", "dupont"]
                const digitsQuery = query.replace(/\D/g, '');

                // 1. Récupération des données (On récupère tout car le filtrage combiné est complexe en index pur)
                const [allClients, byCarnet] = await Promise.all([
                    db.clients.toArray(),
                    db.carnets.where('numero').startsWithIgnoreCase(query).toArray()
                ]);

                // 2. Filtrage intelligent des clients
                const resultatsClients = allClients.filter(c => {
                    const nomComplet = `${c.nom} ${c.prenom}`.toLowerCase();
                    const prenomNom = `${c.prenom} ${c.nom}`.toLowerCase();
                    
                    // Vérifie si TOUS les mots saisis sont présents (ex: "Dupont J" trouvera "Dupont Jean")
                    const matchNomPrenom = terms.every(term => 
                        nomComplet.includes(term) || prenomNom.includes(term)
                    );

                    // Filtrage téléphone
                    const matchPhone = digitsQuery.length > 2 && 
                        c.telephone && c.telephone.replace(/\D/g, '').includes(digitsQuery);

                    return matchNomPrenom || matchPhone;
                });

                // 3. Récupérer les IDs des clients liés aux carnets trouvés
                let clientsViaCarnets = [];
                if (byCarnet.length > 0) {
                    const clientIds = [...new Set(byCarnet.map(car => car.client_id))];
                    clientsViaCarnets = allClients.filter(c => clientIds.includes(c.id));
                }

                // 4. Fusionner et dédoublonner
                const mapResultats = new Map();
                [...resultatsClients, ...clientsViaCarnets].forEach(c => {
                    if (c && c.id) mapResultats.set(c.id, c);
                });

                const resultats = Array.from(mapResultats.values());

                if (resultats.length === 0) {
                    area.innerHTML = `<div class="text-center p-5 text-muted small">Aucun résultat pour "${rawQuery}"</div>`;
                    return;
                }

                // 5. Préchargement pour l'affichage
                const [tousLesCarnets, tousCycles] = await Promise.all([
                    db.carnets.toArray(),
                    db.cycles.toArray()
                ]);

                // 6. Construction du HTML
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
                                        <h6 class="fw-bold mb-1 nana-text-blue">${c.nom} ${c.prenom}</h6>
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
    

    window.goToCollecte = async (collecteId) => {
        // Redirige vers la page d'édition de la collecte
        window.location.href = `/pwa/collectes-liste`;
    };
    //  window.updateOnlineStatus = function(){
    //     const isOnline = navigator.onLine;
    //     document.getElementById('online-icon').style.display = isOnline ? 'block' : 'none';
    //     document.getElementById('offline-icon').style.display = isOnline ? 'none' : 'block';
    // }

</script>

@endsection