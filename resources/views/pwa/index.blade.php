@extends('pwa.layouts.app')

@section('content')

<div class="container mt-n3">
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 cursor-pointer" style="cursor: pointer;">
        <div class="row text-center">
            <div class="col-6 border-end" onclick="goToCollecte()">
                <small class="text-muted d-block">Collectes Jour</small>
                <span class="fw-bold fs-5 text-primary" id="total-montant">0 FCFA</span>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Clients vus</small>
                <span class="fw-bold fs-5 text-dark" id="total-clients-vus">0</span>
            </div>
        </div>
        <!-- <div class="text-center mt-2">
            <small class="text-muted"><i class="bi bi-pencil me-1"></i> Cliquez pour éditer</small>
        </div> -->
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

</script>

@endsection