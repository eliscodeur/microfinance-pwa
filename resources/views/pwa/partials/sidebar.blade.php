<div id="sidebar-overlay" onclick="toggleSidebar()" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
</div>

<div id="pwa-sidebar" 
     style="position:fixed; top:0; left:-280px; width:280px; height:100%; background:#fff; z-index:2001; transition: 0.3s ease; box-shadow: 2px 0 10px rgba(0,0,0,0.2);">
    
    <div class="p-4" style="background: var(--nana-blue, #0d6efd); color: #fff;">
        <div class="d-flex align-items-center">
            <div class="agent-avatar-circle" style="margin-left:0; border-color:#fff;">
                <img id="agent-photo" src="/images/default-avatar.png">
            </div>
            <div class="ms-3">
                <div class="fw-bold">Menu Agent</div>
                <div class="small opacity-75" id="agent"></div>
            </div>
        </div>
    </div>

    <div class="list-group list-group-flush mt-2" id="sidebar-menu-links">
        <a href="/pwa/dashboard" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-house-door me-3 fs-5"></i> Accueil
        </a>
        <a href="/pwa/cycles-liste" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-arrow-repeat me-3 fs-5"></i> Liste des Cycles
        </a>
        <a href="/pwa/collectes-liste" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-cash-stack me-3 fs-5"></i> Liste des Collectes
        </a>
        <a href="/pwa/stats" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-graph-up-arrow me-3 fs-5"></i> Permormances & stats
        </a>

        <hr class="mx-3 my-2">

        <a href="/pwa/security-pin" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-shield-lock me-3 fs-5"></i> Changer mon Code PIN
        </a>
        <a href="javascript:void(0)" onclick="deconnexion()" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-power me-3 fs-5"></i> Déconnexion
        </a>
    </div>
</div>

<style>
    /* --- STYLE STRICT DE L'ÉLÉMENT SÉLECTIONNÉ --- */
    #sidebar-menu-links .list-group-item {
        border-left: 4px solid transparent !important;
        transition: all 0.2s ease;
    }
    
    /* Quand l'élément est actif, on applique ta charte graphique */
    #sidebar-menu-links .list-group-item.active {
        background-color: #f1f3f9 !important; /* Fond gris/bleu très doux */
        color: var(--nana-blue, #0d6efd) !important; /* Texte passe à la couleur principale */
        font-weight: 700 !important;
        border-left: 4px solid var(--nana-blue, #0d6efd) !important; /* Barre d'ancrage à gauche */
    }

    /* Optionnel : On force les icônes à garder leur couleur ou à hériter du bleu */
    #sidebar-menu-links .list-group-item.active i {
        color: var(--nana-blue, #0d6efd) !important;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Récupération du chemin de l'URL actuelle (ex: /pwa/cycles-liste)
        const currentPath = window.location.pathname;
        
        // 2. Sélection de tous les liens du menu de navigation
        const menuLinks = document.querySelectorAll("#sidebar-menu-links a");

        menuLinks.forEach(link => {
            // Extraction du chemin du href du lien
            const linkPath = link.getAttribute("href");

            // 3. Comparaison stricte
            if (currentPath === linkPath) {
                link.classList.add("active");
            } else {
                link.classList.remove("active");
            }
        });
    });
</script>