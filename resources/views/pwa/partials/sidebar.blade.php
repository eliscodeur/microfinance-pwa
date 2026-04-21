<div id="sidebar-overlay" onclick="toggleSidebar()" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
</div>

<div id="pwa-sidebar" 
     style="position:fixed; top:0; left:-280px; width:280px; height:100%; background:#fff; z-index:2001; transition: 0.3s ease; box-shadow: 2px 0 10px rgba(0,0,0,0.2);">
    
    <div class="p-4" style="background: var(--nana-blue); color: #fff;">
        <div class="d-flex align-items-center">
            <div class="agent-avatar-circle" style="margin-left:0; border-color:#fff;">
                <img id="agent-photo" src="/images/default-avatar.png">
            </div>
            <div class="ms-3">
                <div class="fw-bold">Menu Agent</div>
                <div class="small opacity-75">Nana Eco Consulting</div>
            </div>
        </div>
    </div>

    <div class="list-group list-group-flush mt-2">
        <a href="/pwa/dashboard" class="list-group-item list-group-item-action border-0 py-3">
            <i class="bi bi-house-door me-3 text-primary"></i> Accueil
        </a>
        <a href="/pwa/cycles-liste" class="list-group-item list-group-item-action border-0 py-3">
            <i class="bi bi-arrow-repeat me-3 text-success"></i> Liste des Cycles
        </a>
        <a href="/pwa/collectes-liste" class="list-group-item list-group-item-action border-0 py-3">
            <i class="bi bi-cash-stack me-3 text-warning"></i> Liste des Collectes
        </a>
        <hr class="mx-3">
        <a href="javascript:void(0)" onclick="deconnexion()" class="list-group-item list-group-item-action border-0 py-3 text-danger">
            <i class="bi bi-power me-3"></i> Déconnexion
        </a>
    </div>
</div>