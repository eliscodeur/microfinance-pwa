<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            background: linear-gradient(180deg, #2a2f3f 0%, #1f2432 100%);
            padding-top: 20px;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.18);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            overflow-y: auto;
            transition: transform 0.25s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .hamburger {
            display: flex;
            position: fixed;
            top: 15px;
            left: 15px;
            width: 42px;
            height: 34px;
            z-index: 1100;
            cursor: pointer;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            background: linear-gradient(180deg, rgba(53, 60, 83, 0.98) 0%, rgba(40, 46, 65, 0.98) 100%);
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: 0 6px 18px rgba(14, 18, 31, 0.28);
        }
        .content.expanded {
            margin-left: 260px !important;
        }
        .hamburger div {
            width: 18px;
            height: 2px;
            background: rgba(255, 255, 255, 0.92);
            margin: 2px 0;
            border-radius: 999px;
            transition: all 0.3s ease;
        }
        .hamburger.open div:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }
        .hamburger.open div:nth-child(2) {
            opacity: 0;
        }
        .hamburger.open div:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }
        .hamburger:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(14, 18, 31, 0.32);
        }
        .sidebar h4 {
            margin-bottom: 1rem;
            color: #e9ecf2;
            font-size: 1.1rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            padding: 0 20px;
        }
        .sidebar a {
            color: #d6ddea;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 0 10px 6px;
            transition: all 0.2s ease-in-out;
        }
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.12);
            color: #fff;
            transform: translateX(2px);
        }
        .sidebar a.active {
            background-color: rgba(99, 138, 253, 0.2);
            color: #cce0ff;
            border-left: 4px solid #5d83ff;
        }
        .sidebar-nav {
            flex: 1;
            padding-top: 42px;
            padding-bottom: 12px;
        }
        .sidebar .menu-toggle {
            position: relative;
            padding-right: 42px;
        }
        .sidebar .menu-toggle::after {
            content: "\F282";
            font-family: bootstrap-icons;
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
            transition: transform 0.2s ease;
            opacity: 0.75;
        }
        .sidebar .menu-toggle[aria-expanded="true"]::after {
            transform: translateY(-50%) rotate(180deg);
        }
        .content {
            margin-left: 0;
            padding: 0;
            background-color: #f7f9fc;
            min-height: 100vh;
            transition: margin-left 0.25s ease;
        }
        .content-body {
            padding: 24px;
        }
        .submenu {
            padding-left: 20px;
        }
        .submenu a {
            font-size: 0.9em;
            padding-left: 32px;
            color: #b8c1db;
        }
        .submenu a:hover {
            color: #fff;
        }
        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 990;
            background: rgba(247, 249, 252, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e8edf5;
        }
        .admin-profile-trigger {
            border: 1px solid #dbe3f0;
            background: #fff;
            border-radius: 999px;
            padding: 8px 12px;
        }
        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1f6feb, #0ea5e9);
            color: #fff;
            font-weight: 700;
        }
        .sidebar-footer {
            margin: 16px 14px 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #d6ddea;
        }
        .sidebar-footer .footer-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.65;
            margin-bottom: 6px;
        }
        .sidebar-footer .footer-role {
            font-weight: 700;
            color: #fff;
        }
    </style>
</head>
<body>
@php
    $usersMenuOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*') || request()->routeIs('admin.profile*');
    $agentsMenuOpen = request()->routeIs('admin.agents.*');
    $clientsMenuOpen = request()->routeIs('admin.clients.*');
    $collecteMenuOpen = request()->routeIs('admin.carnets.*') || request()->routeIs('admin.cycles.*') || request()->routeIs('admin.sync-batches.*');
@endphp

<div class="hamburger" id="hamburgerBtn" aria-label="Menu">
    <div></div>
    <div></div>
    <div></div>
</div>

<div class="sidebar" id="sidebarNav">
    <div class="sidebar-nav">
    <h4 class="text-white px-3"></h4>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

    @can('Gérer Utilisateurs')
    <a href="#usersSubmenu" data-bs-toggle="collapse" aria-expanded="{{ $usersMenuOpen ? 'true' : 'false' }}" class="dropdown-toggle menu-toggle {{ $usersMenuOpen ? 'active' : '' }}">Administrateurs</a>
    <div class="collapse submenu {{ $usersMenuOpen ? 'show' : '' }}" id="usersSubmenu" data-bs-parent="#sidebarNav">
        <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Liste des administrateurs</a>
        <!-- <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile*') ? 'active' : '' }}">Mon profil</a> -->
    </div>
    @endcan

    @can('Gérer Agents')
    <a href="#agentsSubmenu" data-bs-toggle="collapse" aria-expanded="{{ $agentsMenuOpen ? 'true' : 'false' }}" class="dropdown-toggle menu-toggle {{ $agentsMenuOpen ? 'active' : '' }}">Agents</a>
    <div class="collapse submenu {{ $agentsMenuOpen ? 'show' : '' }}" id="agentsSubmenu" data-bs-parent="#sidebarNav">
        <a href="{{ route('admin.agents.index') }}" class="{{ request()->routeIs('admin.agents.index') || request()->routeIs('admin.agents.show') || request()->routeIs('admin.agents.edit') ? 'active' : '' }}">Liste des agents</a>
        <a href="{{ route('admin.agents.create') }}" class="{{ request()->routeIs('admin.agents.create') ? 'active' : '' }}">Ajouter agent</a>
    </div>
    @endcan

    @can('Gérer Clients')
    <a href="#clientsSubmenu" data-bs-toggle="collapse" aria-expanded="{{ $clientsMenuOpen ? 'true' : 'false' }}" class="dropdown-toggle menu-toggle {{ $clientsMenuOpen ? 'active' : '' }}">Clients</a>
    <div class="collapse submenu {{ $clientsMenuOpen ? 'show' : '' }}" id="clientsSubmenu" data-bs-parent="#sidebarNav">
        <a href="{{ route('admin.clients.index') }}" class="{{ request()->routeIs('admin.clients.index') || request()->routeIs('admin.clients.show') || request()->routeIs('admin.clients.edit') ? 'active' : '' }}">Listes des clients</a>
        <a href="{{ route('admin.clients.create') }}" class="{{ request()->routeIs('admin.clients.create') ? 'active' : '' }}">Ajouter client</a>
    </div>
    @endcan

    @can('Gérer Collectes')
    <a href="#collecteSubmenu" data-bs-toggle="collapse" aria-expanded="{{ $collecteMenuOpen ? 'true' : 'false' }}" class="dropdown-toggle menu-toggle {{ $collecteMenuOpen ? 'active' : '' }}">Gestion collecte</a>
    <div class="collapse submenu {{ $collecteMenuOpen ? 'show' : '' }}" id="collecteSubmenu" data-bs-parent="#sidebarNav">
        <a href="{{ route('admin.carnets.index') }}" class="{{ request()->routeIs('admin.carnets.*') ? 'active' : '' }}">Carnets</a>
        <a href="{{ route('admin.cycles.index') }}" class="{{ request()->routeIs('admin.cycles.*') ? 'active' : '' }}">Cycles</a>
        @can('Valider Synchros')
        <a href="{{ route('admin.sync-batches.index') }}" class="{{ request()->routeIs('admin.sync-batches.*') ? 'active' : '' }}">Validation synchros</a>
        @endcan
    </div>
    @endcan
    </div>

    <div class="sidebar-footer">
        <div class="footer-label">Connecte en tant que</div>
        <div class="footer-role">{{ auth()->user()->role->nom ?? 'Administrateur' }}</div>
        
    </div>
</div>

<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

<div class="content" id="mainContent">
    <div class="admin-topbar px-4 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="small text-muted">Espace administration</div>
                <div class="fw-bold text-dark">{{ config('app.name', 'Nana Consulting') }}</div>
            </div>
            <div class="dropdown">
                <button class="admin-profile-trigger d-flex align-items-center gap-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="admin-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                    <span class="text-start">
                        <span class="d-block fw-bold text-dark small">{{ auth()->user()->name }}</span>
                        <span class="d-block text-muted small">{{ auth()->user()->email ?? 'Administrateur' }}</span>
                    </span>
                    <i class="bi bi-chevron-down text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.profile') }}">
                            <i class="bi bi-person-circle me-2"></i> Mon profil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Deconnexion
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="content-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hamburger = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebarNav');
        const content = document.getElementById('mainContent');

        hamburger.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            hamburger.classList.toggle('open');
        });

        function mediaCheck() {
            if (window.innerWidth <= 992) {
                sidebar.classList.add('collapsed');
                content.classList.remove('expanded');
            } else {
                sidebar.classList.remove('collapsed');
                content.classList.add('expanded');
            }
        }

        window.addEventListener('resize', mediaCheck);
        mediaCheck();

        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alertElement) {
            setTimeout(function () {
                const bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            }, 4000);
        });
    });
</script>
</body>
</html>
