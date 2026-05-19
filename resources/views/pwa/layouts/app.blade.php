<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#134E5E">
    
    <title>{{ config('app.name', 'Nana Eco Consulting') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animate.min.css') }}">

    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/dexie.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>

    <style>
        :root {
            --nana-blue: #134E5E;   /* Bleu du logo */
            --nana-green: #78B13F;  /* Vert du logo */
            --nana-bg: #F4F7F6;     /* Fond de l'app */
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--nana-bg);
            /* On laisse la place pour le header fixe et la nav basse */
            padding-top: 70px !important; 
            padding-bottom: 90px !important;
            min-height: 100vh;
            overscroll-behavior-y: contain;
        }

        /* --- HEADER FIXE --- */
        .pwa-header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: 65px;
            background-color: #ffffff;
            border-bottom: 2px solid var(--nana-blue);
            z-index: 1050;
            display: flex;
            align-items: center;
            padding: 0 15px;
            padding-top: env(safe-area-inset-top); /* Support encoches iPhone */
        }

        .logo-container {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .logo-img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .brand-text {
            color: var(--nana-blue); 
            font-weight: 800; 
            font-size: 1.2rem;
            margin-left: 8px;
            letter-spacing: -0.5px;
        }

        .brand-subtext {
            color: #050505;
        }

        /* --- AVATAR AGENT ROND --- */
        .agent-avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--nana-blue);
            background-color: #eee;
            margin-left: 12px;
        }

        .agent-avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- UTILITAIRES --- */
        .animate-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .4; }
        }
    </style>
</head>
<body>
    @include('pwa.partials.sidebar')
    <header class="pwa-header shadow-sm">
        @yield('header')
    </header>

    <main>
        @yield('content')
    </main>

    @include('pwa.partials.bottom-nav')

<script>
    // --- GESTION DE LA SESSION ET PHOTOS ---
    (function() {
        const sessionActive = localStorage.getItem('session_active');
       
        if (sessionActive !== 'true') {
            window.location.replace("/agent/login");
            return;
        }

        const matricule = localStorage.getItem('current_agent_matricule'); // Contient l'URL entière ou le chemin
        const auth = "auth_v1_" + matricule;
        const authAgent = JSON.parse(localStorage.getItem(auth));
        const nom = (authAgent.nom || '').toUpperCase();
        const nameEl = document.getElementById('agent');
        if (nameEl) {  
            nameEl.innerText = `${nom}`.trim();
        }
        const elPhoto = document.getElementById('agent-photo');
       
        // 1. On prépare les chemins
        const baseUrl = window.location.origin;
        const defaultAvatar = baseUrl + '/images/default-avatar.png';

        // 2. On vérifie si authAgent.photo existe et n'est pas vide/undefined
        if (authAgent && authAgent.photo && authAgent.photo.trim() !== "") {
            
            // On s'assure qu'il y a un slash entre le dossier et le nom du fichier
            const photoPath = authAgent.photo.startsWith('/') ? authAgent.photo : '/' + authAgent.photo;
            elPhoto.src = baseUrl + '/storage/' + photoPath;

            // Sécurité au cas où le fichier n'existe plus sur le serveur
            elPhoto.onerror = function() {
                if (this.src !== defaultAvatar) {
                    this.src = defaultAvatar;
                }
                this.onerror = null; 
            };

        } else {
            elPhoto.src = defaultAvatar;
        }

        // Redirection si synchro interrompue
        const pendingSync = localStorage.getItem('pending_sync_job');
        if (pendingSync && window.location.pathname !== "{{ route('pwa.sync', [], false) }}") {
            window.location.replace("{{ route('pwa.sync') }}?resume=1");
        }
    })();
    
    window.addEventListener('online', async () => {
        await verifierStatutAgentForce();
    });

    function deconnexion() {
        localStorage.setItem('session_active', 'false');
        localStorage.removeItem('current_agent_matricule');
        sessionStorage.clear();
        window.location.replace("/agent/login");
    }

    // --- GESTION DU STATUT ON/OFFLINE ---
   

    // window.addEventListener('online', updateOnlineStatus);
    // window.addEventListener('offline', updateOnlineStatus);
    // updateOnlineStatus(); // Init au chargement

    // Écouteur pour détecter le retour de la connexion
    window.addEventListener('online', async () => {
        await verifierStatutAgentForce();
    });

    // Fonction de vérification stricte
    async function verifierStatutAgentForce() {
        const matricule = localStorage.getItem('current_agent_matricule'); // Ou ta clé habituelle
        if (!matricule) return;

        try {
            // On appelle ta route protégée par le middleware
            const response = await fetch(`/pwa/check-status/${matricule}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Si le middleware renvoie 403 (Forbidden) ou si le statut est false
            if (response.status === 403 || response.status === 401) {
                await autoDestructionLocale("Votre compte a été désactivé par l'administrateur.");
                const matricule = localStorage.getItem("current_agent_matricule");
                const auth = "auth_v1_" + matricule;
                localStorage.removeItem(auth);
                localStorage.removeItem("current_agent_matricule");
                localStorage.removeItem("session_active");
                return;
            }

            const data = await response.json();
            
            if (data.actif === false) {
                await autoDestructionLocale("Accès révoqué.");
                const matricule = localStorage.getItem("current_agent_matricule");
                const auth = "auth_v1_" + matricule;
                localStorage.removeItem(auth);
                localStorage.removeItem("current_agent_matricule");
                localStorage.removeItem("session_active");
            }

        } catch (error) {
            console.error("Impossible de vérifier le statut (serveur injoignable)");
            // On ne supprime rien ici car c'est peut-être juste un timeout serveur
        }
    }

    // Procédure de nettoyage complet
    async function autoDestructionLocale(message) {
        // 1. Alerte l'utilisateur
        await Swal.fire({
            title: 'Sécurité : Accès Refusé',
            text: message,
            icon: 'error',
            confirmButtonText: 'Quitter',
            allowOutsideClick: false
        });

        try {
            const activeDB = getAgentDB(); // Ta fonction qui récupère l'instance Dexie
            if (activeDB) {
                await activeDB.delete();
                console.log("Base de données locale supprimée.");
            }
        } catch (e) {
            console.error("Erreur suppression DB:", e);
        }


        // 4. Redirection forcée
        window.location.href = '/agent/login';
    }
    // --- SÉCURITÉ (Production uniquement) ---
    @if(config('app.env') === 'production')
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.keyCode === 123 || (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74))) {
                e.preventDefault();
                alert("Accès restreint.");
            }
        });
    @endif
</script>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
        // .then(() => console.log("Service Worker enregistré !"))
        // .catch(err => console.log("Erreur SW :", err));
    }
</script>
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('pwa-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        // Si le menu est caché, on l'affiche, sinon on le cache
        if (sidebar.style.left === '0px') {
            sidebar.style.left = '-280px';
            overlay.style.display = 'none';
        } else {
            sidebar.style.left = '0px';
            overlay.style.display = 'block';
        }
    }
</script>   
</body>
</html>