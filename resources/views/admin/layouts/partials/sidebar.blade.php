@php
    // Logique d'ouverture des menus
$usersMenuOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*');
$bonusMenuOpen = request()->routeIs('admin.bonuses.*');
$agentsMenuOpen = request()->routeIs('admin.agents.*');
$personnelMenuOpen = request()->routeIs('admin.employes.*') || request()->routeIs('admin.fonctions.*'); // <-- Ajout pour le personnel & fonctions
$clientsMenuOpen = request()->routeIs('admin.clients.*');
$creditsMenuOpen = request()->routeIs('admin.credits.*') || request()->routeIs('admin.prets.*');
$carnetsMenuOpen = request()->routeIs('admin.carnets.*') || request()->routeIs('admin.categories.*');
$collecteMenuOpen = request()->routeIs('admin.sync-batches.*') || request()->routeIs('admin.cycles.*');
$payrollMenuOpen = request()->routeIs('admin.payrolls.*');
$chargesMenuOpen = request()->routeIs('admin.charges.*') || request()->routeIs('admin.depenses.*');
@endphp

<style>
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding-top: var(--topbar-height);
        background: linear-gradient(180deg, var(--secondary-bg) 0%, var(--primary-bg) 100%);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1050;
        display: flex;
        flex-direction: column;
    }

    .sidebar.mini {
        width: var(--sidebar-mini-width);
    }

    .sidebar.mini span,
    .sidebar.mini .menu-toggle::after,
    .sidebar.mini .sidebar-footer,
    .sidebar.mini .nav-section-title {
        display: none !important;
    }

    .sidebar-nav {
        flex: 1;
        padding: 15px 12px;
        overflow-y: auto;
    }

    .sidebar a {
        color: #b8c1db;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: 0.2s;
        white-space: nowrap;
    }

    .sidebar a i {
        font-size: 1.2rem;
        min-width: 35px;
    }

    .sidebar a:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .sidebar a.active {
        background: rgba(99, 138, 253, 0.15);
        color: var(--accent-color);
        font-weight: 600;
    }

    .submenu {
        background: rgba(0, 0, 0, 0.15);
        margin: 2px 5px 10px 10px;
        border-radius: 8px;
    }

    .submenu a {
        padding-left: 50px !important;
        font-size: 0.85rem;
    }

    .menu-toggle::after {
        content: "\F282";
        font-family: bootstrap-icons;
        margin-left: auto;
        font-size: 0.7rem;
        transition: 0.3s;
    }

    .menu-toggle[aria-expanded="true"]::after {
        transform: rotate(180deg);
    }

    .nav-section-title {
        color: #566181;
        font-size: 0.65rem;
        text-transform: uppercase;
        font-weight: 700;
        padding: 15px 15px 5px;
        letter-spacing: 1px;
    }

    @media (max-width: 991px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-width) !important;
        }

        .sidebar.open {
            transform: translateX(0);
        }
    }
</style>

<div class="sidebar" id="sidebarNav">
    <div class="sidebar-nav">

        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i> <span>Tableau de Bord</span>
        </a>

        <!-- ADMINISTRATION -->
        @can('Gérer Utilisateurs')
            <a href="#usersSub" data-bs-toggle="collapse" aria-expanded="{{ $usersMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $usersMenuOpen ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> <span>Administrateurs</span>
            </a>
            <div class="collapse {{ $usersMenuOpen ? 'show' : '' }} submenu" id="usersSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.roles.index') }}"
                    class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Rôles & Droits</a>
                <a href="{{ route('admin.users.index') }}"
                    class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Liste</a>
            </div>
        @endcan
        <!-- PERSONNEL ADMINISTRATIF -->
        <a href="#personnelSub" data-bs-toggle="collapse" aria-expanded="{{ $personnelMenuOpen ? 'true' : 'false' }}"
            class="menu-toggle {{ $personnelMenuOpen ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> <span>Personnel & Fonctions</span>
        </a>
        <div class="collapse {{ $personnelMenuOpen ? 'show' : '' }} submenu" id="personnelSub"
            data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.employes.index') }}"
                class="{{ request()->routeIs('admin.employes.*') ? 'active' : '' }}">Liste du Personnel</a>
            <a href="{{ route('admin.fonctions.index') }}"
                class="{{ request()->routeIs('admin.fonctions.*') ? 'active' : '' }}">Postes & Fonctions</a>
        </div>

        <!-- TERRAIN -->
        @can('Gérer Agents')
            <a href="#agentsSub" data-bs-toggle="collapse" aria-expanded="{{ $agentsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $agentsMenuOpen ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> <span>Agents</span>
            </a>
            <div class="collapse {{ $agentsMenuOpen ? 'show' : '' }} submenu" id="agentsSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.agents.index') }}"
                    class="{{ request()->routeIs('admin.agents.index') ? 'active' : '' }}">Liste Agents</a>
                <a href="{{ route('admin.agents.create') }}"
                    class="{{ request()->routeIs('admin.agents.create') ? 'active' : '' }}">Ajouter Agent</a>
            </div>
        @endcan

        <!-- RÉMUNÉRATION -->
        @can('Gérer Commissions')
            <a href="#commissionsSub" data-bs-toggle="collapse" aria-expanded="{{ $bonusMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $bonusMenuOpen ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> <span>Commissions & bonus</span>
            </a>
            <div class="collapse {{ $bonusMenuOpen ? 'show' : '' }} submenu" id="commissionsSub"
                data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.bonuses.index') }}"
                    class="{{ request()->routeIs('admin.bonuses.index') ? 'active' : '' }}">À valider</a>
                <a href="{{ route('admin.bonuses.history') }}"
                    class="{{ request()->routeIs('admin.bonuses.history') ? 'active' : '' }}">Historique</a>
            </div>
        @endcan

        <!-- GESTION DES SALAIRES -->
        @can('Gérer Salaires')
            <a href="#payrollSub" data-bs-toggle="collapse" aria-expanded="{{ $payrollMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $payrollMenuOpen ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> <span>Salaires & Avances</span>
            </a>
            <div class="collapse {{ $payrollMenuOpen ? 'show' : '' }} submenu" id="payrollSub"
                data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.payrolls.index') }}"
                    class="{{ request()->routeIs('admin.payrolls.index*') ? 'active' : '' }}">Salaires agents</a>

                {{-- Ajout du lien pour les salaires employés administratifs --}}
                <a href="{{ route('admin.payrolls.employes.index') }}"
                    class="{{ request()->routeIs('admin.payrolls.employes.*') ? 'active' : '' }}">Salaires employés</a>

                <a href="{{ route('admin.payrolls.avance') }}"
                    class="{{ request()->routeIs('admin.payrolls.avance*') ? 'active' : '' }}">Avances sur salaire</a>
            </div>
        @endcan

        <!-- GESTION DES CHARGES & DÉPENSES -->
        <a href="#chargesSub" data-bs-toggle="collapse" aria-expanded="{{ $chargesMenuOpen ? 'true' : 'false' }}"
            class="menu-toggle {{ $chargesMenuOpen ? 'active' : '' }}">
            <i class="bi bi-receipt-cutoff"></i> <span>Charges & Dépenses</span>
        </a>
        <div class="collapse {{ $chargesMenuOpen ? 'show' : '' }} submenu" id="chargesSub"
            data-bs-parent="#sidebarNav">
            <a href="{{ route('admin.charges.index') }}"
                class="{{ request()->routeIs('admin.charges.index') ? 'active' : '' }}">Types & Catégories</a>
            <a href="{{ route('admin.depenses.index') }}"
                class="{{ request()->routeIs('admin.depenses.index') ? 'active' : '' }}">Gestion des Dépenses</a>
        </div>

        @can('Gérer Crédits')
            <a href="#creditsSub" data-bs-toggle="collapse" aria-expanded="{{ $creditsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $creditsMenuOpen ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> <span>Crédits</span>
            </a>
            <div class="collapse {{ $creditsMenuOpen ? 'show' : '' }} submenu" id="creditsSub"
                data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.credits.index') }}"
                    class="{{ request()->routeIs('admin.credits.index') ? 'active' : '' }}">Suivi Crédits</a>
                <a href="{{ route('admin.prets.index') }}"
                    class="{{ request()->routeIs('admin.prets.*') ? 'active' : '' }}">Instruction Crédit</a>
                <a href="{{ route('admin.credits.create') }}"
                    class="{{ request()->routeIs('admin.credits.create') ? 'active' : '' }}">Nouvelle Demande</a>
            </div>
        @endcan

        <!-- PORTEFEUILLE -->
        @can('Gérer Clients')
            <a href="#clientsSub" data-bs-toggle="collapse" aria-expanded="{{ $clientsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $clientsMenuOpen ? 'active' : '' }}">
                <i class="bi bi-people"></i> <span>Clients</span>
            </a>
            <div class="collapse {{ $clientsMenuOpen ? 'show' : '' }} submenu" id="clientsSub"
                data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.clients.index') }}"
                    class="{{ request()->routeIs('admin.clients.index') ? 'active' : '' }}">Liste Clients</a>
                <a href="{{ route('admin.clients.create') }}"
                    class="{{ request()->routeIs('admin.clients.create') ? 'active' : '' }}">Inscrire Client</a>
            </div>
        @endcan

        @can('Gérer Carnets')
            <a href="#carnetsSub" data-bs-toggle="collapse" aria-expanded="{{ $carnetsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $carnetsMenuOpen ? 'active' : '' }}">
                <i class="bi bi-book"></i> <span>Carnets</span>
            </a>
            <div class="collapse {{ $carnetsMenuOpen ? 'show' : '' }} submenu" id="carnetsSub"
                data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.carnets.index') }}"
                    class="{{ request()->routeIs('admin.carnets.index') ? 'active' : '' }}">Gestion Carnets</a>
                <a href="{{ route('admin.categories.index') }}"
                    class="{{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">Catégories de tontine</a>
                <a href="{{ route('admin.stocks.index') }}"
                    class="{{ request()->routeIs('admin.stocks.index') ? 'active' : '' }}">Stock de carnets</a>
            </div>
        @endcan

        @can('Valider Synchros')
            <a href="#collecteSub" data-bs-toggle="collapse" aria-expanded="{{ $collecteMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $collecteMenuOpen ? 'active' : '' }}">
                <i class="bi bi-arrow-repeat"></i> <span>Synchronisation</span>
            </a>
            <div class="collapse {{ $collecteMenuOpen ? 'show' : '' }} submenu" id="collecteSub"
                data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.sync-batches.index') }}"
                    class="{{ request()->routeIs('admin.sync-batches.*') ? 'active' : '' }}">Lots de synchro</a>
            </div>
        @endcan
    </div>

    <div class="sidebar-footer p-3 border-top border-secondary border-opacity-10 text-white-50 small">
        Connecté en tant que :<br>
        <span class="text-white fw-bold">{{ auth()->user()->role->nom ?? 'Admin' }}</span>
    </div>
</div>
