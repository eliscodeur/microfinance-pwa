@php
    $usersMenuOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*');
    $bonusMenuOpen = request()->routeIs('admin.bonuses.*');
    $agentsMenuOpen = request()->routeIs('admin.agents.*');
    $performanceMenuOpen = request()->routeIs('admin.performance-terrain.*');
    $personnelMenuOpen = request()->routeIs('admin.employes.*') || request()->routeIs('admin.fonctions.*') || request()->routeIs('admin.salary-grids.*');
    $clientsMenuOpen = request()->routeIs('admin.clients.*');
    $creditsMenuOpen = request()->routeIs('admin.credits.*') || request()->routeIs('admin.prets.*') || request()->routeIs('admin.credit-settings.*');
    $carnetsMenuOpen = request()->routeIs('admin.carnets.*') || request()->routeIs('admin.categories.*');
    $caisseMenuOpen = request()->routeIs('admin.caisse.*');
    $cyclesMenuOpen = request()->routeIs('admin.cycles.*');
    $payrollMenuOpen = request()->routeIs('admin.payrolls.*');
    $chargesMenuOpen =
        request()->routeIs('admin.charges.*') ||
        request()->routeIs('admin.depenses.*') ||
        request()->routeIs('admin.recettes.*');
@endphp

<style>
    .sidebar {
        box-sizing: border-box;
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding-top: var(--topbar-height);
        background: linear-gradient(180deg, #203735 0%, var(--primary-bg) 100%);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1050;
        display: flex;
        flex-direction: column;
        box-shadow: 8px 0 30px rgba(19, 39, 38, 0.08);
    }

    .sidebar a.sidebar-brand {
        min-height: 78px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        margin: 0;
        color: #fff;
        text-decoration: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-brand:hover {
        color: #fff;
    }

    .sidebar-brand-mark {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        display: grid;
        place-items: center;
        overflow: hidden;
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
    }

    .sidebar-brand-mark img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sidebar-brand-copy {
        min-width: 0;
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .sidebar-brand-copy strong {
        font-size: 0.86rem;
        letter-spacing: 0.055em;
    }

    .sidebar-brand-copy small {
        margin-top: 4px;
        color: #b7c8be;
        font-size: 0.7rem;
    }

    .sidebar.mini {
        width: var(--sidebar-mini-width);
    }

    .sidebar.mini .sidebar-nav > a > span,
    .sidebar.mini .menu-toggle::after,
    .sidebar.mini .sidebar-footer,
    .sidebar.mini .sidebar-header,
    .sidebar.mini .sidebar-brand-copy,
    .sidebar.mini .submenu {
        display: none !important;
    }

    .sidebar.mini .sidebar-brand {
        justify-content: center;
        padding-right: 8px;
        padding-left: 8px;
    }

    .sidebar.mini .sidebar-brand-mark {
        width: 42px;
        height: 42px;
        flex-basis: 42px;
    }

    .sidebar.mini .sidebar-nav {
        padding-right: 8px;
        padding-left: 8px;
    }

    .sidebar.mini .sidebar-nav > a {
        justify-content: center;
        padding-right: 4px;
        padding-left: 4px;
    }

    .sidebar-nav {
        flex: 1;
        padding: 12px 12px 16px;
        overflow-y: auto;
    }

    .sidebar-nav::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.16);
    }

    .sidebar a {
        color: #c1d0cb;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 10px 12px;
        border-radius: 9px;
        margin-bottom: 3px;
        transition: color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        white-space: nowrap;
    }

    .sidebar a i {
        width: 32px;
        flex: 0 0 32px;
        font-size: 1.08rem;
        text-align: center;
    }

    .sidebar a:hover {
        background: rgba(255, 255, 255, 0.075);
        color: #fff;
    }

    .sidebar a.active {
        background: rgba(154, 199, 107, 0.14);
        color: var(--accent-color);
        font-weight: 600;
        box-shadow: inset 3px 0 0 var(--accent-color);
    }

    .submenu {
        background: rgba(9, 23, 23, 0.24);
        margin: 3px 4px 9px 8px;
        padding: 4px 0;
        border-radius: 9px;
    }

    .submenu a {
        padding: 8px 10px 8px 43px !important;
        margin: 1px 5px;
        font-size: 0.82rem;
    }

    .menu-toggle::after {
        content: "\F282";
        font-family: bootstrap-icons;
        margin-left: auto;
        font-size: 0.7rem;
        transition: 0.3s;
        color: #8fa79a;
    }

    .menu-toggle[aria-expanded="true"]::after {
        transform: rotate(180deg);
    }

    .nav-section-title {
        color: #9db5a8;
        font-size: 0.64rem;
        text-transform: uppercase;
        font-weight: 700;
        padding: 18px 12px 7px;
        letter-spacing: 0.09em;
    }

    .sidebar .sidebar-dashboard {
        margin-top: 10px;
        margin-bottom: 8px;
    }

    .sidebar .sidebar-footer {
        color: #a9beb4 !important;
        background: rgba(9, 23, 23, 0.18);
    }

    @media (max-width: 991px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-width) !important;
            box-shadow: 12px 0 35px rgba(10, 25, 24, 0.22);
        }

        .sidebar.open {
            transform: translateX(0);
        }
    }
</style>

<div class="sidebar" id="sidebarNav">
    <a class="sidebar-brand" href="{{ route('admin.dashboard') }}" aria-label="Nana Eco Consulting, tableau de bord">
        <span class="sidebar-brand-mark">
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="">
        </span>
        <span class="sidebar-brand-copy">
            <strong>NANA ECO</strong>
            <small>Microfinance & gestion</small>
        </span>
    </a>

    <div class="sidebar-nav">

        <a href="{{ route('admin.dashboard') }}"
            class="sidebar-dashboard {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
            title="Tableau de bord">
            <i class="bi bi-grid-1x2"></i> <span>Tableau de Bord</span>
        </a>

        @if(auth()->user()->can('clients.view') || auth()->user()->can('agents.view') || auth()->user()->can('carnets.view') || auth()->user()->can('cycles.view') || auth()->user()->can('sync.view') || auth()->user()->can('credits.view'))
            <div class="sidebar-header nav-section-title">Opérations terrain</div>
        @endif

        @can('clients.view')
            <a href="#clientsSub" data-bs-toggle="collapse" aria-expanded="{{ $clientsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $clientsMenuOpen ? 'active' : '' }}" title="Clients">
                <i class="bi bi-people"></i> <span>Clients</span>
            </a>
            <div class="collapse {{ $clientsMenuOpen ? 'show' : '' }} submenu" id="clientsSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.clients.index') }}"
                    class="{{ request()->routeIs('admin.clients.index') ? 'active' : '' }}">Liste Clients</a>
                @can('clients.create')
                    <a href="{{ route('admin.clients.create') }}"
                        class="{{ request()->routeIs('admin.clients.create') ? 'active' : '' }}">Inscrire Client</a>
                @endcan
            </div>
        @endcan

        @can('agents.view')
            <a href="#agentsSub" data-bs-toggle="collapse" aria-expanded="{{ $agentsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $agentsMenuOpen ? 'active' : '' }}" title="Agents Terrain">
                <i class="bi bi-person-gear"></i> <span>Agents Terrain</span>
            </a>
            <div class="collapse {{ $agentsMenuOpen ? 'show' : '' }} submenu" id="agentsSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.agents.index') }}"
                    class="{{ request()->routeIs('admin.agents.index') ? 'active' : '' }}">Liste Agents</a>
                @can('agents.create')
                    <a href="{{ route('admin.agents.create') }}"
                        class="{{ request()->routeIs('admin.agents.create') ? 'active' : '' }}">Ajouter Agent</a>
                @endcan
            </div>
        @endcan
        @can('performance.view')
            <a href="{{ route('admin.performance-terrain.index') }}"
                class="{{ $performanceMenuOpen ? 'active' : '' }}" title="Performance terrain">
                <i class="bi bi-graph-up-arrow"></i> <span>Performance terrain</span>
            </a>
        @endcan

        @can('carnets.view')
            <a href="#carnetsSub" data-bs-toggle="collapse" aria-expanded="{{ $carnetsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $carnetsMenuOpen ? 'active' : '' }}" title="Carnets & Tontines">
                <i class="bi bi-book"></i> <span>Carnets & Tontines</span>
            </a>
            <div class="collapse {{ $carnetsMenuOpen ? 'show' : '' }} submenu" id="carnetsSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.carnets.index') }}"
                    class="{{ request()->routeIs('admin.carnets.index') ? 'active' : '' }}">Gestion Carnets</a>
                @can('carnets.update')
                    <a href="{{ route('admin.categories.index') }}"
                        class="{{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">Catégories de tontine</a>
                @endcan
            </div>
            @can('carnets.stock')
                <a href="{{ route('admin.stocks.index') }}"
                    class="{{ request()->routeIs('admin.stocks.*') ? 'active' : '' }}" title="Stock de carnets">
                    <i class="bi bi-box-seam"></i> <span>Stock de carnets</span>
                </a>
            @endcan
        @endcan

        @can('cycles.view')
            <a href="{{ route('admin.cycles.index') }}"
                class="{{ $cyclesMenuOpen ? 'active' : '' }}" title="Cycles de tontine">
                <i class="bi bi-arrow-left-right"></i> <span>Cycles de tontine</span>
            </a>
        @endcan

        @can('sync.view')
            <a href="{{ route('admin.sync-batches.index') }}"
                class="{{ request()->routeIs('admin.sync-batches.*') ? 'active' : '' }}" title="Synchronisation">
                <i class="bi bi-arrow-repeat"></i> <span>Synchronisation</span>
            </a>
        @endcan

        @can('credits.view')
            <a href="#creditsSub" data-bs-toggle="collapse" aria-expanded="{{ $creditsMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $creditsMenuOpen ? 'active' : '' }}" title="Crédits & Prêts">
                <i class="bi bi-cash-stack"></i> <span>Crédits & Prêts</span>
            </a>
            <div class="collapse {{ $creditsMenuOpen ? 'show' : '' }} submenu" id="creditsSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.credits.index') }}"
                    class="{{ request()->routeIs('admin.credits.index') ? 'active' : '' }}">Suivi Crédits</a>
                <a href="{{ route('admin.prets.index') }}"
                    class="{{ request()->routeIs('admin.prets.*') ? 'active' : '' }}">Instruction Crédit</a>
                @can('credits.create')
                    <a href="{{ route('admin.credits.create') }}"
                        class="{{ request()->routeIs('admin.credits.create') ? 'active' : '' }}">Nouvelle Demande</a>
                @endcan
                @can('credits.configure')
                    <a href="{{ route('admin.credit-settings.index') }}"
                        class="{{ request()->routeIs('admin.credit-settings.*') ? 'active' : '' }}">Paramétrage des crédits</a>
                @endcan
            </div>
        @endcan

        @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.open') || auth()->user()->can('treasury.replenish') || auth()->user()->can('treasury.close') || auth()->user()->can('expenses.view') || auth()->user()->can('revenues.view') || auth()->user()->can('charges.view') || auth()->user()->can('charges.manage'))
            <div class="sidebar-header nav-section-title">Trésorerie & comptabilité</div>
        @endif

        @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.open') || auth()->user()->can('treasury.replenish') || auth()->user()->can('treasury.close'))
        <a href="#tresorerieSub"
           data-bs-toggle="collapse"
           aria-expanded="{{ $caisseMenuOpen ? 'true' : 'false' }}"
           class="menu-toggle {{ $caisseMenuOpen ? 'active' : '' }}" title="Mouvements de Caisse">
            <i class="bi bi-wallet"></i> <span>Mouvements de Caisse</span>
        </a>

        <div class="collapse {{ $caisseMenuOpen ? 'show' : '' }} submenu" id="tresorerieSub" data-bs-parent="#sidebarNav">
            @can('treasury.open')
                <a href="{{ route('admin.caisse.ouvrir') }}"
                   class="{{ request()->routeIs('admin.caisse.ouvrir') ? 'active' : '' }}">
                    Ouverture de Caisse
                </a>
            @endcan
            @can('treasury.view')
                <a href="{{ route('admin.caisse.journal') }}"
                   class="{{ request()->routeIs('admin.caisse.journal') ? 'active' : '' }}">
                    Journal de Caisse
                </a>
            @endcan
            @can('treasury.replenish')
                <a href="{{ route('admin.caisse.approvisionner') }}"
                   class="{{ request()->routeIs('admin.caisse.approvisionner*') ? 'active' : '' }}">
                    Approvisionner la Caisse
                </a>
            @endcan
            @can('treasury.close')
                <a href="{{ route('admin.caisse.cloture') }}"
                   class="{{ request()->routeIs('admin.caisse.cloture') ? 'active' : '' }}">
                    Clôture de Caisse
                </a>
            @endcan
            @can('treasury.audit')
                <a href="{{ route('admin.caisse.audit') }}"
                   class="{{ request()->routeIs('admin.caisse.audit') ? 'active' : '' }}">
                    Audit de Caisse
                </a>
            @endcan
        </div>
        @endif

        @if(auth()->user()->can('expenses.view') || auth()->user()->can('expenses.create') || auth()->user()->can('revenues.view') || auth()->user()->can('charges.view') || auth()->user()->can('charges.manage'))
        <a href="#chargesSub" data-bs-toggle="collapse" aria-expanded="{{ $chargesMenuOpen ? 'true' : 'false' }}"
            class="menu-toggle {{ $chargesMenuOpen ? 'active' : '' }}" title="Recettes & Dépenses">
            <i class="bi bi-receipt-cutoff"></i> <span>Recettes & Dépenses</span>
        </a>
        <div class="collapse {{ $chargesMenuOpen ? 'show' : '' }} submenu" id="chargesSub" data-bs-parent="#sidebarNav">
            @can('revenues.view')
                <a href="{{ route('admin.recettes.index') }}"
                    class="{{ request()->routeIs('admin.recettes.index') ? 'active' : '' }}">Recettes d'Exploitation</a>
            @endcan
            @can('expenses.view')
                <a href="{{ route('admin.depenses.index') }}"
                    class="{{ request()->routeIs('admin.depenses.index') ? 'active' : '' }}">Gestion des Dépenses</a>
            @endcan
            @can('charges.view')
                <a href="{{ route('admin.charges.index') }}"
                    class="{{ request()->routeIs('admin.charges.index') ? 'active' : '' }}">Types & Catégories</a>
            @endcan
        </div>
        @endif

        @if(auth()->user()->can('employees.view') || auth()->user()->can('employees.create') || auth()->user()->can('employees.update') || auth()->user()->can('payroll.view') || auth()->user()->can('advances.view') || auth()->user()->can('commissions.view'))
            <div class="sidebar-header nav-section-title">Ressources humaines</div>
        @endif

        @if(auth()->user()->can('employees.view') || auth()->user()->can('employees.create') || auth()->user()->can('employees.update') || auth()->user()->can('payroll_grille.view'))
        <a href="#personnelSub" data-bs-toggle="collapse" aria-expanded="{{ $personnelMenuOpen ? 'true' : 'false' }}"
            class="menu-toggle {{ $personnelMenuOpen ? 'active' : '' }}" title="Personnel & Fonctions">
            <i class="bi bi-people-fill"></i> <span>Personnel & Fonctions</span>
        </a>
        <div class="collapse {{ $personnelMenuOpen ? 'show' : '' }} submenu" id="personnelSub" data-bs-parent="#sidebarNav">
            @can('employees.view')
                <a href="{{ route('admin.employes.index') }}"
                    class="{{ request()->routeIs('admin.employes.*') ? 'active' : '' }}">Liste du Personnel</a>
            @endcan
            @can('employees.update')
                <a href="{{ route('admin.fonctions.index') }}"
                    class="{{ request()->routeIs('admin.fonctions.*') ? 'active' : '' }}">Postes & Fonctions</a>
            @endcan
            @can('payroll_grille.view')
                <a href="{{ route('admin.salary-grids.index') }}"
                    class="{{ request()->routeIs('admin.salary-grids.*') ? 'active' : '' }}">Grille de Salaire</a>
            @endcan
        </div>
        @endif

        @can('commissions.view')
            <a href="#commissionsSub" data-bs-toggle="collapse" aria-expanded="{{ $bonusMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $bonusMenuOpen ? 'active' : '' }}" title="Commissions agents">
                <i class="bi bi-cash-coin"></i> <span>Commissions agents</span>
            </a>
            <div class="collapse {{ $bonusMenuOpen ? 'show' : '' }} submenu" id="commissionsSub" data-bs-parent="#sidebarNav">
                <a href="{{ route('admin.bonuses.index') }}"
                    class="{{ request()->routeIs('admin.bonuses.index') ? 'active' : '' }}">À valider</a>
                <a href="{{ route('admin.bonuses.history') }}"
                    class="{{ request()->routeIs('admin.bonuses.history') ? 'active' : '' }}">Historique</a>
            </div>
        @endcan

        @if(auth()->user()->can('payroll.view') || auth()->user()->can('advances.view'))
            <a href="#payrollSub" data-bs-toggle="collapse" aria-expanded="{{ $payrollMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $payrollMenuOpen ? 'active' : '' }}" title="Salaires & Avances">
                <i class="bi bi-wallet2"></i> <span>Salaires & Avances</span>
            </a>
            <div class="collapse {{ $payrollMenuOpen ? 'show' : '' }} submenu" id="payrollSub" data-bs-parent="#sidebarNav">
                @can('payroll.view')
                    <a href="{{ route('admin.payrolls.index') }}"
                        class="{{ request()->routeIs('admin.payrolls.index') ? 'active' : '' }}">Salaires agents</a>
                    <a href="{{ route('admin.payrolls.employes.index') }}"
                        class="{{ request()->routeIs('admin.payrolls.employes.*') ? 'active' : '' }}">Salaires employés</a>
                @endcan
                @can('advances.view')
                    <a href="{{ route('admin.payrolls.avance') }}"
                        class="{{ request()->routeIs('admin.payrolls.avance') ? 'active' : '' }}">Avances sur salaire</a>
                @endcan
            </div>
        @endif

        @if(auth()->user()->can('users.view') || auth()->user()->can('roles.view') || auth()->user()->can('audits.view'))
            <div class="sidebar-header nav-section-title">Administration & sécurité</div>
        @endif

        @if(auth()->user()->can('users.view') || auth()->user()->can('roles.view'))
            <a href="#usersSub" data-bs-toggle="collapse" aria-expanded="{{ $usersMenuOpen ? 'true' : 'false' }}"
                class="menu-toggle {{ $usersMenuOpen ? 'active' : '' }}" title="Administrateurs">
                <i class="bi bi-shield-check"></i> <span>Administrateurs</span>
            </a>
            <div class="collapse {{ $usersMenuOpen ? 'show' : '' }} submenu" id="usersSub" data-bs-parent="#sidebarNav">
                @can('roles.view')
                    <a href="{{ route('admin.roles.index') }}"
                        class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Rôles & Droits</a>
                @endcan
                @can('users.view')
                    <a href="{{ route('admin.users.index') }}"
                        class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Liste</a>
                @endcan
            </div>
        @endif

        @can('audits.view')
            <a href="{{ route('admin.audit-logs.index') }}"
                class="{{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" title="Journal d’audit">
                <i class="bi bi-shield-lock"></i> <span>Journal d’audit</span>
            </a>
        @endcan

    </div>

    <div class="sidebar-footer p-3 border-top border-secondary border-opacity-10 text-white-50 small">
        Connecté en tant que :<br>
        <span class="text-white fw-bold">{{ auth()->user()->role->nom ?? 'Admin' }}</span>
    </div>
</div>
