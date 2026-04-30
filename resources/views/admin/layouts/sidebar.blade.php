<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nana Eco Consulting Administration</title>
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

        body { background-color: #f7f9fc; margin: 0; overflow-x: hidden; }

        /* --- TOPBAR FIXE --- */
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
            font-size: 1.5rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            margin-right: 15px;
            transition: all 0.2s;
        }
        .hamburger-btn:hover { background: #e2e8f0; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding-top: var(--topbar-height);
            background: linear-gradient(180deg, var(--secondary-bg) 0%, var(--primary-bg) 100%);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Mode réduit (Style YouTube PC) */
        .sidebar.mini { width: var(--sidebar-mini-width); }
        .sidebar.mini span, 
        .sidebar.mini .menu-toggle::after,
        .sidebar.mini .sidebar-footer { 
            display: none !important; 
        }

        /* --- NAVIGATION --- */
        .sidebar-nav { flex: 1; padding: 15px 12px; }
        .sidebar a {
            color: #b8c1db;
            text-decoration: none;
            display: flex; align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 4px;
            white-space: nowrap;
            transition: 0.2s;
        }
        .sidebar a i { font-size: 1.3rem; min-width: 35px; }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .sidebar a.active { background: rgba(99,138,253,0.15); color: var(--accent-color); font-weight: bold; }

        /* --- SOUS-MENUS --- */
        .submenu { background: rgba(0,0,0,0.15); margin: 2px 5px 10px 10px; border-radius: 8px; }
        .submenu a { padding-left: 50px !important; font-size: 0.88rem; }
        .menu-toggle::after { content: "\F282"; font-family: bootstrap-icons; margin-left: auto; font-size: 0.7rem; transition: 0.3s; }
        .menu-toggle[aria-expanded="true"]::after { transform: rotate(180deg); }

        /* --- CONTENU --- */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .content.expanded { margin-left: var(--sidebar-mini-width); }

        /* --- MOBILE --- */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
            .sidebar.open { transform: translateX(0); }
            .content { margin-left: 0 !important; }
            .sidebar-overlay {
                display: none; position: fixed; inset: 0;
                background: rgba(0,0,0,0.5); z-index: 1045;
            }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<header class="admin-topbar d-flex align-items-center justify-content-between px-3 py-2 bg-white border-bottom shadow-sm">
    <div class="d-flex align-items-center">
        <button class="hamburger-btn border-0 bg-transparent me-2" id="hamburgerBtn">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="d-flex align-items-center border-start ps-3 ms-1">
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="Logo" class="me-2" style="height: 35px; width: auto;">
            
            <div class="d-none d-md-block">
                <div class="small text-muted lh-1" style="font-size: 10px;">Espace administration</div>
                <div class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.5px;">NANA ECO CONSULTING</div>
            </div>
        </div>
    </div>

    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="text-end me-2 d-none d-sm-block">
                <div class="fw-bold small text-dark lh-1">{{ Auth::user()->name }}</div>
                <div class="text-muted" style="font-size: 11px;">Administrateur</div>
            </div>
            <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 38px; height: 38px; border: 2px solid #fff;">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="dropdownUser">
            <li><h6 class="dropdown-header">Mon Compte</h6></li>
            <li><a class="dropdown-item d-flex align-items-center" href="#"><i class="bi bi-person me-2"></i> Profil</a></li>
            <li><a class="dropdown-item d-flex align-items-center" href="#"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center text-danger font-weight-bold">
                        <i class="bi bi-box-arrow-right me-2"></i> Déconnexion
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>
@php
    // On vérifie si la route actuelle correspond à un groupe de menus
    $usersMenuOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*');
    $agentsMenuOpen = request()->routeIs('admin.agents.*');
    $clientsMenuOpen = request()->routeIs('admin.clients.*');
    $carnetsMenuOpen = request()->routeIs('admin.carnets.*') || request()->routeIs('admin.categories.*');
    $collecteMenuOpen = request()->routeIs('admin.cycles.*') || request()->routeIs('admin.sync-batches.*');
@endphp
<div class="sidebar" id="sidebarNav">
    <div class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>

        @can('Gérer Utilisateurs')
        <a href="#usersSub" data-bs-toggle="collapse" aria-expanded="{{ $usersMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $usersMenuOpen ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> <span>Administrateurs</span>
        </a>
        <div class="collapse submenu {{ $usersMenuOpen ? 'show' : '' }}" id="usersSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Rôles</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Liste Admins</a>
        </div>
        @endcan

        @can('Gérer Agents')
        <a href="#agentsSub" data-bs-toggle="collapse" aria-expanded="{{ $agentsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $agentsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-person-gear"></i> <span>Agents</span>
        </a>
        <div class="collapse submenu {{ $agentsMenuOpen ? 'show' : '' }}" id="agentsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.agents.index') }}" class="{{ request()->routeIs('admin.agents.index') ? 'active' : '' }}">Liste Agents</a>
            <a href="{{ route('admin.agents.create') }}" class="{{ request()->routeIs('admin.agents.create') ? 'active' : '' }}">Ajouter Agent</a>
        </div>
        @endcan

        @can('Gérer Clients')
        <a href="#clientsSub" data-bs-toggle="collapse" aria-expanded="{{ $clientsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $clientsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-people"></i> <span>Clients</span>
        </a>
        <div class="collapse submenu {{ $clientsMenuOpen ? 'show' : '' }}" id="clientsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.clients.index') }}" class="{{ request()->routeIs('admin.clients.index') ? 'active' : '' }}">Liste Clients</a>
            <a href="{{ route('admin.clients.create') }}" class="{{ request()->routeIs('admin.clients.create') ? 'active' : '' }}">Ajouter Client</a>
        </div>
        @endcan

        @can('Gérer Collectes')
        <a href="#carnetsSub" data-bs-toggle="collapse" aria-expanded="{{ $carnetsMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $carnetsMenuOpen ? 'active' : '' }}">
            <i class="bi bi-book"></i> <span>Carnets</span>
        </a>
        <div class="collapse submenu {{ $carnetsMenuOpen ? 'show' : '' }}" id="carnetsSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.carnets.index') }}" class="{{ request()->routeIs('admin.carnets.index') ? 'active' : '' }}">Liste Carnets</a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">Catégories Tontine</a>
        </div>

        <a href="#collecteSub" data-bs-toggle="collapse" aria-expanded="{{ $collecteMenuOpen ? 'true' : 'false' }}" class="menu-toggle {{ $collecteMenuOpen ? 'active' : '' }}">
            <i class="bi bi-arrow-repeat"></i> <span>Gestion Collecte</span>
        </a>
        <div class="collapse submenu {{ $collecteMenuOpen ? 'show' : '' }}" id="collecteSub" data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.cycles.index') }}" class="{{ request()->routeIs('admin.cycles.*') ? 'active' : '' }}">Cycles</a>
            @can('Valider Synchros')
            <a href="{{ route('admin.sync-batches.index') }}" class="{{ request()->routeIs('admin.sync-batches.*') ? 'active' : '' }}">Synchros</a>
            @endcan
        </div>
        @endcan
    </div>

    <div class="sidebar-footer p-3">
        <div class="small text-muted"><span>Rôle :</span></div>
        <div class="text-white fw-bold"><span>{{ auth()->user()->role->nom ?? 'Admin' }}</span></div>
    </div>
</div>

<main class="content" id="mainContent">
    <div class="p-4">
        @yield('content')
         
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const btn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebarNav');
    const content = document.getElementById('mainContent');
    const overlay = document.getElementById('overlay');

    btn.addEventListener('click', () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.toggle('mini');
            content.classList.toggle('expanded');
        } else {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
</script>
</body>
</html>