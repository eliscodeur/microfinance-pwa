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

    <header class="pwa-header shadow-sm">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center">
                @if(View::hasSection('header_left'))
                    <div class="me-2">@yield('header_left')</div>
                @endif
                <div class="logo-container">
                    <img src="{{ asset('icons/icon-192x192.png') }}" class="logo-img" alt="Logo">
                </div>
                <span class="brand-text">Nana<span class="brand-subtext">Eco</span></span>
            </div>

            <div class="d-flex align-items-center">
                <div id="status-icons" class="me-3">
                    <i id="online-icon" class="bi bi-wifi" style="color: var(--nana-green); font-size: 1.4rem;"></i>
                    <i id="offline-icon" class="bi bi-wifi-off animate-pulse" style="color: #dc3545; font-size: 1.4rem; display: none;"></i>
                </div>

                <button onclick="deconnexion()" class="btn btn-link text-danger p-0 border-0">
                    <i class="bi bi-box-arrow-right fs-4"></i> 
                </button>

                <div class="agent-avatar-circle">
                    <img id="agent-photo" src="/images/default-avatar.png">
                </div>
            </div>
        </div>
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

        const photoUrl = localStorage.getItem('agent_photo'); // Contient l'URL entière ou le chemin
        
        const elPhoto = document.getElementById('agent-photo');
        elPhoto.src = '/storage/' + photoUrl; // Construction du chemin complet 
        console.log("Chargement de la photo depuis:", elPhoto.src);

        // Si l'image n'est ni sur le réseau ni en cache, on bascule sur l'avatar par défaut
        elPhoto.onerror = function() {
            this.src = window.location.origin + '/images/default-avatar.png';
            this.onerror = null; 
        };

        // Redirection si synchro interrompue
        const pendingSync = localStorage.getItem('pending_sync_job');
        if (pendingSync && window.location.pathname !== "{{ route('pwa.sync', [], false) }}") {
            window.location.replace("{{ route('pwa.sync') }}?resume=1");
        }
    })();

    function deconnexion() {
        localStorage.setItem('session_active', 'false');
        sessionStorage.clear();
        window.location.replace("/agent/login");
    }

    // --- GESTION DU STATUT ON/OFFLINE ---
    function updateOnlineStatus() {
        const isOnline = navigator.onLine;
        document.getElementById('online-icon').style.display = isOnline ? 'block' : 'none';
        document.getElementById('offline-icon').style.display = isOnline ? 'none' : 'block';
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus(); // Init au chargement

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
        .then(() => console.log("Service Worker enregistré !"))
        .catch(err => console.log("Erreur SW :", err));
    }
</script>

</body>
</html>