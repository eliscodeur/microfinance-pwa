@extends('pwa.layouts.app')

@section('header')
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

    /* Style épuré sans bordure pour coller exactement à la capture d'écran */
    .search-input-clean {
        font-size: 1.15rem;
        font-weight: 500;
        color: #333;
    }
    .search-input-clean::placeholder {
        color: #757575;
        font-weight: 500;
    }
    
    /* ANNULATION STRICTE DE LA BORDURE BLEUE AU FOCUS */
    .search-input-clean:focus {
        outline: none !important;
        box-shadow: none !important;
        border-color: transparent !important;
        background-color: transparent !important;
    }

    .search-full-width {
        width: 100%;
    }
</style>

<div class="d-flex align-items-center w-100 position-relative">
    
    <div class="d-flex align-items-center w-100" id="header-normal-state">
        <button onclick="toggleSidebar()" class="btn btn-link text-dark p-0 me-3 border-0">
            <i class="bi bi-list fs-3 me-3"></i>
        </button>
        <h5 class="fw-bold mb-0 text-primary flex-grow-1">
            <i class="bi bi-people-fill me-2"></i>Mes Clients <span class="badge bg-light text-primary border" id="clientCount">0</span>
        </h5> 
        <button onclick="openSearch()" class="btn btn-link text-dark p-0 border-0" id="loupe-btn">
            <i class="bi bi-search fs-4"></i>
        </button>
    </div>

    <div class="d-flex align-items-center search-full-width" id="header-search-state" style="display: none !important;">
        <button onclick="closeAndResetSearch()" class="btn btn-link text-dark p-0 me-3 border-0">
            <i class="bi bi-arrow-left fs-3"></i>
        </button>
        
        <div class="flex-grow-1">
            <input type="text" id="searchInput" class="form-control border-0 bg-transparent p-0 search-input-clean" placeholder="Chercher par nom, téléphone ou carnet...">
        </div>
    </div>

</div>
@endsection

@section('content')
<div class="container py-3" style="padding-bottom: 80px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        </div>

    {{-- Zone des clients alimentée dynamiquement --}}
    <div id="clientsContainer" class="row g-2">
        <div class="text-center py-5">
            <div class="spinner-border text-primary spinner-border-sm"></div>
            <p class="text-muted small mt-2">Chargement de la base locale...</p>
        </div>
    </div>
</div>

<script type="module">
    import { db, getAgentDB } from '/js/db-manager.js'; 
    window.db = db;

    /**
     * 1. Initialisation et vérification de la base IndexedDB
     */
    async function init() {
        try {
            const database = getAgentDB();
            if (!database.isOpen()) {
                await database.open();
            }
            await displayClients();
        } catch (err) {
            console.error("Impossible d'ouvrir la base de données:", err);
        }
    }

    /**
     * 2. Rendu applicatif et filtrage des bénéficiaires
     */
    async function displayClients(filter = "") {
        const container = document.getElementById('clientsContainer');
        const countBadge = document.getElementById('clientCount');
        if (!container) return;

        try {
            const [clients, allCarnets, cycles] = await Promise.all([
                db.clients.orderBy('nom').toArray(),
                db.carnets.toArray(),
                db.cycles.where('statut').equals('en_cours').toArray()
            ]);

            const activeCycles = {};
            cycles.forEach(c => activeCycles[c.carnet_id] = c);

            const carnetCounts = {};
            allCarnets.forEach(car => {
                carnetCounts[car.client_id] = (carnetCounts[car.client_id] || 0) + 1;
            });

            let filteredClients = clients;
            if (filter) {
                const lowFilter = filter.toLowerCase();
                const terms = lowFilter.split(/\s+/);

                filteredClients = clients.filter(c => {
                    const nomComplet = `${c.nom} ${c.prenom}`.toLowerCase();
                    const prenomNom = `${c.prenom} ${c.nom}`.toLowerCase();
                    const tel = (c.telephone || "").replace(/\s/g, "");

                    return terms.every(term => 
                        nomComplet.includes(term) || 
                        prenomNom.includes(term) || 
                        tel.includes(term)
                    );
                });
            }

            if (countBadge) {
                countBadge.textContent = `${filteredClients.length}`;
            }

            if (filteredClients.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search mb-2" style="font-size: 2rem;"></i>
                        <p class="small">Aucun client trouvé pour "${filter}"</p>
                    </div>`;
                return;
            }

            container.innerHTML = filteredClients.map(client => {
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

    window.openCarnet = function(clientId) {
        window.location.href = `/pwa/carnet?client_id=${clientId}`;
    }

    // Gestion de l'écouteur unique de l'input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => displayClients(e.target.value), 200);
        });
    }

    /**
     * 3. Contrôle des transitions d'états du header
     */
    window.openSearch = function() {
        document.getElementById('header-normal-state').classList.remove('d-flex');
        document.getElementById('header-normal-state').style.setProperty('display', 'none', 'important');
        
        const searchState = document.getElementById('header-search-state');
        searchState.style.setProperty('display', 'flex', 'important');
        
        setTimeout(() => {
            document.getElementById('searchInput').focus();
        }, 50);
    }

    window.closeAndResetSearch = function() {
        const searchInput = document.getElementById('searchInput');
        
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        
        document.getElementById('header-search-state').classList.remove('d-flex');
        document.getElementById('header-search-state').style.setProperty('display', 'none', 'important');
        
        document.getElementById('header-normal-state').style.setProperty('display', 'flex', 'important');
    }

    init();
</script>
@endsection