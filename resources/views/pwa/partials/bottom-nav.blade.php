<style>
    :root {
        --nana-blue: #134E5E;
        --nana-green: #78B13F;
        --nana-red: #ff3b30;
    }

    .pwa-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 70px;
        background: #ffffff;
        display: flex;
        justify-content: space-around;
        align-items: center;
        border-top: 1.5px solid rgba(19, 78, 94, 0.1);
        box-shadow: 0 -4px 15px rgba(0,0,0,0.08);
        z-index: 2000;
        /* Gestion des iPhones récents (espace sous la barre home) */
        padding-bottom: env(safe-area-inset-bottom); 
    }

    .nav-item-pwa {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #8e8e93; /* Gris inactif */
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        height: 100%;
    }

    .nav-item-pwa i {
        font-size: 1.5rem;
        margin-bottom: 2px;
        transition: transform 0.2s ease;
    }

    .nav-item-pwa span {
        font-size: 11px;
        font-weight: 600;
    }

    /* --- ÉTAT ACTIF (BLEU NANA) --- */
    .nav-item-pwa.active {
        color: var(--nana-blue);
    }

    .nav-item-pwa.active i {
        transform: translateY(-4px); /* Effet de levée pour le pouce */
    }

    /* --- BADGE DE SYNCHRO (Point Rouge) --- */
    .icon-wrapper {
        position: relative;
        display: inline-block;
    }

    .sync-indicator {
        position: absolute;
        top: -2px;
        right: -6px;
        width: 12px;
        height: 12px;
        background-color: var(--nana-red);
        border: 2px solid #ffffff;
        border-radius: 50%;
        display: none; /* Caché par défaut */
    }

    .sync-indicator.active {
        display: block;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 59, 48, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(255, 59, 48, 0); }
        100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 59, 48, 0); }
    }

    /* Feedback visuel au toucher */
    .nav-item-pwa:active {
        background-color: rgba(19, 78, 94, 0.05);
    }
</style>

<nav class="pwa-bottom-nav">
    {{-- ACCUEIL --}}
    <a href="{{ route('pwa.index') }}" class="nav-item-pwa {{ request()->routeIs('pwa.index') ? 'active' : '' }}" data-pending-sync-link>
        <i class="bi {{ request()->routeIs('pwa.index') ? 'bi-house-door-fill' : 'bi-house-door' }}"></i>
        <span>Accueil</span>
    </a>

    {{-- CLIENTS --}}
    <a href="{{ route('pwa.clients') }}" class="nav-item-pwa {{ request()->routeIs('pwa.clients') ? 'active' : '' }}" data-pending-sync-link>
        <i class="bi {{ request()->routeIs('pwa.clients') ? 'bi-people-fill' : 'bi-people' }}"></i>
        <span>Clients</span>
    </a>

    {{-- SYNCHRONISATION --}}
    <a href="{{ route('pwa.sync') }}" class="nav-item-pwa {{ request()->routeIs('pwa.sync') ? 'active' : '' }}" data-pending-sync-link>
        <div class="icon-wrapper">
            <i class="bi {{ request()->routeIs('pwa.sync') ? 'bi-arrow-repeat fw-bold' : 'bi-arrow-repeat' }}"></i>
            <span class="sync-indicator" id="sync-dot"></span>
        </div>
        <span>Sync</span>
    </a>
</nav>

<script>
    async function tenterAccesSync() {
    // CONDITION 1 : Est-ce qu'il a internet ?
    if (!navigator.onLine) {
        alert("Action impossible : Vous devez être connecté à Internet pour accéder à la synchronisation.");
        return; 
    }

    // CONDITION 2 : Est-ce que l'admin l'autorise ?
    try {
        const response = await fetch("{{ route('pwa.check-sync-permission') }}");
        const data = await response.json();

        if (data.can_sync) {
            // SI TOUT EST OK -> On entre dans la vue
            window.location.href = "{{ route('pwa.sync') }}";
        } else {
            // SI L'ADMIN A BLOQUÉ
            alert("Synchronisation non autorisée. Veuillez contacter votre administrateur.");
        }
    } catch (error) {
        console.error("Erreur lors de la vérification des droits", error);
        alert("Erreur de communication avec le serveur.");
    }
}
</script>
