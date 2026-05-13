@extends('pwa.layouts.app')

@section('content')
<div class="container py-3" style="padding-bottom: 80px;"> {{-- Marge pour la nav du bas --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-people-fill me-2"></i>Mes Clients</h5>
        <!-- <span class="badge bg-light text-primary border" id="clientCount">0 clients</span> -->
    </div>

    {{-- Barre de recherche stylisée --}}
    <div class="input-group mb-4 shadow-sm rounded-4 overflow-hidden border-0 bg-white">
        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="searchInput" class="form-control border-0 py-3" placeholder="Nom, téléphone ou N° carnet...">
    </div>

    {{-- Zone des clients --}}
    <div id="clientsContainer" class="row g-2">
        <div class="text-center py-5">
            <div class="spinner-border text-primary spinner-border-sm"></div>
            <p class="text-muted small mt-2">Chargement de la base locale...</p>
        </div>
    </div>
</div>



<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js'; 
    window.db = db; // Pour debug global
    /**
     * 1. Initialisation et vérification de la base
     */
    async function init() {
        try {
            const database = getAgentDB();

            if (!database.isOpen()) {
                await database.open();
            }
            // Lancement du premier affichage
            await displayClients();
        } catch (err) {
            console.error("Impossible d'ouvrir la base de données:", err);
        }
    }

    /**
     * 2. Fonction de rendu des clients
     */
    async function displayClients(filter = "") {
        const container = document.getElementById('clientsContainer');
        if (!container) return;

        try {
            // Récupération des données en parallèle pour la performance
            const [clients, allCarnets, cycles] = await Promise.all([
                db.clients.orderBy('nom').toArray(),
                db.carnets.toArray(),
                db.cycles.where('statut').equals('en_cours').toArray()
            ]);

            // Map des cycles actifs pour un accès O(1)
            const activeCycles = {};
            cycles.forEach(c => activeCycles[c.carnet_id] = c);

            // Comptage des carnets par client
            const carnetCounts = {};
            allCarnets.forEach(car => {
                carnetCounts[car.client_id] = (carnetCounts[car.client_id] || 0) + 1;
            });

            // Filtrage intelligent
            let filteredClients = clients;
            if (filter) {
                const lowFilter = filter.toLowerCase();
                const terms = lowFilter.split(/\s+/);

                filteredClients = clients.filter(c => {
                    const nomComplet = `${c.nom} ${c.prenom}`.toLowerCase();
                    const prenomNom = `${c.prenom} ${c.nom}`.toLowerCase();
                    const tel = (c.telephone || "").replace(/\s/g, "");

                    // Match si TOUS les termes de recherche sont trouvés dans le nom ou tel
                    return terms.every(term => 
                        nomComplet.includes(term) || 
                        prenomNom.includes(term) || 
                        tel.includes(term)
                    );
                });
            }

            // Rendu HTML
            if (filteredClients.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search mb-2" style="font-size: 2rem;"></i>
                        <p class="small">Aucun client trouvé pour "${filter}"</p>
                    </div>`;
                return;
            }

            container.innerHTML = filteredClients.map(client => {
                // Un client est considéré "En cours" s'il a au moins un carnet avec un cycle actif
                const clientHasActive = allCarnets.some(car => car.client_id === client.id && activeCycles[car.id]); 
                const nbCarnets = carnetCounts[client.id] || 0;
                
                const initiales = `${(client.nom || "?").charAt(0)}${(client.prenom || "?").charAt(0)}`.toUpperCase();

                return `
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 mb-2 p-2 client-card ${clientHasActive ? 'border-start border-primary border-4' : ''}" 
                         onclick="window.openCarnet(${client.id})">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle ${clientHasActive ? 'bg-primary text-white' : 'bg-light text-muted'}">
                                ${initiales}
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-0 fw-bold text-dark">${client.nom} ${client.prenom}</h6>
                                    <span class="badge ${nbCarnets > 1 ? 'bg-warning text-dark' : 'bg-light text-muted'} border small">
                                        ${nbCarnets} carnet${nbCarnets > 1 ? 's' : ''}
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted me-3"><i class="bi bi-telephone me-1"></i>${client.telephone || '---'}</small>
                                    ${clientHasActive 
                                        ? `<small class="text-primary fw-bold"><i class="bi bi-check-circle-fill me-1"></i>En cours</small>` 
                                        : `<small class="text-muted"><i class="bi bi-slash-circle me-1"></i>En attente</small>`
                                    }
                                </div>
                            </div>
                            <div class="text-muted ps-2">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('');

        } catch (err) {
            console.error("Erreur d'affichage des clients:", err);
            container.innerHTML = `<div class="alert alert-danger mx-2">Erreur de chargement des données.</div>`;
        }
    }

    /**
     * 3. Bridge global et Événements
     */
    window.openCarnet = function(clientId) {
        window.location.href = `/pwa/carnet?client_id=${clientId}`;
    }

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // On utilise un petit délai (debounce) pour ne pas rafraîchir à chaque micro-frappe
        let timeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => displayClients(e.target.value), 200);
        });
    }

    // Lancement
    init();
</script>

<style>
    body { background-color: #f8f9fa; }
    
    .client-card {
        transition: transform 0.1s, background-color 0.1s;
        cursor: pointer;
    }

    .client-card:active {
        background-color: #f0f7ff !important;
        transform: scale(0.97);
    }

    .avatar-circle {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        color: white;
    }

    .bg-primary-soft {
        background-color: #e7f0ff;
    }

    .border-4 { border-left-width: 4px !important; }
</style>
@endsection