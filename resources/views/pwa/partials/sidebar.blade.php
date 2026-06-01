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
            <i class="bi bi-house-door"></i> Accueil
        </a>
        <a href="/pwa/cycles-liste" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-arrow-repeat"></i> Liste des Cycles
        </a>
        <a href="/pwa/collectes-liste" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-cash-stack"></i> Liste des Collectes
        </a>
        <a href="/pwa/stats" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-graph-up-arrow"></i> Permormances & stats
        </a>

        <hr class="mx-3 my-2">

        <a href="/pwa/security-pin" class="list-group-item list-group-item-action border-0 py-3 px-4">
            <i class="bi bi-shield-lock"></i> Sécurité
        </a>
        <a href="javascript:void(0)" onclick="deconnexion()" class="list-group-item list-group-item-action border-0 py-3 px-4 text-danger">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
    </div>
</div>

<style>
    /* --- STYLE DE L'ÉLÉMENT SÉLECTIONNÉ (CHARTE NANA) --- */
    #sidebar-menu-links .list-group-item {
        border-left: 4px solid transparent !important;
        transition: all 0.2s ease;
        display: flex !important;
        align-items: center !important; /* Aligne horizontalement l'icône et le texte */
    }
    
    #sidebar-menu-links .list-group-item.active {
        background-color: #f1f3f9 !important; 
        color: var(--nana-blue, #0d6efd) !important; 
        font-weight: 700 !important;
        border-left: 4px solid var(--nana-blue, #0d6efd) !important; 
    }

    /* --- NETTOYAGE ET CENTRAGE GEOMÉTRIQUE STRICT DES ICÔNES --- */
    #sidebar-menu-links .list-group-item i {
        width: 38px !important;
        height: 38px !important;
        
        /* Box-model Flex centré au pixel près */
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        
        border-radius: 10px !important;
        padding: 0 !important;
        margin: 0 14px 0 0 !important; /* Marge à droite uniquement pour pousser le texte */
        
        /* Réinitialisation de la taille et suppression de la hauteur de ligne textuelle */
        font-size: 1.2rem !important; 
        line-height: 1 !important;
        flex-shrink: 0 !important;
    }

    /* Recalage du moteur de rendu interne de Bootstrap Icons (Le pseudo-élément) */
    #sidebar-menu-links .list-group-item i::before {
        display: inline-block !important;
        line-height: 1 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* ATTRIBUTION DES PALETTES DE COULEURS SOFTS */
    #sidebar-menu-links .list-group-item:nth-of-type(1) i { background-color: #e6f0ff !important; color: #0d6efd !important; } /* Accueil */
    #sidebar-menu-links .list-group-item:nth-of-type(2) i { background-color: #fff3cd !important; color: #fd7e14 !important; } /* Cycles */
    #sidebar-menu-links .list-group-item:nth-of-type(3) i { background-color: #e2f6ed !important; color: #198754 !important; } /* Collectes */
    #sidebar-menu-links .list-group-item:nth-of-type(4) i { background-color: #f3e5f5 !important; color: #6f42c1 !important; } /* Stats */
    
    #sidebar-menu-links .list-group-item:nth-of-type(5) i { background-color: #e0f2f1 !important; color: #20c997 !important; } /* Code PIN */
    #sidebar-menu-links .list-group-item:nth-of-type(6) i { background-color: #f8d7da !important; color: #dc3545 !important; } /* Déconnexion */

    /* Préservation de l'état actif (Garde ta charte mais avec l'icône sur fond plein) */
    #sidebar-menu-links .list-group-item.active i {
        background-color: var(--nana-blue, #0d6efd) !important;
        color: #fff !important;
    }
    
    #sidebar-menu-links .list-group-item.active.text-danger i {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
</style>

<script>
    // --- 1. SÉCURITÉ DE SESSION ET INITIALISATION (ANTI-CLIGNOTEMENT AUTOMATIQUE) ---
    (function() {
        const sessionActive = localStorage.getItem('session_active');
        
        if (sessionActive !== 'true') {
            document.documentElement.style.display = 'none'; 
            window.location.replace("/agent/login");
            return;
        }

        const matricule = localStorage.getItem('current_agent_matricule'); 
        const auth = "auth_v1_" + matricule;
        const authAgent = JSON.parse(localStorage.getItem(auth));
        
        if (!authAgent) {
            document.documentElement.style.display = 'none';
            window.location.replace("/agent/login");
            return;
        }

        // Hydratation du nom de l'agent
        const nom = (authAgent.nom || '').toUpperCase();
        const nameEl = document.getElementById('agent');
        if (nameEl) {  
            nameEl.innerText = `${nom}`.trim();
        }
        
        // Hydratation et vérification de la photo de l'agent
        const elPhoto = document.getElementById('agent-photo');
        const baseUrl = window.location.origin;
        const defaultAvatar = baseUrl + '/images/default-avatar.png';

        if (elPhoto) {
            if (authAgent.photo && authAgent.photo.trim() !== "") {
                const photoPath = authAgent.photo.startsWith('/') ? authAgent.photo : '/' + authAgent.photo;
                elPhoto.src = baseUrl + '/storage/' + photoPath;

                elPhoto.onerror = function() {
                    if (this.src !== defaultAvatar) {
                        this.src = defaultAvatar;
                    }
                    this.onerror = null; 
                };
            } else {
                elPhoto.src = defaultAvatar;
            }
        }

        // Redirection forcée si une synchronisation terrain a été coupée en plein vol
        const pendingSync = localStorage.getItem('pending_sync_job');
        if (pendingSync && window.location.pathname !== "{{ route('pwa.sync', [], false) }}") {
            window.location.replace("{{ route('pwa.sync') }}?resume=1");
        }
    })();

    // --- 2. LOGIQUE ACTIVE DU MENU LATÉRAL ---
    document.addEventListener("DOMContentLoaded", function () {
        const currentPath = window.location.pathname;
        const menuLinks = document.querySelectorAll("#sidebar-menu-links a");

        menuLinks.forEach(link => {
            const linkPath = link.getAttribute("href");
            if (currentPath === linkPath) {
                link.classList.add("active");
            } else {
                link.classList.remove("active");
            }
        });
    });

    // --- 3. FONCTION DE DÉCONNEXION INTERCEPTÉE PAR SWEETALERT ---
    function deconnexion() {
        if (typeof toggleSidebar === 'function') {
            toggleSidebar();
        }

        Swal.fire({
            title: 'Déconnexion',
            text: 'Voulez-vous vraiment vous déconnecter de votre espace terrain ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545', 
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, me déconnecter',
            cancelButtonText: 'Annuler',
            reverseButtons: true 
        }).then((result) => {
            if (result.isConfirmed) {
                localStorage.setItem('session_active', 'false');
                localStorage.removeItem('current_agent_matricule');
                sessionStorage.clear();
                window.location.replace("/agent/login");
            }
        });
    }
</script>