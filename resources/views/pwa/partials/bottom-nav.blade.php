<style>
    :root {
        --nana-blue: #134E5E;
        --nana-green: #78B13F;
        --nana-red: #ff3b30;
        /* Nouvelle variable pour la capsule active (mélange gris-bleu très doux) */
        --nana-active-badge: #eef3f6; 
    }

    .pwa-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 68px; /* Conservé */
        background: #ffffff;
        display: flex;
        justify-content: space-around;
        align-items: center;
        border-top: 1.5px solid rgba(19, 78, 94, 0.1);
        box-shadow: 0 -4px 15px rgba(0,0,0,0.06);
        z-index: 2000;
        padding-bottom: env(safe-area-inset-bottom, 0px); 
    }

    .nav-item-pwa {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #8e8e93; /* Gris inactif conservé */
        transition: color 0.2s ease;
        position: relative;
        height: 100%;
    }

    /* --- NOUVELLE CAPSULE BADGE : CADRAGE FIXE --- */
    .nav-item-pwa .icon-wrapper {
        position: relative;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        /* Dimensions parfaites pour créer l'effet pilule/badge derrière l'icône */
        width: 56px; 
        height: 32px;
        border-radius: 16px; /* Bords totalement arrondis */
        margin-bottom: 4px;
        background-color: transparent; /* Invisible par défaut */
        transition: background-color 0.2s ease; /* Transition douce de la couleur de fond uniquement */
    }

    /* Taille stricte et immuable de l'icône interne */
    .nav-item-pwa .icon-wrapper i {
        font-size: 1.35rem !important; /* Fixé pour éviter les sauts de taille */
        line-height: 1 !important;
        display: inline-block !important;
    }

    .nav-item-pwa .icon-wrapper i::before {
        display: inline-block !important;
        line-height: 1 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .nav-item-pwa span {
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
    }

    /* --- ÉTAT ACTIF COMPLÈTEMENT STABILISÉ --- */
    .nav-item-pwa.active {
        color: var(--nana-blue);
    }

    /* Apparition du badge gris-bleu clair sans aucun saut ni déplacement vertical */
    .nav-item-pwa.active .icon-wrapper {
        background-color: var(--nana-active-badge); 
        /* Suppression du translateY(-3px) qui causait le décalage */
    }

    /* --- AJUSTEMENT DU BADGE DE SYNCHRO SUR LA CAPSULE --- */
    .sync-indicator {
        position: absolute;
        top: 2px;
        right: 14px; /* Ajusté pour se caler parfaitement sur le bord de la capsule */
        width: 10px;
        height: 10px;
        background-color: var(--nana-red);
        border: 2px solid #ffffff;
        border-radius: 50%;
        display: none; 
    }

    .sync-indicator.active {
        display: block;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 59, 48, 0.6); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 5px rgba(255, 59, 48, 0); }
        100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 59, 48, 0); }
    }

    .nav-item-pwa:active {
        background-color: rgba(19, 78, 94, 0.02);
    }
</style>
<nav class="pwa-bottom-nav">
    {{-- ACCUEIL --}}
    <a href="{{ route('pwa.index') }}" class="nav-item-pwa {{ request()->routeIs('pwa.index') ? 'active' : '' }}">
        <div class="icon-wrapper">
            <i class="bi {{ request()->routeIs('pwa.index') ? 'bi-house-door-fill' : 'bi-house-door' }}"></i>
        </div>
        <span>Accueil</span>
    </a>

    {{-- CLIENTS --}}
    <a href="{{ route('pwa.clients') }}" class="nav-item-pwa {{ request()->routeIs('pwa.clients') ? 'active' : '' }}">
        <div class="icon-wrapper">
            <i class="bi {{ request()->routeIs('pwa.clients') ? 'bi-people-fill' : 'bi-people' }}"></i>
        </div>
        <span>Clients</span>
    </a>

    {{-- GAINS --}}
    <a href="{{ route('pwa.gains') }}" class="nav-item-pwa {{ request()->routeIs('pwa.gains') || request()->is('*gains*') ? 'active' : '' }}">
        <div class="icon-wrapper">
            <i class="bi {{ request()->routeIs('pwa.gains') ? 'bi-wallet-fill' : 'bi-wallet2' }}"></i>
        </div>
        <span>Gains</span>
    </a>

    {{-- SYNCHRONISATION --}}
    <a href="{{ route('pwa.sync') }}" class="nav-item-pwa {{ request()->routeIs('pwa.sync') ? 'active' : '' }}" data-pending-sync-link>
        <div class="icon-wrapper">
            <i class="bi {{ request()->routeIs('pwa.sync') ? 'bi-cloud-upload-fill fw-bold' : 'bi-cloud-upload' }}"></i>
            <span class="sync-indicator" id="sync-dot"></span>
        </div>
        <span>Sync</span>
    </a>
</nav>