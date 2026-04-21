@extends('admin.layouts.sidebar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Agents</h2>
    <div>
        <a href="{{ route('admin.agents.export', 'csv') }}" class="btn btn-outline-secondary btn-sm">Exporter CSV</a>
        <a href="{{ route('admin.agents.export', 'excel') }}" class="btn btn-outline-success btn-sm">Exporter Excel</a>
    </div>
</div>

<table class="table table-hover border">
    <thead class="table-light">
        <tr>
            <th>Code Agent</th>
            <th>Nom</th>
            <th>Téléphone</th>
            @can('Activer/Désactiver')
            <th class="text-center">Actif</th>
            @endcan
            @can('Gérer Sync')  
            <th class="text-center">Synchro</th>
            @endcan
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
@foreach($agents as $agent)
    <tr id="agent-row-{{ $agent->id }}">
        <td><span class="badge bg-light text-dark border">{{ $agent->code_agent }}</span></td>
        <td><strong>{{ $agent->nom }}</strong></td>
        <td>{{ $agent->telephone }}</td>
        
        @can('Activer/Désactiver')
        <td class="text-center">
            
            <button type="button" id="btn-status-{{ $agent->id }}" 
                    class="btn btn-link p-0 border-0 shadow-none" 
                    onclick="fastToggleStatus({{ $agent->id }}, {{ $agent->actif ? 'true' : 'false' }})">
                @if($agent->actif)
                    <i class="bi bi-toggle2-on text-success" style="font-size: 1.8rem;"></i>
                @else
                    <i class="bi bi-toggle2-off text-secondary" style="font-size: 1.8rem;"></i>
                @endif
            </button>
                
        </td>
        @endcan
        @can('Gérer Sync')
        <td class="text-center">
            <button type="button" id="btn-sync-{{ $agent->id }}" 
                    class="btn btn-link p-0 border-0" 
                    onclick="confirmSync({{ $agent->id }}, {{ $agent->can_sync ? 'true' : 'false' }}, '{{ addslashes($agent->nom) }}')">
                @if($agent->can_sync)
                    <i class="bi bi-cloud-check-fill text-primary" style="font-size: 1.5rem;"></i>
                @else
                    <i class="bi bi-cloud-slash text-muted" style="font-size: 1.5rem;"></i>
                @endif
            </button>   
        </td>
        @endcan

        <td>
            <div class="d-flex gap-1">
                <a href="{{ route('admin.agents.show', $agent->id) }}" class="btn btn-sm btn-info text-white">
                    <i class="bi bi-eye"></i>
                </a>
                @can('Modifier données')   
                <a href="{{ route('admin.agents.edit', $agent->id) }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i>
                </a>
                @endcan
                @can('Supprimer données')
                    <button type="button" class="btn btn-sm btn-danger" 
                    onclick="confirmDelete({{ $agent->id }}, '{{ addslashes($agent->nom) }}')" title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
                @endcan
            </div>
        </td>
    </tr>
@endforeach
    </tbody>
</table>

{{ $agents->links() }}

<div class="modal fade" id="syncModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i> Autorisation de Synchro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p id="syncModalMessage" class="fs-5"></p>
                <p class="text-muted small">L'agent pourra télécharger les données sur son mobile.</p>
            </div>
            <div class="modal-footer justify-content-center border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="confirmSyncBtn" class="btn btn-primary" onclick="executeSyncToggle()">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-trash3 me-2"></i> Supprimer l'agent
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-person-x text-danger" style="font-size: 3rem;"></i>
                </div>
                
                <p class="fs-5">Voulez-vous supprimer l'agent <br>
                    <strong id="delAgentName" class="text-danger text-uppercase"></strong> ?
                </p>
                
                <div class="alert alert-warning small py-3 mt-3 border-0 shadow-sm text-start">
                    <div class="d-flex">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                        <div>
                            <strong>Règle de sécurité :</strong><br>
                            Seuls les agents n'ayant <strong>jamais</strong> eu d'activité (collectes ou clients) peuvent être supprimés. 
                            Pour les autres, utilisez la <strong>désactivation</strong>.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-center border-0 pb-4">
                <button type="button" class="btn btn-light px-4 border" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="confirmDelBtn" class="btn btn-danger px-4 shadow-sm" onclick="executeDelete()">
                    Confirmer la suppression
                </button>
            </div>

        </div> 
    </div> 
</div>
<script>
let currentAgentId = null;
let currentSyncState = null;

// Fonction pour ouvrir le modal de synchro
function confirmSync(id, currentState, name) {
    currentAgentId = id;
    currentSyncState = currentState;
    const action = currentState ? 'révoquer' : 'accorder';
    
    document.getElementById('syncModalMessage').innerHTML = `Voulez-vous <strong>${action}</strong> l'accès à la synchro pour <strong>${name}</strong> ?`;
    
    const modal = new bootstrap.Modal(document.getElementById('syncModal'));
    modal.show();
}

// Fonction AJAX pour traiter le changement sans recharger
// On utilise les variables globales définies plus haut (currentAgentId, currentSyncState)
    async function fastToggleStatus(agentId, currentState) {
        const btn = document.getElementById(`btn-status-${agentId}`);
        
        // On désactive le bouton temporairement pour éviter le "spam" de clics
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.5';

        try {
            const response = await fetch(`/admin/agents/${agentId}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                const isNowActive = data.actif;

                // Mise à jour de l'icône
                btn.innerHTML = isNowActive 
                    ? '<i class="bi bi-toggle2-on text-success" style="font-size: 1.8rem;"></i>' 
                    : '<i class="bi bi-toggle2-off text-secondary" style="font-size: 1.8rem;"></i>';
                
                // Mise à jour du onclick pour le prochain clic
                btn.setAttribute('onclick', `fastToggleStatus(${agentId}, ${isNowActive})`);

                // Petite notification discrète
                showToast(`${data.nom} est maintenant ${isNowActive ? 'actif' : 'inactif'}.`);
            }
        } catch (error) {
            showToast("Erreur lors de la modification", "danger");
        } finally {
            // On réactive le bouton
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
        }
    }
    async function executeSyncToggle() {
        const btn = document.getElementById('confirmSyncBtn');
        btn.disabled = true; // On bloque le bouton pour éviter les doubles clics
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>...';

        try {
            const response = await fetch(`/admin/agents/${currentAgentId}/toggle-sync`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                // 1. On met à jour l'icône dans le tableau sans recharger
                const rowBtn = document.getElementById(`btn-sync-${currentAgentId}`);
                const isNowSynced = data.can_sync;
                
                rowBtn.innerHTML = isNowSynced 
                    ? '<i class="bi bi-cloud-check-fill text-primary fs-4"></i>' 
                    : '<i class="bi bi-cloud-slash text-muted fs-4"></i>';
                
                // On met à jour l'attribut onclick pour le prochain clic
                rowBtn.setAttribute('onclick', `confirmSync(${currentAgentId}, ${isNowSynced}, '${data.agent_name.replace(/'/g, "\\'")}')`);

                // 2. ON FERME LE MODAL (C'est cette ligne qui te manquait)
                const modalElement = document.getElementById('syncModal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                modalInstance.hide();

                // 3. On affiche la petite notification (Toast)
                showToast(`Synchro ${isNowSynced ? 'autorisée' : 'révoquée'} pour ${data.agent_name}`);
            }
        } catch (error) {
            showToast("Erreur lors de la synchronisation", "danger");
        } finally {
            btn.disabled = false;
            btn.innerText = 'Confirmer';
        }
    }
// Fonction pour afficher un message rapide (Toast)
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
let agentToDeleteId = null;

function confirmDelete(id, name) {
    // 1. On stocke l'ID pour la requête Fetch
    agentToDeleteId = id;
    
    // 2. On injecte UNIQUEMENT le nom dans le span dédié
    const nameSpan = document.getElementById('delAgentName');
    if (nameSpan) {
        nameSpan.innerText = name;
    }

    // 3. On affiche le modal proprement avec l'API Bootstrap 5
    const modalElement = document.getElementById('deleteModal');
    const myModal = new bootstrap.Modal(modalElement);
    myModal.show();
}

async function executeDelete() {
    const btn = document.getElementById('confirmDelBtn');
    
    // UI : Chargement
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';

    try {
        const response = await fetch(`/admin/agents/${agentToDeleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            // Suppression réussie : on retire la ligne du tableau
            const row = document.getElementById(`agent-row-${agentToDeleteId}`);
            if (row) row.remove();
            
            // Fermeture du modal
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modalInstance.hide();

            showToast(data.message || "Agent supprimé.", "success");
        } else {
            // Erreur métier (ex: historique existant)
            showToast(data.message || "Impossible de supprimer cet agent.", "danger");
            
            // Fermeture automatique même en cas d'erreur pour ne pas bloquer l'écran
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        }
    } catch (error) {
        showToast("Erreur de communication avec le serveur.", "danger");
    } finally {
        btn.disabled = false;
        btn.innerText = "Confirmer la suppression";
    }
}
</script>
@endsection