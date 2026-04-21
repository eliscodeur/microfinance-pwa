@extends('pwa.layouts.app')

@section('header_left')
    <a href="{{ route('pwa.index') }}" class="text-white me-3"><i class="bi bi-chevron-left fs-4"></i></a>
    <span class="fw-bold">Mes Clients</span>
@endsection

@section('content')
<div class="container py-3">
    
    <div class="input-group mb-3 shadow-sm rounded-3 overflow-hidden">
        <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="searchInput" class="form-control border-0 py-2" placeholder="Nom ou N° de carnet...">
    </div>

    <div id="clientsList">
        <div class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm mb-2"></div><br>
            Chargement des clients locaux...
        </div>
    </div>
</div>

<script>
    // Fonction pour afficher les clients depuis Dexie
    async function renderClients(filter = '') {
        const container = document.getElementById('clientsList');
        // On récupère les clients de Dexie
        let clients = await db.clients.toArray();

        // Filtrage si recherche
        if(filter) {
            clients = clients.filter(c => 
                c.nom.toLowerCase().includes(filter.toLowerCase()) || 
                c.carnet.toString().includes(filter)
            );
        }

        if(clients.length === 0) {
            container.innerHTML = '<div class="text-center py-5 opacity-50">Aucun client trouvé</div>';
            return;
        }

        container.innerHTML = clients.map(client => `
            <div class="card card-pwa shadow-sm border-0 mb-3" onclick="window.location='/pwa/collecte?id=${client.id}'">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="bi bi-person text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold text-dark">${client.nom} ${client.prenom || ''}</h6>
                            <div class="small text-muted">
                                Carnet: <span class="fw-bold text-primary">#${client.carnet}</span> | 
                                Mise: <span class="badge bg-light text-dark border">${client.mise_journaliere} F</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Ecouteur sur la barre de recherche
    document.getElementById('searchInput').addEventListener('input', (e) => {
        renderClients(e.target.value);
    });

    // Initialisation au chargement
    document.addEventListener('DOMContentLoaded', () => {
        renderClients();
    });
</script>

<style>
    .card-pwa:active {
        background-color: #f8f9fa;
        transform: scale(0.98);
        transition: 0.1s;
    }
</style>
@endsection