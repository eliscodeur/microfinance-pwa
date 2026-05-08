<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nana Eco Consulting - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-mini-width: 75px;
            --topbar-height: 70px;
            --primary-bg: #1f2432;
            --secondary-bg: #2a2f3f;
            --accent-color: #638afd;
        }

        body { background-color: #f7f9fc; margin: 0; overflow-x: hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* TOPBAR */
        .admin-topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #eef2f7;
            z-index: 1060;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }

        .hamburger-btn {
            background: #f1f3f9;
            border: none;
            width: 45px; height: 45px;
            border-radius: 10px;
            color: var(--primary-bg);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding-top: var(--topbar-height);
            background: linear-gradient(180deg, var(--secondary-bg) 0%, var(--primary-bg) 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            display: flex;
            flex-direction: column;
        }

        .sidebar.mini { width: var(--sidebar-mini-width); }
        
        .sidebar.mini span, 
        .sidebar.mini .menu-toggle::after, 
        .sidebar.mini .sidebar-footer,
        .sidebar.mini .nav-section-title { 
            display: none !important; 
        }

        .sidebar-nav { flex: 1; padding: 15px 12px; overflow-y: auto; }
        
        .sidebar a {
            color: #b8c1db;
            text-decoration: none;
            display: flex; align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: 0.2s;
            white-space: nowrap;
        }

        .sidebar a i { font-size: 1.2rem; min-width: 35px; }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .sidebar a.active { background: rgba(99,138,253,0.15); color: var(--accent-color); font-weight: 600; }

        .submenu { background: rgba(0,0,0,0.15); margin: 2px 5px 10px 10px; border-radius: 8px; }
        .submenu a { padding-left: 50px !important; font-size: 0.85rem; }

        .menu-toggle::after { 
            content: "\F282"; 
            font-family: bootstrap-icons; 
            margin-left: auto; 
            font-size: 0.7rem; 
            transition: 0.3s; 
        }
        .menu-toggle[aria-expanded="true"]::after { transform: rotate(180deg); }

        .nav-section-title {
            color: #566181;
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 15px 15px 5px;
            letter-spacing: 1px;
        }

        /* CONTENT */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        .content.expanded { margin-left: var(--sidebar-mini-width); }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
            .sidebar.open { transform: translateX(0); }
            .content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>

<header class="admin-topbar justify-content-between shadow-sm">
    <div class="d-flex align-items-center">
        <button class="hamburger-btn me-3" id="hamburgerBtn">
            <i class="bi bi-list fs-4"></i>
        </button>
        <img src="{{ asset('icons/icon-192x192.png') }}" alt="Logo" style="height: 35px;" class="me-2">
        <span class="fw-bold d-none d-sm-inline">NANA ECO CONSULTING</span>
    </div>
</header>

@php
    // Logique d'ouverture des menus
    $usersMenuOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*');
    
    // Séparation des variables pour les deux nouveaux blocs
    $bonusMenuOpen = request()->routeIs('admin.bonuses.*');
    $agentsMenuOpen = request()->routeIs('admin.agents.*');
    
    $clientsMenuOpen = request()->routeIs('admin.clients.*');
    $creditsMenuOpen = request()->routeIs('admin.credits.*');
    $carnetsMenuOpen = request()->routeIs('admin.carnets.*') || request()->routeIs('admin.categories.*');
    $collecteMenuOpen = request()->routeIs('admin.sync-batches.*') || request()->routeIs('admin.cycles.*');
@endphp

<div class="sidebar" id="sidebarNav">
    <div class="sidebar-nav">
        
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>

        <!-- ADMINISTRATION -->
        @can('Gérer Utilisateurs')
        <div class="nav-section-title">Sécurité</div>
        <a href="#usersSub" data-bs-toggle="collapse" aria-expanded="{{ $usersMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $usersMenuOpen ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> <span>Admins</span>
        </a>
        <div class="collapse {{ $usersMenuOpen ? 'show' : '' }} submenu" id="usersSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Rôles & Droits</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Liste Admins</a>
        </div>
        @endcan

        <!-- BLOC 1 : GESTION DES AGENTS (Terrain) -->
        @can('Gérer Agents')
        <div class="nav-section-title">Terrain</div>
        <a href="#agentsSub" data-bs-toggle="collapse" aria-expanded="{{ $agentsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $agentsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-person-gear"></i> <span>Agents</span>
        </a>
        <div class="collapse {{ $agentsMenuOpen ? 'show' : '' }} submenu" id="agentsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.agents.index') }}" class="{{ request()->routeIs('admin.agents.index') ? 'active' : '' }}">Liste Agents</a>
            <a href="{{ route('admin.agents.create') }}" class="{{ request()->routeIs('admin.agents.create') ? 'active' : '' }}">Ajouter Agent</a>
        </div>
        @endcan
        <!-- BLOC 2 : GESTION DES COMMISSIONS (Finances) -->
        @can('Gérer Commissions')
        <div class="nav-section-title">Rénumération</div>
        <a href="#commissionsSub" data-bs-toggle="collapse" aria-expanded="{{ $bonusMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $bonusMenuOpen ? 'active' : '' }}">
            <i class="bi bi-cash-coin"></i> <span>Commissions</span>
        </a>
        <div class="collapse {{ $bonusMenuOpen ? 'show' : '' }} submenu" id="commissionsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.bonuses.index') }}" class="{{ request()->routeIs('admin.bonuses.index') ? 'active' : '' }}">
                <i class="bi bi-hourglass-split me-1 small"></i> À valider
            </a>
            <a href="{{ route('admin.bonuses.history') }}" class="{{ request()->routeIs('admin.bonuses.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history me-1 small"></i> Historique
            </a>
        </div>
        @endcan

        <!-- CLIENTS -->
        @can('Gérer Clients')
        <div class="nav-section-title">Portefeuille</div>
        <a href="#clientsSub" data-bs-toggle="collapse" aria-expanded="{{ $clientsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $clientsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-people"></i> <span>Clients</span>
        </a>
        <div class="collapse {{ $clientsMenuOpen ? 'show' : '' }} submenu" id="clientsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.clients.index') }}" class="{{ request()->routeIs('admin.clients.index') ? 'active' : '' }}">Liste Clients</a>
            <a href="{{ route('admin.clients.create') }}" class="{{ request()->routeIs('admin.clients.create') ? 'active' : '' }}">Inscrire Client</a>
        </div>
        @endcan

        <!-- CREDITS -->
        @can('Gérer Crédits')
        <a href="#creditsSub" data-bs-toggle="collapse" aria-expanded="{{ $creditsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $creditsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> <span>Crédits</span>
        </a>
        <div class="collapse {{ $creditsMenuOpen ? 'show' : '' }} submenu" id="creditsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.credits.index') }}" class="{{ request()->routeIs('admin.credits.index') ? 'active' : '' }}">Suivi Crédits</a>
            <a href="{{ route('admin.credits.create') }}" class="{{ request()->routeIs('admin.credits.create') ? 'active' : '' }}">Nouvelle Demande</a>
        </div>
        @endcan

        <!-- COLLECTES & PARAMÈTRES -->
        @can('Gérer Collectes')
        <div class="nav-section-title">Exploitation</div>
        <a href="#carnetsSub" data-bs-toggle="collapse" aria-expanded="{{ $carnetsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $carnetsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-book"></i> <span>Carnets</span>
        </a>
        <div class="collapse {{ $carnetsMenuOpen ? 'show' : '' }} submenu" id="carnetsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.carnets.index') }}" class="{{ request()->routeIs('admin.carnets.index') ? 'active' : '' }}">Gestion Carnets</a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">Catégories</a>
        </div>

        <a href="#collecteSub" data-bs-toggle="collapse" aria-expanded="{{ $collecteMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $collecteMenuOpen ? 'active' : '' }}">
            <i class="bi bi-arrow-repeat"></i> <span>Synchronisation</span>
        </a>
        <div class="collapse {{ $collecteMenuOpen ? 'show' : '' }} submenu" id="collecteSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.sync-batches.index') }}" class="{{ request()->routeIs('admin.sync-batches.*') ? 'active' : '' }}">Lots de synchro</a>
            <a href="{{ route('admin.cycles.index') }}" class="{{ request()->routeIs('admin.cycles.index') ? 'active' : '' }}">Cycles de collecte</a>
        </div>
        @endcan

    </div>

    <div class="sidebar-footer p-3 border-top border-secondary border-opacity-10 text-white-50 small">
        Connecté en tant que :<br>
        <span class="text-white fw-bold">{{ auth()->user()->role->nom ?? 'Admin' }}</span>
    </div>
</div>

<main class="content" id="mainContent">
    <div class="container-fluid p-4">
        {{-- On ajoute l'ID 'app' ici pour qu'Inertia et Echo s'activent --}}
        <div id="app">
            @yield('content')
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const btn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebarNav');
    const content = document.getElementById('mainContent');

    if (btn) {
        btn.addEventListener('click', () => {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('mini');
                content.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('open');
            }
        });
    }

    // Fermeture automatique du menu sur mobile lors du clic à l'extérieur
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && !sidebar.contains(e.target) && !btn.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });
</script>

</body>
</html>